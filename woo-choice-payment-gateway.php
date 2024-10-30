<?php
/*
Plugin Name: Choice Payment Gateway for WooCommerce
Plugin URI: https://www.versacomputer.com/choice-payment-gateway-for-woocommerce/
Description: Choice Payment gateway for WooCommerce.
Version: 2.1.2
WC tested up to: 6.1.1
Author: Choice Merchant Solutions
*/

if ( ! function_exists( 'cpgfw_fs' ) ) {
	// Create a helper function for easy SDK access.
	function cpgfw_fs() {
		global $cpgfw_fs;

		if ( ! isset( $cpgfw_fs ) ) {
			// Include Freemius SDK.
			include_once dirname( __FILE__ ) . '/freemius/start.php';

			$cpgfw_fs = fs_dynamic_init(
				array(
					'id'             => '9257',
					'slug'           => 'choice-payment-gateway-for-woocommerce',
					'type'           => 'plugin',
					'public_key'     => 'pk_0099b870764f9632c0cf52f607918',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => false,
					'menu'           => array(
						'first-path' => 'plugins.php',
						'account'    => false,
						'support'    => false,
					),
				)
			);
		}

		return $cpgfw_fs;
	}

	// Init Freemius.
	cpgfw_fs();
	// Signal that SDK was initiated.
	do_action( 'cpgfw_fs_loaded' );
}

const PAYNT_API_SANDBOX_URL                  = 'https://sandbox.choice.dev/api/v1/';
const PAYNT_API_PRODUCTION_URL               = 'https://payments.choice.dev/api/v1/';
const PAYNT_API_ENDPOINT_AUTHONLY            = 'AuthOnlys';
const PAYNT_API_ENDPOINT_BANKCLEARINGS       = 'BankClearings';
const PAYNT_API_ENDPOINT_BANKCLEARINGVOIDS   = 'BankClearingVoids';
const PAYNT_API_ENDPOINT_BANKCLEARINGRETURNS = 'BankClearingReturns';
const PAYNT_API_ENDPOINT_BANKCLEARINGVERIFY  = 'BankClearings/Verify';
const PAYNT_API_ENDPOINT_CAPTURE             = 'Captures';
const PAYNT_API_ENDPOINT_RETURN              = 'returns';
const PAYNT_API_ENDPOINT_SALE                = 'sales';
const PAYNT_API_ENDPOINT_TOKEN               = 'token';
const PAYNT_API_ENDPOINT_TOKENIZATION        = 'tokenization';
const PAYNT_API_ENDPOINT_TIMEOUT_REVERSAL    = 'timeoutreversal';
const PAYNT_API_ENDPOINT_VERIFY              = 'Verify';
const PAYNT_API_ENDPOINT_VOID                = 'void';
const PAYNT_API_ENDPOINT_HOSTEDPAYMENTPAGE   = 'HostedPaymentPageRequests';
const PAYNT_API_STATUS_APPROVED              = 'Transaction - Approved';
const PAYNT_API_STATUS_APPROVED_WARNING      = 'Transaction - Approved - Warning';
const PAYNT_API_STATUS_DECLINED              = 'Transaction - Declined';
const PAYNT_API_STATUS_CREATED_LOCAL         = 'Transaction - Created - Local';
const PAYNT_API_STATUS_CREATED_ERROR         = 'Transaction - Created - Error: Processor not reached';
const PAYNT_API_STATUS_PROCESSOR_ERROR       = 'Transaction - Processor Error';
const PAYNT_API_PROCESS_MAX_ATTEMPTS         = 1;

const WC_CHOICEPAYMENT_LOG_LEVEL_NONE      = 0;
const WC_CHOICEPAYMENT_LOG_LEVEL_INFO      = 1;
const WC_CHOICEPAYMENT_LOG_LEVEL_ERROR     = 2;
const WC_CHOICEPAYMENT_LOG_LEVEL_DEBUG     = 3;
const WC_CHOICEPAYMENT_LOG_LEVEL_VERBOSE   = 4;
const WC_CHOICEPAYMENT_LOG_CONTEXT         = 'choice-payment-gateway';
const WC_CHOICEPAYMENT_SERVICE_FEE_LABEL   = 'Service Fee*';
const WC_CHOICEPAYMENT_CASH_DISCOUNT_LABEL = 'Cash Discount';
const WC_CHOICEPAYMENT_PLUGIN_VERSION      = '2.1.2';

if (!defined('WC_CHOICEPAYMENT_KEY')) {
    define('WC_CHOICEPAYMENT_KEY', AUTH_KEY );
}

require_once 'classes/trait-GatewayInfo.php';
require_once 'vendor/autoload.php';

class WooCommerceChoicePayntGateway {

	use ChoicePayment\GatewayInfo;

	const CHOICE_PAYNT_GATEWAY_CLASS     = 'WC_Gateway_Choice_Paynt';
	const CHOICE_PAYNT_ACH_GATEWAY_CLASS = 'WC_Gateway_Choice_BankClearing';

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_action( 'woocommerce_load', array( $this, 'activate' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'loadScripts' ) );

		// handle service fees
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'addFees' ) );

		// checkout scripts
		add_action( 'woocommerce_after_checkout_form', array( $this, 'refresh_checkout_on_payment_methods_change' ) );
		add_action( 'woocommerce_review_order_before_submit', array( $this, 'display_cash_discount_notice' ) );

		// email filters
		add_action(
			'woocommerce_email_after_order_table',
			array(
				$this,
				'display_cash_discount_notice_email',
			),
			999,
			4
		);
		add_action(
			'woocommerce_order_details_after_order_table',
			array(
				$this,
				'display_cash_discount_orderdetails',
			)
		);
	}

	/***
	 * Initialize plugin
	 */
	public function init() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		load_plugin_textdomain( 'choicepaynt_gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		$this->loadClasses();
		$this->registerAjax();

		$woochoice = call_user_func( array( self::CHOICE_PAYNT_GATEWAY_CLASS, 'getInstance' ) );

		// filters
		add_filter( 'woocommerce_payment_gateways', array( $this, 'addGateways' ) );

		// order actions
		add_action( 'woocommerce_order_actions', array( $woochoice, 'addDelayedCaptureOrderAction' ) );
		add_action( 'woocommerce_order_action_' . $woochoice->id . '_capture', array( $woochoice, 'process_capture' ) );
	}

	/***
	 * Handle any actions that needs to occur when plugin is activated.
	 */
	public function activate() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		$this->loadClasses();

		add_filter( 'woocommerce_payment_gateways', array( $this, 'addGateways' ) );
	}

	/**
	 * Adds payment options to WooCommerce to be enabled by store admin.
	 *
	 * @param array $methods
	 *
	 * @return array
	 */
	public function addGateways( $methods ) {

        if (class_exists('WC_Subscriptions_Order')) {
            $klass = self::CHOICE_PAYNT_GATEWAY_CLASS . '_Subscriptions';
            if (!function_exists('wcs_create_renewal_order')) {
//                $klass .= '_Deprecated';
            }
            $methods[] = $klass;
            $methods[] = self::CHOICE_PAYNT_ACH_GATEWAY_CLASS . '_Subscriptions';
        } else {
            $methods[] = self::CHOICE_PAYNT_GATEWAY_CLASS;
            $methods[] = self::CHOICE_PAYNT_ACH_GATEWAY_CLASS;
        }

		return $methods;
	}

	public function loadScripts() {
		if ( ! is_account_page() ) {
			return;
		}
		wp_enqueue_style( 'woo_choice_payment', plugins_url( 'assets/css/woo-choice-payment.css', __FILE__ ), array(), WC_CHOICEPAYMENT_PLUGIN_VERSION );
	}

	public function registerAjax() {
		$gateway = WC_Gateway_Choice_Paynt::getInstance();

		// ajax
		add_action( 'wp_ajax_get_temp_token', array( $gateway, 'get_temp_token' ) );
		add_action( 'wp_ajax_nopriv_get_temp_token', array( $gateway, 'get_temp_token' ) );
	}

	protected function loadClasses() {
		include_once 'classes/trait-GatewayInfo.php';
		include_once 'classes/class-wc-gateway-choice-paynt.php';
		include_once 'classes/class-wc-gateway-choice-subscription.php';
		include_once 'classes/WC_Gateway_Choice_BankClearing.php';
		include_once 'classes/WC_Gateway_Choice_BankClearing_Subscriptions.php';
		include_once 'classes/HttpApi.php';
		include_once 'classes/BankInfo.php';
		include_once 'classes/Customer.php';
		include_once 'classes/Payment.php';
		include_once 'classes/WC_Util.php';
	}

	public function addFees( $cart ) {
		if ( ! $cart ) {
			return;
		}

		// Get available gateways
		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		$selected_payment_method_id = WC()->session->get( 'chosen_payment_method' );

		foreach ( $gateways as $gateway ) {
			if ( $gateway->id === 'choicepaynt' ) {
				if ( $gateway->settings && wc_string_to_bool( $gateway->settings['enabled'] )
					 && $gateway->service_fee
				) {

					// Calculate the service fee
					if ( $gateway->service_fee_percent ) {
						$fee = $this->calculateServiceFee( $cart->get_totals(), $gateway->service_fee_percent );

						$taxable   = $gateway->service_fee_tax_type === 'taxable';
						$tax_class = $gateway->service_fee_tax_class;

						$cart->add_fee( WC_CHOICEPAYMENT_SERVICE_FEE_LABEL, wc_format_decimal( $fee, 2 ), $taxable, $tax_class );
					}
				}
			}

			if ( $gateway->id === 'choicepaynt_ach' ) {

				// Check if credit card payment method is enabled and configured for service fee
				$cc_gateway = $gateways['choicepaynt'];

				if ( $cc_gateway && $cc_gateway->settings && wc_string_to_bool( $cc_gateway->settings['enabled'] )
					 && $cc_gateway->service_fee
				) {

					// Apply cash discount if paying by ACH and service fee is included in the product price
					if ( $selected_payment_method_id === $gateway->id ) {

						// Calculate the service fee
						if ( $cc_gateway->service_fee_percent ) {
							$fee = $this->calculateServiceFee( $cart->get_totals(), $cc_gateway->service_fee_percent );

							$taxable   = $cc_gateway->service_fee_tax_type === 'taxable';
							$tax_class = $cc_gateway->service_fee_tax_class;

							// Fee should be negative
							$fee = $fee * - 1;

							$cart->add_fee( WC_CHOICEPAYMENT_CASH_DISCOUNT_LABEL, wc_format_decimal( $fee, 2 ), $taxable, $tax_class );

						}
					} else {
						$cart->add_fee( WC_CHOICEPAYMENT_CASH_DISCOUNT_LABEL, 0.00 );
					}
				}
			}
		}
	}

	function refresh_checkout_on_payment_methods_change() {
		wc_enqueue_js(
			"
           $( 'form.checkout' ).on( 'change', 'input[name^=\'payment_method\']', function() {
               $('body').trigger('update_checkout');
            });
       "
		);
	}

	function display_cash_discount_notice() {
		// Get available gateways
		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		// Check if credit card payment method is enabled and configured for service fee
		$cc_gateway = $gateways['choicepaynt'];

		if ( $cc_gateway && $cc_gateway->settings && wc_string_to_bool( $cc_gateway->settings['enabled'] )
			 && $cc_gateway->service_fee
		) {

			echo $this->formatCashDiscountNotice( $cc_gateway, $cc_gateway->service_fee_disclaimer, true );

		}
	}

	function display_cash_discount_notice_email( $order, $sent_to_admin, $plain_text, $email ) {
		// Get available gateways
		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		// Check if credit card payment method is enabled and configured for service fee
		$cc_gateway = $gateways['choicepaynt'];

		if ( $cc_gateway && $cc_gateway->settings && wc_string_to_bool( $cc_gateway->settings['enabled'] )
			 && $cc_gateway->service_fee
		) {
			if ( $plain_text ) {
				$this->formatCashDiscountNotice( $cc_gateway, $cc_gateway->service_fee_disclaimer, false );
			} else {
				$this->formatCashDiscountNotice( $cc_gateway, $cc_gateway->service_fee_disclaimer, true );
			}
		}
	}

	function display_cash_discount_orderdetails( $order ) {
		// Get available gateways
		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		// Check if credit card payment method is enabled and configured for service fee
		$cc_gateway = $gateways['choicepaynt'];

		if ( $cc_gateway && $cc_gateway->settings && wc_string_to_bool( $cc_gateway->settings['enabled'] )
			 && $cc_gateway->service_fee
		) {
			$this->formatCashDiscountNotice( $cc_gateway, $cc_gateway->service_fee_disclaimer, true );
		}
	}

	private function formatCashDiscountNotice( $gateway, $message, $is_html = false ) {
		$discount_rate = $gateway->service_fee_percent;
		$message       = str_replace( '{$discount_rate}', $discount_rate, $message );

		if ( ! $is_html ) {
			return $message;
		}
		?>

		<div class='container woocommerce-privacy-policy-text'>
			<div class='woocommerce-privacy-policy-text'>
				<p> <?php echo esc_html( $message ); ?> </p>
			</div>
		</div>

		<?php
	}
}

new WooCommerceChoicePayntGateway();
