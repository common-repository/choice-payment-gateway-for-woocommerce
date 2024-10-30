<?php

namespace ChoicePayment;

class HttpApi {
	private $_logging_level;
	private $_api_base_url;
	private $_sandbox_mode;

	/**
	 * @param bool   $sandbox_mode
	 * @param int    $logging_level
	 * @param string $api_base_url
	 */
	public function __construct(
		bool $sandbox_mode = false, int $logging_level = WC_CHOICEPAYMENT_LOG_LEVEL_INFO,
		string $api_base_url = PAYNT_API_SANDBOX_URL
	) {
		$this->_api_base_url  = $api_base_url;
		$this->_sandbox_mode  = $sandbox_mode;
		$this->_logging_level = $logging_level;

		if ( ! $this->_sandbox_mode ) {
			$this->_api_base_url = PAYNT_API_PRODUCTION_URL;
		}
	}

	/***
	 * Get an authorization token from PAYNT API.
	 *
	 * This must be called and the return token used for all subsequent API calls.
	 *
	 * @param $username
	 * @param $password
	 */
	public function GetAuthroizationToken( $username, $password ) {
		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);

		$data = http_build_query(
			array(
				'grant_type' => 'password',
				'username'   => $username,
				'password'   => $password,
			)
		);

		$options = array(
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => $data,
		);

		$result = $this->CreateHttpRequest( PAYNT_API_ENDPOINT_TOKEN, $options );
		$token  = null;
		if ( $result and array_key_exists( 'body', $result ) ) {
			$data = \json_decode( $result['body'] );
			if ( $data ) {
				$token = $data->access_token;
			}
		}

		return self::formatResultAsJson( array( 'access_token' => $token ) );
	}

	public function ProcessPayment( string $endpoint, array $data, $auth_token ) {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => "Bearer {$auth_token}",
		);

		$data = \json_encode( $data );

		$options = array(
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => $data,
		);

		return $this->CreateHttpRequest( $endpoint, $options );
	}

	public function GetObject( string $endpoint, string $guid, $auth_token ) {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => "Bearer {$auth_token}",
		);

		$options = array(
			'method'  => 'GET',
			'headers' => $headers,
		);

		$params = array(
			'guid' => $guid,
		);

		return $this->CreateHttpRequest( $endpoint, $options, $params );
	}

	private function CreateHttpRequest( string $endpoint, array $options, array $query_params = null ) {
		// set the timeout
		$options['timeout'] = 20;
		if ( $query_params ) {
			$url = $this->GetApiUrl() . $endpoint . '?' . http_build_query( $query_params );
		} else {
			$url = $this->GetApiUrl() . $endpoint;
		}

		try {
			WC_Util::logMessage(
				'API request: ' . PHP_EOL . ' Endpoint: ' . $endpoint . PHP_EOL
				. 'Data: ' . PHP_EOL . wc_print_r( $this->sanitize_log_message( $options ), true ),
				$this->_logging_level
			);

			$result = wp_remote_request( $url, $options );

			WC_Util::logMessage( 'API response: ' . PHP_EOL . wc_print_r( $result, true ), $this->_logging_level );
		} catch ( \Exception $e ) {
			if ( $this->_logging_level >= WC_CHOICEPAYMENT_LOG_LEVEL_DEBUG ) {
				WC_Util::logMessage( 'Error while communicating with API.' . PHP_EOL . wc_print_r( $e, true ), $this->_logging_level );
			} else {
				WC_Util::logMessage( 'Error while communicating with API.' . PHP_EOL . $e->getMessage(), $this->_logging_level );
			}
		}

		return $result;
	}

	public function GetApiUrl(): string {
		if ( $this->_api_base_url ) {
			return $this->_api_base_url; // If url has been overridden then return it.
		}
		if ( $this->_sandbox_mode ) {
			return PAYNT_API_SANDBOX_URL;
		}

		return PAYNT_API_PRODUCTION_URL;
	}

	public static function formatResultAsJson( $data ) {
		return json_encode( array( 'data' => $data ) );
	}

	private function sanitize_log_message( $options ) {
		$sanitized_options = $options;
		if ( $sanitized_options && is_array( $sanitized_options ) ) {
			if ( array_key_exists( 'body', $sanitized_options ) ) {
				if ( stripos( $sanitized_options['body'], 'password' ) ) {
					$sanitized_options['body'] = '';
				}
			}
			if ( array_key_exists( 'headers', $sanitized_options ) && is_array( $sanitized_options['headers'] ) ) {
				if ( array_key_exists( 'UserAuthorization', $sanitized_options['headers'] ) ) {
					$sanitized_options['headers']['UserAuthorization'] = '';
				}
				if ( array_key_exists( 'Authorization', $sanitized_options['headers'] ) ) {
					$sanitized_options['headers']['Authorization'] = '';
				}
			}
		}

		return $sanitized_options;
	}
}
