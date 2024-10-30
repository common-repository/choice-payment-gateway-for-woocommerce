<?php

use ChoicePayment\HttpApi;
use ChoicePayment\Payment;
use ChoicePayment\Customer;
use ChoicePayment\WC_Util;

class WC_Gateway_Choice_Paynt_Subscriptions extends WC_Gateway_Choice_Paynt {
    /***
     * Subscription payment using Choice Payment Gateway
     */

    use ChoicePayment\GatewayInfo;

    private static $_instance = null;

    private $_access_token = '';

    public function __construct() {
        parent::__construct();

        $this->supports               = array(
            'products',
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_admin',
            'subscription_payment_method_change_customer',
            'subscription_date_changes',
            'multiple_subscriptions',
        );

        add_action( 'plugins_loaded', array( $this, 'init' ) );

    }

    public function init() {
        if ( class_exists( 'WC_Subscriptions_Order' ) ) {
            add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array(
                $this,
                'scheduledSubscriptionPayment'
            ), 10, 2 );
            add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->id, array(
                $this,
                'updateFailingPaymentMethod'
            ), 10, 2 );
            add_action( 'wcs_resubscribe_order_created', array( $this, 'deleteResubscribeMeta' ), 10 );
            add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'addSubscriptionPaymentMeta' ), 10, 2 );
            add_filter( 'woocommerce_subscription_validate_payment_meta', array(
                $this,
                'validateSubscriptionPaymentMeta'
            ), 10, 2 );
        }
    }

    public function process_payment( $order_id ) {
        if ( ( $this->orderHasSubscription( $order_id )
               || ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order_id ) ) )
        ) {
            return $this->processSubscription( $order_id );
        } else {
            return parent::process_payment( $order_id );
        }
    }

    protected function orderHasSubscription( $orderId ) {
        return function_exists( 'wcs_order_contains_subscription' )
               && ( wcs_order_contains_subscription( $orderId ) || wcs_order_contains_renewal( $orderId ) );
    }

    protected function orderGetTotal( $order ) {
        if ( method_exists( $order, 'get_total' ) ) {
            return $order->get_total();
        }

        return WC_Subscriptions_Order::get_total_initial_payment( $order );
    }

    public function processSubscription( $orderId ) {
        global $woocommerce;

        $order = new WC_Order($orderId);
        $useStoredCard = false;

        WC_Util::cleanSubmittedFormValues();

        $card_token = WC_Util::cleanValue( $_POST['paynt_card_token'] );
        $auth_token = WC_Util::cleanValue( $_POST['paynt_access_token'] );

        $orderTotal = wc_format_decimal(
            WC_Util::getWooCommerceData( $order, 'get_total', 'order_total' ),
            2
        );

        $api = new HttpApi( $this->sandbox_mode, $this->log_level );

        // used for card saving:
        $last_four = isset($_POST['last_four']) ? WC_Util::cleanValue($_POST['last_four']) : '';
        $exp_month = isset($_POST['exp_month']) ? WC_Util::cleanValue($_POST['exp_month']) : '';
        $exp_year = isset($_POST['exp_year']) ? WC_Util::cleanValue($_POST['exp_year']) : '';
        $card_type = isset($_POST['card_type']) ? WC_Util::cleanValue($_POST['card_type']) : '';
        $saveCard = isset($_POST['save_card']) ? WC_Util::cleanValue($_POST['save_card']) : '';

        try {
            if (empty($card_token)) {
                if (isset($_POST['secure_submit_card']) && $_POST['secure_submit_card'] === 'new') {
                    throw new Exception(__('Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'wc_securesubmit'));
                }
            }

            if (is_user_logged_in() && isset($_POST['choice_payment_card']) && $_POST['choice_payment_card'] !== 'new') {
                $cards = get_user_meta(get_current_user_id(), '_choice_payment_card', false);

                if (isset($cards[$_POST['choice_payment_card']]['token_value'])) {
                    $tokenValue = (string)$cards[(int)$_POST['_choice_payment_card']]['token_value'];
                    $useStoredCard = true;
                } else {
                    throw new Exception(__('Invalid saved card.', 'choicepaynt_gateway'));
                }
            } else {
                $tokenValue = $card_token;
            }

            try {
                $saveCardToCustomer = !$useStoredCard;

                $orderTotal = wc_format_decimal(
                    WC_Util::getWooCommerceData( $order, 'get_total', 'order_total' ),
                    2
                );

                $initialPayment = $orderTotal;

                $response = null;

                if ($initialPayment >= 0) {
                    $response = $this->processSubscriptionPayment($order, $initialPayment, $tokenValue, $saveCardToCustomer);
                }

                if (!$response) {
                    WC_Util::logMessage("Subscription payment could not be processed.  Invalid payment response from gateway.", WC_CHOICEPAYMENT_LOG_LEVEL_ERROR);
                }

                if (isset($response) && is_wp_error($response)) {
                    throw new Exception($response->get_error_message());
                }

                $woocommerce->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } catch (HpsException $e) {
                throw new Exception(__((string)$e->getMessage(), 'choicepaynt_gateway'));
            }
        } catch (Exception $e) {
            $error = __('Error:', 'choicepaynt_gateway') . ' "' . (string)$e->getMessage() . '"';
            if (function_exists('wc_add_notice')) {
                wc_add_notice($error, 'error');
            } else {
                $woocommerce->add_error($error);
            }
            return;
        }
    }

    protected function saveTokenMeta( $order, $token ) {
        $orderId = WC_Util::getWooCommerceData($order, 'get_id', 'id');
        $order = wc_get_order($orderId);
        update_post_meta($orderId, '_choicepaynt_gateway_card_token', $token);

        if (method_exists($order, 'set_payment_method')) {
            $order->set_payment_method($this->id, array(
                'post_meta' => array(
                    '_choicepaynt_gateway_card_token' => $token,
                ),
            ));
        }

        // save to subscriptions in order
        foreach (wcs_get_subscriptions_for_order($orderId) as $subscription) {
            $subscriptionId = WC_Util::getWooCommerceData($subscription, 'get_id', 'id');
            update_post_meta($subscriptionId, '_choicepaynt_gateway_card_token', $token);

            if (method_exists($order, 'set_payment_method')) {
                $order->set_payment_method($this->id, array(
                    'post_meta' => array(
                        '_choicepaynt_gateway_card_token' => $token,
                    ),
                ));
            }
        }
    }

    public function scheduledSubscriptionPayment( $amount, $order, $productId = null ) {
        $orderPostStatus = WC_Util::getWooCommerceData( $order, 'get_post_status', 'post_status' );
        if ( empty( $orderPostStatus ) ) {
            $orderPostStatus = get_post_status( WC_Util::getWooCommerceData( $order, 'get_id', 'id' ) );
        }

        // If the order has a _transaction_id in the post_meta, then payment is not needed.
        $orderId        = WC_Util::getWooCommerceData( $order, 'get_id', 'id' );
        $transaction_id = get_post_meta( $orderId, '_transaction_id', true );
        if ( isset( $transaction_id ) && ! empty( $transaction_id ) ) {
            return;
        }

        $result = $this->processSubscriptionPayment( $order, wc_format_decimal( $amount, 2 ) );

        if ( is_wp_error( $result ) ) {
            $order->update_status( 'failed', sprintf( __( 'Choice Payment transaction failed: %s', 'choicepaynt_gateway' ), $result->get_error_message() ) );
        }
    }

    public function processSubscriptionPayment( $order, $initialPayment, $tokenData = null, $requestMulti = false ) {
        global $woocommerce;

        WC_Util::cleanSubmittedFormValues();

        $auth_token = WC_Util::cleanValue( $_POST['paynt_access_token'] );

        $order = wc_get_order($order);

        $orderId = WC_Util::getWooCommerceData($order, 'get_id', 'id');

        $tokenValue = get_post_meta($orderId, '_choicepaynt_gateway_card_token', true);

        if (empty($tokenValue)) {
            $tokenValue = $tokenData;
            $this->saveTokenMeta( $order, $tokenValue );
        }

        $orderTotal = wc_format_decimal(
            WC_Util::getWooCommerceData( $order, 'get_total', 'order_total' ),
            2
        );

        $api = new HttpApi( $this->sandbox_mode, $this->log_level );

        if (!isset($tokenValue)) {
            return new WP_Error('choicepaynt_gateway_error', __('Choice payment token not found', 'choicepaynt_gateway'));
        }

        try {

            // Get service fee
            $service_fee   = 0.00;
            $cash_discount = 0.00;
            foreach ( $order->get_fees() as $fee ) {
                $name = $fee->get_name();
                if ( $name === WC_CHOICEPAYMENT_SERVICE_FEE_LABEL ) {
                    $service_fee = wc_format_decimal( $fee->get_amount(), 2 );
                }
                if ( $name === WC_CHOICEPAYMENT_CASH_DISCOUNT_LABEL ) {
                    $cash_discount = wc_format_decimal( $fee->get_amount(), 2 );
                }
            }

            $card_data = array(
                'CardNumber'     => $tokenValue,
            );

            $data = array(
                'DeviceGuid'     => $this->device_cc_guid,
                'Amount'         => $orderTotal,
                'OrderNumber'    => (string)$order_id,
                'OrderDate'      => date( 'Y-m-d' ),
                'SequenceNumber' => (string)$order_id,
                'SendReceipt'    => false,
                'Card'           => $card_data,
            );

            if ( $service_fee ) {
                $new_amount           = wc_format_decimal( $orderTotal - $service_fee, 2 );
                $data['ServiceFee']   = $service_fee;
                $data['CashDiscount'] = $cash_discount;
                $data['Amount']       = $new_amount;
                $data['GrossAmount']  = $orderTotal;
            }

            $endpoint = PAYNT_API_ENDPOINT_SALE;
            $verb     = 'captured';

            if ( $this->payment_action !== 'sale' ) {
                $endpoint = PAYNT_API_ENDPOINT_AUTHONLY;
                $verb     = 'authorized';
            }

            WC_Util::logMessage(
                'Processing payment using endpoint: ' . $endpoint . '.' . PHP_EOL
                . 'Card data: ' . PHP_EOL . wc_print_r( $card_data, true ) . PHP_EOL
                . 'Order data: ' . PHP_EOL . wc_print_r( $data, true ) . PHP_EOL,
                $this->log_level
            );

            $result            = null;
            $payment_succeeded = false;
            $attempts          = 0;

            while ( ! $payment_succeeded && $attempts < PAYNT_API_PROCESS_MAX_ATTEMPTS ) {
                try {
                    $attempts ++;
                    $result = $api->ProcessPayment( $endpoint, $data, $auth_token );
                } catch ( \Exception $e ) {
                    $this->displayErrorMessage( 'Error while processing payment. ' . $e->getMessage() );
                    WC_Util::logMessage( 'Error while contacting API. ' . PHP_EOL . $e->getMessage(), $this->log_level );
                }

                $payment_succeeded = parent::process_payment_result( $result, $order, $order_id );
            }

        } catch (Exception $e) {
            return new WP_Error('choicepaynt_gateway_error', sprintf(__('Choice Payment payment error: %s', 'choicepaynt_gateway'), (string)$e->getMessage()));
        }

        return $result;
    }

    public function updateFailingPaymentMethod( $old, $new, $key = null ) {
        $oldOrderId = WC_Util::getWooCommerceData($old, 'get_id', 'id');
        $newOrderId = WC_Util::getWooCommerceData($new, 'get_id', 'id');

        update_post_meta($oldOrderId, '_choicepaynt_gateway_card_token', get_post_meta($newOrderId, '_choicepaynt_gateway_card_token', true));
    }

    public function addSubscriptionPaymentMeta( $meta, $subscription ) {
        $subscriptionId = WC_Util::getWooCommerceData($subscription, 'get_id', 'id');

        $meta[$this->id] = array(
            'post_meta' => array(
                '_choicepaynt_gateway_card_token' => array(
                    'value' => get_post_meta($subscriptionId, '_choicepaynt_gateway_card_token', true),
                    'label' => 'Choice payment token',
                ),
            ),
        );
        return $meta;
    }

    public function validateSubscriptionPaymentMeta( $methodId, $meta ) {
        if ($this->id === $methodId) {
            $token = $meta['post_meta']['_choicepaynt_gateway_card_token'];
            if (!isset($token) || empty($token)) {
                throw new Exception(__('A Choice payment token is required.', 'choicepaynt_gateway'));
            }
        }
    }

    public function deleteResubscribeMeta( $order ) {
        $orderId = WC_Util::getWooCommerceData($order, 'get_id', 'id');
        delete_user_meta($orderId, '_choicepaynt_gateway_card_token');
    }
}
