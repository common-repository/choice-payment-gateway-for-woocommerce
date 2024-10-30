<?php

namespace ChoicePayment;

trait GatewayInfo {

	function getInstanceToken( $instance, $format_as_json = true ) {

		if ( ! $instance ) {
			return;
		}

		if ( ! $instance->_access_token ) {
			WC_Util::logMessage( 'Requesting token from api.', $instance->log_level );
			$api          = new HttpApi( $instance->sandbox_mode, $instance->log_level );
			$access_token = $api->GetAuthroizationToken( $instance->username, $instance->password );
		}

		$data = \json_decode( $access_token );

		if ( $data && $data->data ) {
			$instance->_access_token = $data->data->access_token;
		}

		if ( $format_as_json ) {
			return $access_token;
		}

		return $instance->_access_token;

	}

	function calculateServiceFee( $totals, $rate ) {
		$cart_total = ( (float) $totals['subtotal'] - (float) $totals['discount_total'] ) + (float) $totals['shipping_total'];

		return $cart_total * ( floatval( $rate / 100 ) );
	}

}
