<?php

use ChoicePayment\BankInfo;
use ChoicePayment\HttpApi;
use ChoicePayment\WC_Util;

/**
 * @property mixed|null $api_key
 * @property mixed|null $debug_mode
 * @property mixed|null $device_ach_guid
 * @property mixed|null $log_level
 * @property mixed|null $merchant_name
 * @property mixed|null $password
 * @property bool $sandbox_mode
 * @property mixed|null $username
 */
class WC_Gateway_Choice_BankClearing_Subscriptions extends WC_Gateway_Choice_BankClearing {
    use ChoicePayment\GatewayInfo;

    private static $_instance = null;

    private $_access_token = '';

    public function __construct() {
        parent::__construct();

        $this->supports = array(
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

        $order = new WC_Order( $orderId );

        try {

            $orderTotal = wc_format_decimal(
                WC_Util::getWooCommerceData( $order, 'get_total', 'order_total' ),
                2
            );

            $initialPayment = $orderTotal;

            $response = null;

            if ( $initialPayment >= 0 ) {
                $response = $this->processSubscriptionPayment( $order, $initialPayment );
            }

            if ( ! $response ) {
                WC_Util::logMessage( "Subscription payment could not be processed.  Invalid payment response from gateway.", WC_CHOICEPAYMENT_LOG_LEVEL_ERROR );
            }

            if ( isset( $response ) && is_wp_error( $response ) ) {
                throw new Exception( $response->get_error_message() );
            }

            $woocommerce->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order )
            );

        } catch ( Exception $e ) {
            $error = __( 'Error:', 'choicepaynt_gateway' ) . ' "' . (string) $e->getMessage() . '"';
            if ( function_exists( 'wc_add_notice' ) ) {
                wc_add_notice( $error, 'error' );
            } else {
                $woocommerce->add_error( $error );
            }

            return;
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

    public function processSubscriptionPayment( $order, $initialPayment ) {
        global $woocommerce;

        WC_Util::cleanSubmittedFormValues();

        $billing_first_name = isset( $_POST['billing_first_name'] ) ? WC_Util::cleanValue( $_POST['billing_first_name'] ) : '';
        $billing_last_name  = isset( $_POST['billing_last_name'] ) ? WC_Util::cleanValue( $_POST['billing_last_name'] ) : '';

        $name_on_account = $billing_first_name . ' ' . $billing_last_name;

        $routing_number = isset( $_POST['woo-choice-payment_ach_routing'] ) ? WC_Util::cleanValue( $_POST['woo-choice-payment_ach_routing'] ) : '';
        $account_number = isset( $_POST['woo-choice-payment_ach_account'] ) ? WC_Util::cleanValue( $_POST['woo-choice-payment_ach_account'] ) : '';

        $bank_account = new BankInfo(
            $name_on_account,
            $routing_number,
            $account_number
        );

        $order = wc_get_order( $order );

        $orderId = WC_Util::getWooCommerceData( $order, 'get_id', 'id' );

        $tokenValue = get_post_meta( $orderId, '_choicepaynt_gateway_ach_token', true );

        if ( empty( $tokenValue ) ) {
            $this->saveTokenMeta( $order, WC_Util::encodeAchToken( $bank_account ) );
            $tokenValue = get_post_meta( $orderId, '_choicepaynt_gateway_ach_token', true );
        }

        $tokenData = WC_Util::decodeAchToken( $tokenValue );

        if ( ! isset( $tokenValue ) ) {
            return new WP_Error( 'choicepaynt_gateway_error', __( 'Choice payment token not found', 'choicepaynt_gateway' ) );
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

            $orderTotal = wc_format_decimal(
                WC_Util::getWooCommerceData( $order, 'get_total', 'order_total' ),
                2
            );

            $api = new HttpApi( $this->sandbox_mode, $this->log_level );

            $bank_account = array(
                'NameOnAccount' => $tokenData->AccountName,
                'RoutingNumber' => $tokenData->RoutingNumber,
                'AccountNumber' => $tokenData->AccountNumber,
            );

            $userId     = get_current_user_id();
            $customerId = $userId ?? str_replace( ' ', '', $tokenData->AccountName . substr( $tokenData->AccountNumber, - 4 ) );

            $data = array(
                'DeviceGuid'     => $this->device_ach_guid,
                'Amount'         => $orderTotal,
                'CustomData'     => $orderId,
                'CustomerId'     => $customerId,
                'SequenceNumber' => $orderId,
                'SendReceipt'    => false,
                'BankAccount'    => $bank_account
            );

            $endpoint = PAYNT_API_ENDPOINT_BANKCLEARINGS;

            $this->_access_token = $this->getInstanceToken( $this, false );

            WC_Util::logMessage(
                'Processing payment using endpoint: ' . $endpoint . '.' . PHP_EOL
                . 'Bank Account: ' . PHP_EOL . wc_print_r( $bank_account, true ) . PHP_EOL
                . 'Order data: ' . PHP_EOL . wc_print_r( $data, true ) . PHP_EOL,
                $this->log_level
            );

            $result            = null;
            $payment_succeeded = false;
            $attempts          = 0;

            while ( ! $payment_succeeded && $attempts < PAYNT_API_PROCESS_MAX_ATTEMPTS ) {
                try {
                    $attempts ++;
                    $result = $api->ProcessPayment( $endpoint, $data, $this->_access_token );
                } catch ( \Exception $e ) {
                    $this->displayErrorMessage( 'Error while processing payment. ' . $e->getMessage() );
                    WC_Util::logMessage( 'Error while contacting API. ' . PHP_EOL . $e->getMessage(), $this->log_level );
                }

                $payment_succeeded = $this->process_payment_result( $result, $order, $orderId );
            }

            if ( $result ) {

                if ( $result['response'] && $result['response']['code'] && (
                        $result['response']['code'] === 201 ||
                        $result['response']['code'] === 200
                    )
                ) {

                    // Ensure the bank info is saved
                    $this->saveTokenMeta( $order, WC_Util::encodeAchToken( $tokenData ) );

                    WC()->cart->empty_cart();

                    return array(
                        'result'   => 'success',
                        'redirect' => $this->get_return_url( $order ),
                    );

                } else {

                    WC_Util::logMessage( 'Invalid response from API.', $this->log_level );

                    // payment failed
                    if ( $result['response'] ) {
                        $message = $result['response']['message'];
                    }
                    if ( $result['body'] ) {
                        $response_data = \json_decode( $result['body'] );
                        if ( $response_data && property_exists( $response_data, 'processorResponseMessage' ) ) {
                            $message = $response_data->processorResponseMessage;
                        }
                    }
                    if ( $order ) {
                        $order->add_order_note(
                            __(
                                'ACH Payment failed. ',
                                'choicepaynt_gateway'
                            ) . $message
                        );
                        $order->set_status( 'failed', 'Payment failed.' );
                    }
                }
            } else {
                WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
            }


        } catch ( Exception $e ) {
            return new WP_Error( 'choicepaynt_gateway_error', sprintf( __( 'Choice Payment payment error: %s', 'choicepaynt_gateway' ), (string) $e->getMessage() ) );
        }

        return $result;
    }

    public function updateFailingPaymentMethod( $old, $new, $key = null ) {
        $oldOrderId = WC_Util::getWooCommerceData( $old, 'get_id', 'id' );
        $newOrderId = WC_Util::getWooCommerceData( $new, 'get_id', 'id' );

        update_post_meta( $oldOrderId, '_choicepaynt_gateway_card_token', get_post_meta( $newOrderId, '_choicepaynt_gateway_card_token', true ) );
    }

    public function addSubscriptionPaymentMeta( $meta, $subscription ) {
        $subscriptionId = WC_Util::getWooCommerceData( $subscription, 'get_id', 'id' );

        $meta[ $this->id ] = array(
            'post_meta' => array(
                '_choicepaynt_gateway_ach_token' => array(
                    'value' => get_post_meta( $subscriptionId, '_choicepaynt_gateway_ach_token', true ),
                    'label' => 'Choice payment token',
                ),
            ),
        );

        return $meta;
    }

    public function validateSubscriptionPaymentMeta( $methodId, $meta ) {
        if ( $this->id === $methodId ) {
            $token = $meta['post_meta']['_choicepaynt_gateway_card_token'];
            if ( ! isset( $token ) || empty( $token ) ) {
                throw new Exception( __( 'A Choice payment token is required.', 'choicepaynt_gateway' ) );
            }
        }
    }

    public function deleteResubscribeMeta( $order ) {
        $orderId = WC_Util::getWooCommerceData( $order, 'get_id', 'id' );
        delete_user_meta( $orderId, '_choicepaynt_gateway_card_token' );
    }

    protected function saveTokenMeta( $order, $token ) {
        $orderId = WC_Util::getWooCommerceData( $order, 'get_id', 'id' );
        $order   = wc_get_order( $orderId );
        update_post_meta( $orderId, '_choicepaynt_gateway_ach_token', $token );

        if ( method_exists( $order, 'set_payment_method' ) ) {
            $order->set_payment_method( $this->id, array(
                'post_meta' => array(
                    '_choicepaynt_gateway_ach_token' => $token,
                ),
            ) );
        }

        // save to subscriptions in order
        foreach ( wcs_get_subscriptions_for_order( $orderId ) as $subscription ) {
            $subscriptionId = WC_Util::getWooCommerceData( $subscription, 'get_id', 'id' );
            update_post_meta( $subscriptionId, '_choicepaynt_gateway_ach_token', $token );

            if ( method_exists( $order, 'set_payment_method' ) ) {
                $order->set_payment_method( $this->id, array(
                    'post_meta' => array(
                        '_choicepaynt_gateway_ach_token' => $token,
                    ),
                ) );
            }
        }
    }

}
