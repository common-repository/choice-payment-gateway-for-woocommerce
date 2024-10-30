<?php

/*
 * Define payment gateway options.
 *
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'enabled'                => array(
		'title'       => __( 'Enable/Disable', 'choicepaynt_gateway' ),
		'label'       => __( 'Enable Choice Payment', 'choicepaynt_gateway' ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
	),
	'sandbox_mode'           => array(
		'title'       => __( 'Sandbox Mode', 'choicepaynt_gateway' ),
		'label'       => __( 'Enable Sandbox Mode', 'choicepaynt_gateway' ),
		'type'        => 'checkbox',
		'description' => 'Enable this to use the sandbox gateway environment for testing purposes.  Disable this when you are ready to go live.',
		'default'     => 'yes',
	),
	'title'                  => array(
		'title'       => __( 'Title', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => __( 'This controls the title the user sees during checkout.', 'choicepaynt_gateway' ),
		'default'     => __( 'Credit Card', 'choicepaynt_gateway' ),
	),
	'description'            => array(
		'title'       => __( 'Description', 'choicepaynt_gateway' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description the user sees during checkout.', 'choicepaynt_gateway' ),
		'default'     => 'Pay with your credit card via Choice Payment Gateway.',
	),
	'merchant_name'          => array(
		'title'       => __( 'Merchant name', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => __( 'The name of the merchant.', 'choicepaynt_gateway' ),
		'default'     => '',
	),
	'username'               => array(
		'title'       => __( 'Username', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => __( 'Username with access to payment gateway.  Contact your gateway administrator if you do not have one.', 'choicepaynt_gateway' ),
		'default'     => '',
	),
	'password'               => array(
		'title'       => __( 'Password', 'choicepaynt_gateway' ),
		'type'        => 'password',
		'description' => __( 'Password used to access the payment gateway.  Contact your gateway administrator if you need assistance.', 'choicepaynt_gateway' ),
		'default'     => '',
	),
	'device_cc_guid'         => array(
		'title'       => __( 'Device Credit Card GUID', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => __( 'Device ID use for credit card transactions.', 'choicepaynt_gateway' ),
		'default'     => '',
	),
	'payment_action'         => array(
		'title'       => __( 'Payment Action', 'choicepaynt_gateway' ),
		'type'        => 'select',
		'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only and capture when the order ships.', 'choicepaynt_gateway' ),
		'default'     => 'sale',
		'desc_tip'    => true,
		'options'     => array(
			'sale'          => __( 'Capture', 'choicepaynt_gateway' ),
			'authorization' => __( 'Authorize', 'choicepaynt_gateway' ),
		),
	),
	'service_fee'            => array(
		'title'       => __( 'Service Fee', 'choicepaynt_gateway' ),
		'label'       => __( 'Charge a Service Fee', 'choicepaynt_gateway' ),
		'type'        => 'checkbox',
		'description' => 'Select this option if you want to charge a fee when credit card is chosen as payment method.  This fee will automatically be discounted if ACH is available and chosen as the payment method.',
		'default'     => 'no',
	),
	'service_fee_percent'    => array(
		'title'       => __( 'Service Fee Percent', 'choicepaynt_gateway' ),
		'type'        => 'decimal',
		'default'     => '3.99',
		'description' => 'The percentage to charge.',
	),
	'service_fee_tax_type'   => array(
		'title'       => __( 'Service Fee Tax Type', 'choicepaynt_gateway' ),
		'type'        => 'select',
		'description' => __( 'Is service fee taxable?', 'choicepaynt_gateway' ),
		'default'     => 'no-tax',
		'desc_tip'    => true,
		'options'     => array(
			'no-tax'  => __( 'Non-taxable', 'choicepaynt_gateway' ),
			'taxable' => __( 'Taxable', 'choicepaynt_gateway' ),
		),
	),
	'service_fee_tax_class'  => array(
		'title'       => __( 'Service Fee Tax Class', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => 'The tax class for payment service fees.',
		'default'     => '',
	),
	'service_fee_disclaimer' => array(
		'title'       => __( 'Cash Discount Disclaimer', 'choicepaynt_gateway' ),
		'type'        => 'textarea',
		'description' => __( 'Displayed to customer on checkout and invoice. This is required when applying a Service Fee to the order.', 'choicepaynt_gateway' ),
		'default'     => '* A {$discount_rate}% customer service charge is applied to all sales. As an incentive for customers, we provide a discount to customers who pay with cash or through ACH by giving a {$discount_rate}% immediate discount on the service charge.',
	),
	'log_level'              => array(
		'title'       => __( 'Logging level', 'choicepaynt_gateway' ),
		'type'        => 'select',
		'description' => __( 'Select log level.', 'choicepaynt_gateway' ),
		'default'     => '0',
		'desc_tip'    => true,
		'options'     => array(
			'0' => __( 'None', 'choicepaynt_gateway' ),
			'1' => __( 'Informational', 'choicepaynt_gateway' ),
			'2' => __( 'Error', 'choicepaynt_gateway' ),
			'3' => __( 'Debug', 'choicepaynt_gateway' ),
			'4' => __( 'Verbose', 'choicepaynt_gateway' ),
		),
	),
);
