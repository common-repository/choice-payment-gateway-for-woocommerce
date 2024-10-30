<?php

namespace ChoicePayment;

use ChoicePayment\BankInfo;
use Defuse\Crypto\Crypto;

class WC_Util {

    const CHOICE_CIPHER = "aes-256-ctr";
    const CHOICE_CRYPTO_VERSION = "2";

    /**
     * Wraps WooCommerce data access APIs to ensure proper data is retrieved
     *
     * @param object $object Data object to test
     * @param string $method Method name for 3.0 API
     * @param string $property Property name for 2.6 API
     *
     * @return mixed
     */
    public static function getWooCommerceData( $object, $method, $property ) {
        if ( ! $object || ! is_object( $object ) ) {
            return null;
        }

        if ( method_exists( $object, $method ) ) {
            return $object->{$method}();
        }

        if ( property_exists( $object, $property ) || isset( $object->{$property} ) ) {
            return $object->{$property};
        }

        return null;
    }

    public static function cleanSubmittedFormValues() {
        $_POST = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
        $_GET  = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );
    }

    public static function cleanValue( $value ) {
        if ( function_exists( 'wc_clean' ) ) {
            return wc_clean( $value );
        } elseif ( function_exists( 'woocommerce_clean' ) ) {
            return woocommerce_clean( $value );
        } elseif ( function_exists( 'sanitize_text_field' ) ) {
            return sanitize_text_field( $value );
        }

        return filter_var( $value, FILTER_SANITIZE_STRING );
    }

    public static function logMessage( $message, $log_level = WC_CHOICEPAYMENT_LOG_LEVEL_NONE ) {
        $logger = null;

        if ( $log_level === WC_CHOICEPAYMENT_LOG_LEVEL_NONE ) {
            return;
        }

        if ( function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
        }

        if ( ! $logger && class_exists( 'WC_Logger' ) ) {
            $logger = new WC_Logger();
        }

        $context = array( 'source' => WC_CHOICEPAYMENT_LOG_CONTEXT );

        // WC_LOG error levels: emergency|alert|critical|error|warning|notice|info|debug.

        if ( $logger ) {

            switch ( $log_level ) {
                case WC_CHOICEPAYMENT_LOG_LEVEL_DEBUG:
                    $level = 'debug';
                    break;
                case WC_CHOICEPAYMENT_LOG_LEVEL_ERROR:
                    $level = 'error';
                    break;
                default:
                    $level = 'info';
                    break;
            }
            $logger->log( $level, $message, $context );
        }

    }

    public static function encodeAchToken( $bank_account ) {
        return WC_Util::encrypt( $bank_account->AccountName.'|'.$bank_account->RoutingNumber.'|'.$bank_account->AccountNumber );
    }

    public static function decodeAchToken( $token ): BankInfo {
        $data = explode( '|', WC_Util::decrypt( $token ) );

        return new BankInfo( $data[0], $data[1], $data[2] );
    }

    public static function encrypt( $plaintext ) {
        $key = WC_CHOICEPAYMENT_KEY;

        $ciphertext = null;

        try {
            $options = 0;
            $iv      = hex2bin( md5( microtime() . rand() ) );
            if ( in_array( self::CHOICE_CIPHER, openssl_get_cipher_methods() ) ) {
                $ciphertext = openssl_encrypt( $plaintext, self::CHOICE_CIPHER, $key, $options, $iv );
            } else {
                return $plaintext;
            }
        } catch ( \Exception $ex ) {
            WC_Util::logMessage( $ex->getMessage() );
        }

        $version = "^CHOICE#V" . self::CHOICE_CRYPTO_VERSION . "!";

        return base64_encode( $version . $iv . $ciphertext );

    }

    public static function decrypt( $encoded ) {
        $key = WC_CHOICEPAYMENT_KEY;

        $original_plaintext = null;

        try {
            $options = 0;
            $decoded = base64_decode( $encoded );
            $version = substr( $decoded, 0, 11 );

            // Handle migration of previous crypto scheme
            if ( $version !== "^CHOICE#V" . self::CHOICE_CRYPTO_VERSION . "!" ) {
                return self::decrypt_deprecated( $encoded );
            }

            $iv   = substr( $decoded, 11, 16 ); // skip the first 11 characters of the version number
            $data = substr( $decoded, 27 ); // data starts after 11 characters of version plus 16 characters iv.
            if ( in_array( self::CHOICE_CIPHER, openssl_get_cipher_methods() ) ) {
                $original_plaintext = openssl_decrypt( $data, self::CHOICE_CIPHER, $key, $options, $iv );
            } else {
                return $encoded;
            }
        } catch ( \Exception $ex ) {
            WC_Util::logMessage( $ex->getMessage() );
        }

        return $original_plaintext;
    }

    public static function encrypt_deprecated( $plaintext ) {
        $key = WC_CHOICEPAYMENT_KEY;

        $ciphertext = null;

        try {
            $cipher  = "aes-256-ctr";
            $options = 0;
            $iv      = '1234567891011121';
            if ( in_array( $cipher, openssl_get_cipher_methods() ) ) {
                $ciphertext = openssl_encrypt( $plaintext, $cipher, $key, $options, $iv );
            } else {
                return $plaintext;
            }
        } catch ( \Exception $ex ) {
            WC_Util::logMessage( $ex->getMessage() );
        }

        return base64_encode( $ciphertext );

    }

    public static function decrypt_deprecated( $encoded ) {
        $key = WC_CHOICEPAYMENT_KEY;

        $original_plaintext = null;

        try {
            $cipher  = "aes-256-ctr";
            $options = 0;
            $iv      = '1234567891011121';
            $c       = base64_decode( $encoded );
            if ( in_array( $cipher, openssl_get_cipher_methods() ) ) {
                $original_plaintext = openssl_decrypt( $c, $cipher, $key, $options, $iv );
            } else {
                return $encoded;
            }
        } catch ( \Exception $ex ) {
            WC_Util::logMessage( $ex->getMessage() );
        }

        return $original_plaintext;
    }
}
