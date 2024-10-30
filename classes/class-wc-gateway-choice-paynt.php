<?php

use ChoicePayment\HttpApi;
use ChoicePayment\Payment;
use ChoicePayment\Customer;
use ChoicePayment\WC_Util;

/**
 * @property mixed|null api_key
 * @property mixed|null device_cc_guid
 * @property mixed|null device_ach_guid
 * @property mixed|null sandbox_mode
 * @property mixed|null merchant_name
 * @property mixed|null password
 * @property mixed|null username
 * @property mixed|null debug_mode
 * @property mixed|null payment_action
 * @property mixed|null $log_level
 * @property bool $service_fee
 * @property mixed|null $service_fee_percent
 * @property mixed|null $service_fee_max
 * @property mixed|null $service_fee_name
 * @property mixed|null $service_fee_type
 * @property mixed|null $service_fee_tax_type
 * @property mixed|null $service_fee_tax_class
 * @property mixed|null $service_fee_disclaimer
 * @property string $allow_card_saving
 */
class WC_Gateway_Choice_Paynt extends WC_Payment_Gateway {
    use ChoicePayment\GatewayInfo;

    private static $_instance = null;

    private $_access_token = '';

    public function __construct() {
        // includes

        // properties
        $this->id           = 'choicepaynt';
        $this->method_title = __( 'Choice Payment', 'choicepaynt_gateway' );
        $this->has_fields   = true;
        $this->initFormFields();
        $this->init_settings();
        $this->title                  = $this->getSetting( 'title' );
        $this->description            = $this->getSetting( 'description' );
        $this->enabled                = $this->getSetting( 'enabled' );
        $this->api_key                = $this->getSetting( 'api_key' );
        $this->debug_mode             = $this->getSetting( 'debug_mode' );
        $this->device_cc_guid         = $this->getSetting( 'device_cc_guid' );
        $this->device_ach_guid        = $this->getSetting( 'device_ach_guid' );
        $this->log_level              = $this->getSetting( 'log_level' );
        $this->merchant_name          = $this->getSetting( 'merchant_name' );
        $this->method_description     = 'Process credit cards via Choice Payment gateway.';
        $this->password               = $this->getSetting( 'password' );
        $this->payment_action         = $this->getSetting( 'payment_action' );
        $this->sandbox_mode           = wc_string_to_bool( $this->getSetting( 'sandbox_mode' ) );
        $this->supports               = array( 'products', 'refunds' );
        $this->username               = $this->getSetting( 'username' );
        $this->service_fee            = wc_string_to_bool( $this->getSetting( 'service_fee' ) );
        $this->service_fee_type       = $this->getSetting( 'service_fee_type' );
        $this->service_fee_percent    = $this->getSetting( 'service_fee_percent' );
        $this->service_fee_max        = $this->getSetting( 'service_fee_max' );
        $this->service_fee_name       = $this->getSetting( 'service_fee_name' );
        $this->service_fee_tax_type   = $this->getSetting( 'service_fee_tax_type' );
        $this->service_fee_tax_class  = $this->getSetting( 'service_fee_tax_class' );
        $this->service_fee_disclaimer = $this->getSetting( 'service_fee_disclaimer' );
        $this->allow_card_saving      = 'yes';

        // actions
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array(
                $this,
                'process_admin_options',
            )
        );

    }

    public static function getInstance(): ?WC_Gateway_Choice_Paynt {
        if ( null === self::$_instance ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function get_temp_token() {
        $access_token = $this->getInstanceToken( $this, true );
        echo wp_kses( $access_token, array() );
        wp_die();
    }

    public function is_available(): bool {
        return $this->enabled == 'yes';
    }

    public function initFormFields() {
        $path              = dirname( plugin_dir_path( __FILE__ ) );
        $this->form_fields = include $path . '/etc/choicepaynt-options.php';
    }

    public function payment_fields() {
        $path = dirname( plugin_dir_path( __FILE__ ) );
        include $path . '/templates/payment-fields.php';
    }

    public function payment_scripts() {
        // @codingStandardsIgnoreEnd PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        if ( ( ! is_checkout() && ! is_wc_endpoint_url( 'add-payment-method' ) )
             || is_wc_endpoint_url( 'order-received' )
        ) {
            return;
        }
        wp_enqueue_script( 'woo_choice_payment', plugins_url( 'assets/js/woo-choice-payment.js', dirname( __FILE__ ) ), array( 'jquery' ), WC_CHOICEPAYMENT_PLUGIN_VERSION, true );
        wp_enqueue_style( 'woo_choice_payment', plugins_url( 'assets/css/woo-choice-payment.css', dirname( __FILE__ ) ), array(), WC_CHOICEPAYMENT_PLUGIN_VERSION );

        $api = new HttpApi( $this->sandbox_mode, $this->log_level );

        $woo_choice_payment_params = array(
            'sandbox_mode'     => $this->sandbox_mode,
            'ajax_url'         => admin_url( 'admin-ajax.php' ),
            'device_cc_guid'   => $this->device_cc_guid,
            'device_ach_guid'  => $this->device_ach_guid,
            'tokenization_url' => $api->GetApiUrl() . PAYNT_API_ENDPOINT_TOKENIZATION,
        );

        wp_localize_script( 'woo_choice_payment', 'woo_choice_payment_params', $woo_choice_payment_params );
    }

    public function process_payment( $order_id ) {
        global $wpdb;

        $order = wc_get_order( $order_id );

        WC_Util::cleanSubmittedFormValues();

        $expiration       = isset( $_POST['woo-choice-payment_card_expiration'] ) ? WC_Util::cleanValue( $_POST['woo-choice-payment_card_expiration'] ) : '';
        $expiration_split = explode( '/', $expiration );
        $expiration_date  = substr( str_replace( ' ', '', $expiration_split[1] ), 2, 2 )
                            . str_replace( ' ', '', $expiration_split[0] );

        $card_zip               = isset( $_POST['billing_postcode'] ) ? WC_Util::cleanValue( $_POST['billing_postcode'] ) : '';
        $billing_first_name     = isset( $_POST['billing_first_name'] ) ? WC_Util::cleanValue( $_POST['billing_first_name'] ) : '';
        $billing_last_name      = isset( $_POST['billing_last_name'] ) ? WC_Util::cleanValue( $_POST['billing_last_name'] ) : '';
        $billing_address1       = isset( $_POST['billing_address_1'] ) ? WC_Util::cleanValue( $_POST['billing_address_1'] ) : '';
        $billing_address2       = isset( $_POST['billing_address_2'] ) ? WC_Util::cleanValue( $_POST['billing_address_2'] ) : '';
        $billing_city           = isset( $_POST['billing_city'] ) ? WC_Util::cleanValue( $_POST['billing_city'] ) : '';
        $billing_state_province = isset( $_POST['billing_state'] ) ? WC_Util::cleanValue( $_POST['billing_state'] ) : '';
        $billing_postcode       = isset( $_POST['billing_postcode'] ) ? WC_Util::cleanValue( $_POST['billing_postcode'] ) : '';
        $billing_country        = isset( $_POST['billing_country'] ) ? WC_Util::cleanValue( $_POST['billing_country'] ) : '';
        $billing_phone          = isset( $_POST['billing_phone'] ) ? WC_Util::cleanValue( $_POST['billing_phone'] ) : '';
        $billing_email          = isset( $_POST['billing_email'] ) ? WC_Util::cleanValue( $_POST['billing_email'] ) : '';
        $cvv                    = isset( $_POST['woo-choice-payment_card_cvv'] ) ? WC_Util::cleanValue( $_POST['woo-choice-payment_card_cvv'] ) : '';

        $cardholder_name = $billing_first_name . ' ' . $billing_last_name;

        $card_token = WC_Util::cleanValue( $_POST['paynt_card_token'] );
        $auth_token = WC_Util::cleanValue( $_POST['paynt_access_token'] );

        $orderTotal = wc_format_decimal(
            WC_Util::getWooCommerceData( $order, 'get_total', 'order_total' ),
            2
        );

        $api = new HttpApi( $this->sandbox_mode, $this->log_level );

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
            'CardNumber'     => $card_token,
            'CardHolderName' => $cardholder_name,
            'Cvv2'           => $cvv,
            'ExpirationDate' => $expiration_date,
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

            $payment_succeeded = $this->process_payment_result( $result, $order, $order_id );
        }

        if ( $result ) {

            if ( $result['response'] && $result['response']['code'] && (
                    $result['response']['code'] === 201
                    || $result['response']['code'] === 200
                )
            ) {
                $response_data = \json_decode( $result['body'] );

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
                            'Payment failed. ',
                            'choicepaynt_gateway'
                        ) . $message
                    );
                    $order->set_status( 'failed', 'Payment failed.' );
                }
            }
        } else {
            WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
        }

        return array(
            'result'   => 'fail',
            'redirect' => '',
        );

    }

    protected function process_payment_result( $result, $order, $order_id ): bool {
        if ( ! $result ) {
            return false;
        }

        $verb = 'captured';

        if ( $this->payment_action !== 'sale' ) {
            $verb = 'authorized';
        }

        if ( $result instanceof WP_Error ) {

            WC_Util::logMessage(
                "There was an error communicating with PAYNT API.\n"
                . $result->get_error_message(),
                $this->log_level
            );

            return false;

        }

        $response_data = null;
        if ( $result['response'] && $result['response']['code'] && (
                $result['response']['code'] === 201
                || $result['response']['code'] === 200
            )
        ) {
            $response_data = \json_decode( $result['body'] );
        }

        if ( $response_data ) {
            $payment_guid               = $response_data->guid;
            $payment_amount             = wc_format_decimal( $response_data->amount, 2 );
            $payment_status             = $response_data->status;
            $payment_processor_status   = $response_data->processorStatusCode;
            $payment_processor_response = $response_data->processorResponseMessage;
            $payment_was_processed      = $response_data->wasProcessed;
            $payment_auth_code          = $response_data->authCode;
            $payment_ref_number         = $response_data->refNumber;

            if ( $order && (
                    $payment_status === PAYNT_API_STATUS_APPROVED
                    || $payment_processor_status === "A0000"
                    || stripos( $payment_processor_response, "success" ) !== false
                )
            ) {

                $order->add_order_note(
                    __(
                        'ChoicePaynt payment ' . $verb . ' $' . $payment_amount,
                        'choicepaynt_gateway'
                    ) . ' (Transaction ID: ' . $response_data->refNumber . ')'
                );

                // update order post_meta
                $prefix = '_woochoice_payment';
                update_post_meta( $order_id, $prefix . '_action', $this->payment_action );
                update_post_meta( $order_id, $prefix . '_guid', $payment_guid );
                update_post_meta( $order_id, $prefix . '_amount', $payment_amount );
                update_post_meta( $order_id, $prefix . '_status', $payment_status );
                update_post_meta( $order_id, $prefix . '_processor_status', $payment_processor_status );
                update_post_meta( $order_id, $prefix . '_processor_response', $payment_processor_response );
                update_post_meta( $order_id, $prefix . '_was_processed', $payment_was_processed );
                update_post_meta( $order_id, $prefix . '_auth_code', $payment_auth_code );
                update_post_meta( $order_id, $prefix . '_ref_number', $payment_ref_number );

                $order->payment_complete( $response_data->refNumber );
            }
        } else {
            WC_Util::logMessage( 'Invalid response from API.', $this->log_level );

            return false;
        }

        return true;
    }

    public function process_capture( $order ) {
        if ( ! $order ) {
            return;
        }

        $api = new HttpApi( $this->sandbox_mode, $this->log_level );

        // check if authorization is still active on gateway
        $order_id       = WC_Util::getWooCommerceData( $order, 'get_id', 'id' );
        $auth_guid      = get_post_meta( $order_id, '_woochoice_payment_guid', true );
        $capture_guid   = get_post_meta( $order_id, '_woochoice_payment_capture_guid', true );
        $payment_action = get_post_meta( $order_id, '_woochoice_payment_action', true );

        if ( $payment_action != 'verify' && $capture_guid ) {
            WC_Util::logMessage( "Payment already captured for Order #{$order_id}.", $this->log_level );
            $this->displayErrorMessage( 'Payment already captured' );

            return;
        }

        $orderTotal = wc_format_decimal(
            WC_Util::getWooCommerceData( $order, 'get_total', 'order_total' ),
            2
        );

        $data = array(
            'DeviceGuid'   => $this->device_cc_guid,
            'AuthOnlyGuid' => $auth_guid,
            'NewAmount'    => $orderTotal,
        );

        $verb = 'captured';

        WC_Util::logMessage(
            'Processing delayed capture using endpoint: ' . PAYNT_API_ENDPOINT_CAPTURE . '.' . PHP_EOL
            . 'Order data: ' . PHP_EOL . wc_print_r( $data, true ) . PHP_EOL,
            $this->log_level
        );

        $result = null;

        try {
            $auth_token = $this->getInstanceToken( $this, false );
            $result     = $api->ProcessPayment( PAYNT_API_ENDPOINT_CAPTURE, $data, $auth_token );
        } catch ( \Exception $e ) {
            $this->displayErrorMessage( 'Error while processing capture. ' . $e->getMessage() );
            WC_Util::logMessage( 'Error while contacting API. ' . PHP_EOL . $e->getMessage(), $this->log_level );
        }

        if ( $result ) {

            if ( $result['response'] && $result['response']['code'] && (
                    $result['response']['code'] === 201
                    || $result['response']['code'] === 200
                )
            ) {
                $response_data = \json_decode( $result['body'] );

                if ( $response_data ) {
                    $payment_guid               = $response_data->guid;
                    $payment_amount             = $response_data->newAmount;
                    $payment_status             = $response_data->status;
                    $payment_processor_status   = $response_data->processorStatusCode;
                    $payment_processor_response = $response_data->processorResponseMessage;
                    $payment_was_processed      = $response_data->wasProcessed;
                    $payment_auth_code          = $response_data->authCode;
                    $payment_ref_number         = $response_data->refNumber;

                    $order->add_order_note(
                        __(
                            'ChoicePaynt payment ' . $verb . ' $' . $orderTotal,
                            'choicepaynt_gateway'
                        ) . ' (Transaction ID: ' . $response_data->refNumber . ')'
                    );

                    // update order post_meta
                    $prefix = '_woochoice_payment_capture';
                    update_post_meta( $order_id, $prefix . '_guid', $payment_guid );
                    update_post_meta( $order_id, $prefix . '_amount', $payment_amount );
                    update_post_meta( $order_id, $prefix . '_status', $payment_status );
                    update_post_meta( $order_id, $prefix . '_processor_status', $payment_processor_status );
                    update_post_meta( $order_id, $prefix . '_processor_response', $payment_processor_response );
                    update_post_meta( $order_id, $prefix . '_was_processed', $payment_was_processed );
                    update_post_meta( $order_id, $prefix . '_auth_code', $payment_auth_code );
                    update_post_meta( $order_id, $prefix . '_ref_number', $payment_ref_number );
                }

                return true;

            } else {
                WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
            }
        } else {
            WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
        }

        return false;
    }

    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return false;
        }

        // Get the sale guid
        $payment_guid       = get_post_meta( $order_id, '_woochoice_payment_guid', true );
        $capture_guid       = get_post_meta( $order_id, '_woochoice_payment_capture_guid', true );
        $payment_ref_number = get_post_meta( $order_id, '_woochoice_payment_ref_number', true );
        $payment_action     = get_post_meta( $order_id, '_woochoice_payment_action', true );

        $sale_guid_label = 'SaleGuid';

        if ( $payment_action === 'authorization' ) {
            $sale_guid_label = 'AuthOnlyGuid';
        }

        if ( $capture_guid ) {
            $payment_guid = $capture_guid;
        }

        if ( ! $payment_guid ) {
            return false;
        }

        $api = new HttpApi( $this->sandbox_mode, $this->log_level );

        try {

            $refund_amount = wc_format_decimal( $amount, 2 );

            // The paynt API can't void authonly by guid, so we are using the refnumber.
            $data = array(
                'DeviceGuid'          => $this->device_cc_guid,
                'SaleReferenceNumber' => $payment_ref_number,
                'Amount'              => $refund_amount,
            );

            WC_Util::logMessage(
                'Processing return using endpoint: ' . PAYNT_API_ENDPOINT_RETURN . '.' . PHP_EOL
                . 'data: ' . PHP_EOL . wc_print_r( $data, true ) . PHP_EOL,
                $this->log_level
            );

            $auth_token = $this->getInstanceToken( $this, false );

            $result = $api->ProcessPayment( PAYNT_API_ENDPOINT_RETURN, $data, $auth_token );

            if ( $result ) {

                if ( $result instanceof WP_Error ) {

                    WC_Util::logMessage(
                        "There was an error communicating with PAYNT API.\n"
                        . $result->get_error_message(),
                        $this->log_level
                    );

                    return false;

                }

                $response_code = null;

                if ( $result['response'] && $result['response']['code'] ) {
                    $response_code = $result['response']['code'];
                }
                if ( $response_code === 201 || $response_code === 200 ) {
                    $response_data = \json_decode( $result['body'] );
                } else {
                    // Try Voiding if this has not been settled yet.
                    $data    = array(
                        'DeviceGuid'     => $this->device_cc_guid,
                        $sale_guid_label => $payment_guid,
                    );
                    $result2 = $api->ProcessPayment( PAYNT_API_ENDPOINT_VOID, $data, $auth_token );

                    if ( ! $result2 ) {
                        return false;
                    }

                    $response_data = null;
                    if ( $result2['response'] && $result2['response']['code'] && (
                            $result2['response']['code'] === 201 ||
                            $result2['response']['code'] === 200
                        )
                    ) {
                        $response_data = \json_decode( $result2['body'] );
                    }

                    if ( $response_data ) {
                        $void_guid      = $response_data->guid;
                        $payment_amount = wc_format_decimal( $response_data->amount, 2 );
                        $payment_status = $response_data->status;

                        if ( $order && $payment_status === PAYNT_API_STATUS_APPROVED ) {

                            $order->add_order_note(
                                __(
                                    'ChoicePaynt payment voided.',
                                    'choicepaynt_gateway'
                                ) . ' (Transaction ID: ' . $response_data->refNumber . ')'
                            );

                            // Update order
                            // update order post_meta
                            $prefix = '_woochoice_void';
                            update_post_meta( $order_id, $prefix . '_guid', $void_guid );

                            // Void successful
                            return true;
                        }
                    }

                    // Return failed
                    return false;
                }

                if ( $response_data ) {
                    $refund_guid    = $response_data->guid;
                    $payment_status = $response_data->status;

                    if ( $payment_status === PAYNT_API_STATUS_APPROVED ) {

                        $order->add_order_note(
                            __(
                                'ChoicePaynt refunded $' . $refund_amount,
                                'choicepaynt_gateway'
                            ) . ' (Transaction ID: ' . $response_data->refNumber . ')'
                        );

                        // update order post_meta
                        $prefix = '_woochoice_refund';
                        update_post_meta( $order_id, $prefix . '_guid', $refund_guid );
                        update_post_meta( $order_id, $prefix . '_amount', $refund_amount );
                        update_post_meta( $order_id, $prefix . '_reason', $reason );

                        // Refund succeeded
                        return true;
                    } else {
                        $order->add_order_note(
                            __(
                                'ChoicePaynt refund failed with status of "' . $payment_status . '." Login to Choice WebPortal to manually refund payment.',
                                'choicepaynt_gateway'
                            ) . ' (Transaction ID: ' . $response_data->refNumber . ')'
                        );
                    }
                }
            } else {
                WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
            }
        } catch ( \Exception $e ) {
            WC_Util::logMessage( "Error while trying processing refund. \n" . $e->getMessage(), $this->log_level );
        }

        // Refund failed
        return false;
    }

    public function addDelayedCaptureOrderAction( $actions ) {
        $actions[ $this->id . '_capture' ] = __( 'Capture credit card authorization', 'choicepaynt_gateway' );

        return $actions;
    }

    public function isAuthOnlyActive( $auth_guid, $capture_guid ) {
        $api        = new HttpApi( $this->sandbox_mode, $this->log_level );
        $auth_token = $this->getInstanceToken( $this, false );
        $is_active  = false;

        WC_Util::logMessage( 'Sending request to API on endpoint ' . PAYNT_API_ENDPOINT_AUTHONLY . '...' . PHP_EOL, $this->log_level );

        $result = $api->GetObject( PAYNT_API_ENDPOINT_AUTHONLY, $auth_guid, $auth_token );

        if ( $result ) {
            if ( $result['response'] && $result['response']['code'] && (
                    $result['response']['code'] === 201
                    || $result['response']['code'] === 200
                )
            ) {
                $response_data = \json_decode( $result['body'] );
                if ( $response_data ) {
                    if ( $response_data['batchStatus'] && $response_data['wasProcessed'] ) {
                        $is_active = true;
                    }
                }
            } else {
                WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
            }
        } else {
            WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
        }

        WC_Util::logMessage( 'Sending request to API on endpoint ' . PAYNT_API_ENDPOINT_CAPTURE . '...' . PHP_EOL, $this->log_level );

        $result = $api->GetObject( PAYNT_API_ENDPOINT_CAPTURE, $auth_guid, $auth_token );

        if ( $result ) {
            if ( $result['response'] && $result['response']['code'] && (
                    $result['response']['code'] === 201
                    || $result['response']['code'] === 200
                )
            ) {
                $response_data = \json_decode( $result['body'] );
                if ( $response_data ) {
                    if ( $response_data['authOnlyGuid'] && $response_data['authOnlyGuid'] === $auth_guid
                         && $response_data['wasProcessed']
                    ) {
                        $is_active = false;
                    }
                }
            } else {
                WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
            }
        } else {
            WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
        }

        return $is_active;
    }

    public function admin_options() {
        $path = dirname( plugin_dir_path( __FILE__ ) );
        include $path . '/templates/admin-options.php';
    }

    protected function getSetting( $setting ) {
        $value = null;
        if ( isset( $this->settings[ $setting ] ) ) {
            $value = $this->settings[ $setting ];
        }

        return $value;
    }

    public function displayErrorMessage( $message ) {
        global $woocommerce;

        $message = __( (string) $message, 'choicepaynt_gateway' );

        if ( function_exists( 'wc_add_notice' ) ) {
            wc_add_notice( $message, 'error' );
        } elseif ( isset( $woocommerce ) && property_exists( $woocommerce, 'add_error' ) ) {
            $woocommerce->add_error( $message );
        }
    }
}
