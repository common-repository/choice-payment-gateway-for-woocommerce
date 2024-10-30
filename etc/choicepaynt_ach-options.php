<?php

/*
 * Define payment gateway options.
 *
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'enabled'         => array(
		'title'       => __( 'Enable/Disable', 'choicepaynt_gateway' ),
		'label'       => __( 'Enable Choice Payment ACH/Bank Clearing', 'choicepaynt_gateway' ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
	),
	'sandbox_mode'    => array(
		'title'       => __( 'Sandbox Mode', 'choicepaynt_gateway' ),
		'label'       => __( 'Enable Sandbox Mode', 'choicepaynt_gateway' ),
		'type'        => 'checkbox',
		'description' => 'Enable this to use the sandbox gateway environment for testing purposes.  Disable this when you are ready to go live.',
		'default'     => 'yes',
	),
	'title'           => array(
		'title'       => __( 'Title', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => __( 'This controls the title the user sees during checkout.', 'choicepaynt_gateway' ),
		'default'     => __( 'ACH/Bank Clearing', 'choicepaynt_gateway' ),
	),
	'description'     => array(
		'title'       => __( 'Description', 'choicepaynt_gateway' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description the user sees during checkout.', 'choicepaynt_gateway' ),
		'default'     => 'Pay with ACH via Choice Payment Gateway.',
	),
	'merchant_name'   => array(
		'title'       => __( 'Merchant name', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => __( 'The name of the merchant.', 'choicepaynt_gateway' ),
		'default'     => '',
	),
	'username'        => array(
		'title'       => __( 'Username', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => __( 'Username with access to payment gateway.  Contact your gateway administrator if you do not have one.', 'choicepaynt_gateway' ),
		'default'     => '',
	),
	'password'        => array(
		'title'       => __( 'Password', 'choicepaynt_gateway' ),
		'type'        => 'password',
		'description' => __( 'Password used to access the payment gateway.  Contact your gateway administrator if you need assistance.', 'choicepaynt_gateway' ),
		'default'     => '',
	),
	'device_ach_guid' => array(
		'title'       => __( 'Device ACH/Bank Clearing GUID', 'choicepaynt_gateway' ),
		'type'        => 'text',
		'description' => __( 'Device ID used for ACH/Bank Clearing transactions', 'choicepaynt_gateway' ),
		'default'     => '',
	),
	'log_level'       => array(
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
