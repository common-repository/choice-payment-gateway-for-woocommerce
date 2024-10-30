<?php

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
class WC_Gateway_Choice_BankClearing extends WC_Payment_Gateway {
	use ChoicePayment\GatewayInfo;

	private static $_instance = null;

	private $_access_token = '';

	public function __construct() {
		// includes

		// properties
		$this->id           = 'choicepaynt_ach';
		$this->method_title = __( 'Choice Payment ACH', 'choicepaynt_gateway' );
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

	public static function getInstance(): ?WC_Gateway_Choice_BankClearing {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function is_available(): bool {
		return $this->enabled == 'yes';
	}

	public function initFormFields() {
		$path              = dirname( plugin_dir_path( __FILE__ ) );
		$this->form_fields = include $path . '/etc/choicepaynt_ach-options.php';
	}

	public function payment_fields() {
		$path = dirname( plugin_dir_path( __FILE__ ) );
		include $path . '/templates/payment-fields-ach.php';
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		WC_Util::cleanSubmittedFormValues();

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

		$name_on_account = $billing_first_name . ' ' . $billing_last_name;

		$routing_number = isset( $_POST['woo-choice-payment_ach_routing'] ) ? WC_Util::cleanValue( $_POST['woo-choice-payment_ach_routing'] ) : '';
		$account_number = isset( $_POST['woo-choice-payment_ach_account'] ) ? WC_Util::cleanValue( $_POST['woo-choice-payment_ach_account'] ) : '';

		$orderTotal = wc_format_decimal(
			WC_Util::getWooCommerceData( $order, 'get_total', 'order_total' ),
			2
		);

		$api = new HttpApi( $this->sandbox_mode, $this->log_level );

		$bank_account = array(
			'RoutingNumber' => $routing_number,
			'AccountNumber' => $account_number,
			'NameOnAccount' => $name_on_account,
		);

		$data = array(
			'DeviceGuid'     => $this->device_ach_guid,
			'Amount'         => $orderTotal,
			'CustomData'     => "Order #" . $order_id,
			'CustomerId'     => $billing_email,
			'SendReceipt'    => false,
			'BankAccount'    => $bank_account,
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

			$payment_succeeded = $this->process_payment_result( $result, $order, $order_id );
		}

		if ( $result ) {

			if ( $result['response'] && $result['response']['code'] &&
                 (
                     $result['response']['code'] === 201 ||
                     $result['response']['code'] === 200
                 )
            ) {

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

		return array(
			'result'   => 'fail',
			'redirect' => '',
		);
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		// Get the sale guid
		$payment_guid = get_post_meta( $order_id, '_woochoice_payment_ach_guid', true );

		if ( ! $payment_guid ) {
			return false;
		}

		$api = new HttpApi( $this->sandbox_mode, $this->log_level );

		try {

			$data = array(
				'DeviceGuid'   => $this->device_ach_guid,
				'ClearingGuid' => $payment_guid,
			);

			WC_Util::logMessage(
				'Processing return using endpoint: ' . PAYNT_API_ENDPOINT_BANKCLEARINGRETURNS . '.' . PHP_EOL
				. 'data: ' . PHP_EOL . wc_print_r( $data, true ) . PHP_EOL,
				$this->log_level
			);

			$auth_token = $this->getInstanceToken( $this, false );
			$result     = $api->ProcessPayment( PAYNT_API_ENDPOINT_BANKCLEARINGRETURNS, $data, $auth_token );

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
					$result2 = $api->ProcessPayment( PAYNT_API_ENDPOINT_BANKCLEARINGVOIDS, $data, $auth_token );

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
						$payment_status = $response_data->status;

						if ( stripos( $payment_status, PAYNT_API_STATUS_APPROVED ) !== false ) {

							$order->add_order_note(
								__(
									'Choice ACH payment voided.',
									'choicepaynt_gateway'
								) . ' (Transaction ID: ' . $response_data->relatedClearing->refNumber . ')'
							);

							// Update order
							// update order post_meta
							$prefix = '_woochoice_ach_void';
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
					$refund_amount  = $response_data->relatedClearing->amount;
					$ref_number     = $response_data->relatedClearing->refNumber;

					if ( stripos( $payment_status, PAYNT_API_STATUS_APPROVED ) !== false ) {

						$order->add_order_note(
							__(
								'Choice ACH refunded $' . $refund_amount,
								'choicepaynt_gateway'
							) . ' (Transaction ID: ' . $ref_number . ')'
						);

						// update order post_meta
						$prefix = '_woochoice_ach_refund';
						update_post_meta( $order_id, $prefix . '_guid', $refund_guid );
						update_post_meta( $order_id, $prefix . '_amount', $refund_amount );
						update_post_meta( $order_id, $prefix . '_reason', $reason );

						// Refund succeeded
						return true;
					} else {
						$order->add_order_note(
							__(
								'Choice ACH refund failed with status of "' . $payment_status . '." Login to Choice WebPortal to manually refund payment.',
								'choicepaynt_gateway'
							) . ' (Transaction ID: ' . $ref_number . ')'
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

	protected function getSetting( $setting ) {
		$value = null;
		if ( isset( $this->settings[ $setting ] ) ) {
			$value = $this->settings[ $setting ];
		}

		return $value;
	}

	protected function process_payment_result( $result, $order, $order_id ): bool {
		if ( ! $result ) {
			return false;
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
                $result['response']['code'] === 201 ||
                $result['response']['code'] === 200
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
			$payment_ref_number         = $response_data->refNumber;

			if ( $order
				 && ( $payment_status === PAYNT_API_STATUS_APPROVED
					  || stripos( $payment_status, 'Transaction - Approved' ) !== false )
			) {

				$order->add_order_note(
					__(
						'Choice ACH payment processed $' . $payment_amount,
						'choicepaynt_gateway'
					) . ' (Transaction ID: ' . $response_data->refNumber . ')'
				);

				// update order post_meta
				$prefix = '_woochoice_payment_ach';
				update_post_meta( $order_id, $prefix . '_action', 'ACH' );
				update_post_meta( $order_id, $prefix . '_guid', $payment_guid );
				update_post_meta( $order_id, $prefix . '_amount', $payment_amount );
				update_post_meta( $order_id, $prefix . '_status', $payment_status );
				update_post_meta( $order_id, $prefix . '_processor_status', $payment_processor_status );
				update_post_meta( $order_id, $prefix . '_processor_response', $payment_processor_response );
				update_post_meta( $order_id, $prefix . '_was_processed', $payment_was_processed );
				update_post_meta( $order_id, $prefix . '_ref_number', $payment_ref_number );

				$order->payment_complete( $response_data->refNumber );

                return true;
			}
		} else {
			WC_Util::logMessage( 'Invalid response from API.', $this->log_level );
		}

		return false;
	}
}
