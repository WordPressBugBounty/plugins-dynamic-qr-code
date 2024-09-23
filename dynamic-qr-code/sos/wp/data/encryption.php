<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait Encryption
{

    public function encrypt( $value ) {
        if ( trim($value) == '' ) {
            return '';
        }

        $method = 'aes-256-ctr';
        $iv_length = openssl_cipher_iv_length( $method );
        $iv = openssl_random_pseudo_bytes( $iv_length );

        $raw_value = openssl_encrypt( $value . SECURE_AUTH_SALT, $method, SECURE_AUTH_KEY, 0, $iv );
        if ( ! $raw_value ) {
            return false;
        }

        return base64_encode( $iv . $raw_value );
    }

    public function decrypt( $enc_value ) {
        if ( trim($enc_value) == '' ) {
            return '';
        }

        $raw_value = base64_decode( $enc_value, true );

        $method = 'aes-256-ctr';
        $iv_length = openssl_cipher_iv_length( $method );
        $iv = substr( $raw_value, 0, $iv_length );

        $raw_value = substr( $raw_value, $iv_length );

        $value = openssl_decrypt( $raw_value, $method, SECURE_AUTH_KEY, 0, $iv );
        if ( ! $value || substr( $value, - strlen( SECURE_AUTH_SALT ) ) !== SECURE_AUTH_SALT ) {
            return false;
        }

        return substr( $value, 0, - strlen( SECURE_AUTH_SALT ) );
    }

    public function hash64($value) {
        return base64_encode( hash_hmac( 'sha256', $value, AUTH_KEY, true ) );
    }

    public function hash($value) {
        return hash_hmac( 'sha256', $value, AUTH_KEY, false );
    }

}

