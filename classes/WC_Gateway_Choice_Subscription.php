<?php

use ChoicePayment\HttpApi;
use ChoicePayment\WC_Util;

class WC_Gateway_Choice_Subscription extends WC_Payment_Gateway {
    use ChoicePayment\GatewayInfo;

    private static $_instance = null;
    private $_access_token = '';

    public function __construct() {
        // includes

        // properties
        $this->id           = 'choicepaynt_subscription';
        $this->method_title = __( 'Choice Payment Subscription', 'choicepaynt_gateway' );
        $this->has_fields   = true;
        $this->initFormFields();
        $this->init_settings();
        $this->title              = $this->getSetting( 'title' );
        $this->description        = $this->getSetting( 'description' );
        $this->enabled            = $this->getSetting( 'enabled' );
        $this->api_key            = $this->getSetting( 'api_key' );
        $this->debug_mode         = $this->getSetting( 'debug_mode' );
        $this->device_ach_guid    = $this->getSetting( 'device_ach_guid' );
        $this->log_level          = $this->getSetting( 'log_level' );
        $this->merchant_name      = $this->getSetting( 'merchant_name' );
        $this->method_description = 'Process ACH/Bank Clearing via Choice Payment gateway.';
        $this->password           = $this->getSetting( 'password' );
        $this->sandbox_mode       = $this->getSetting( 'sandbox_mode' ) === 'yes';
        $this->supports           = array( 'products', 'refunds' );
        $this->username           = $this->getSetting( 'username' );

        // actions
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array(
                $this,
                'process_admin_options',
            )
        );

    }
}
