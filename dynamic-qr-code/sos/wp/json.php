<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Json
{

    public static function encode( $value ) {
        try {
            return json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR );
        } catch ( \JsonException $e ) {
            $error = "SOS\Json.encode() Error: " . $e->getMessage() . " for value=" . serialize($value);
            sosidee_log($error);
            return false; // restituisce false per mantenere il comportamento non-eccezione
        }
    }

    public static function decode( string $json, $as_array = false ) {
        $json = trim($json);
        if ( empty($json) ) return null;

        try {
            return json_decode( $json, $as_array, 512, JSON_THROW_ON_ERROR );
        } catch ( \JsonException $e ) {
            $error = "SOS\Json.decode() (as array=$as_array) Error: " . $e->getMessage() . " for string=" . $json;
            sosidee_log($error);
            return null; // Restituisce null in caso di errore
        }
    }

}