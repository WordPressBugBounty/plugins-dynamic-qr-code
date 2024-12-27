<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

class Text
{
    
    public static function dump( $obj ) {
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }

    public static function get_dump( $obj ) {
      ob_start();
      var_dump($obj);
      $ret = ob_get_contents();
      ob_end_clean();
      return $ret;
    }

    public static function getAlphanumeric( $value ) {
        return preg_replace("/[^A-Za-z0-9]/", '', $value);
    }
    
    public static function startsWith( $haystack, $needle ) {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
    
    public static function endsWith( $haystack, $needle ) {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    /*
        If delimiter contains a value that is not contained in string (even if string is empty), it will return a single item array containing string
        If delimiter is an empty string (""), it will return an array of a single character items
    */
    public static function split( $delimiter, $string ) {
       if ( !empty($delimiter) ) {
            return explode($delimiter, $string);
       } else {
            return str_split($string, 1);
       }
    }
    
    public static function isNullOrEmpty( $value ) {
        return ( !isset($value) || trim($value) === '' );
    }

    public static function getJsAlert( $value ) {
        return "alert('" . str_replace("'", "\'", $value) . "');";
    }

    // $length: lenght of the chunks
    // $sep: character(s) between chunks
    public static function getChunked( $string, $length, $sep ) {
        return substr( chunk_split($string, $length, $sep), 0, -strlen($sep) );
    }
    
    public static function getRndUserFriendly( $length, $uppercase = false ) {
        $source = '23456789abcdefghijkmnpqrstuvwxyz';
        if ($uppercase) {
            $source = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        }
        return self::getRandom($length, $source);
    }

    public static function getRandom( $length, $source = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
        $ret = '';
        if ( $length > 0 ) {
            $max = strlen($source);
            for ( $i = 0; $i < $length; $i++ ) {
                $randomKey = Number::getRandomInteger(0, $max);
                $ret .= $source[$randomKey];
            }
        }
        return $ret;        
    }

    private static $ciphering = 'aes-256-cbc'; // it grants a C# counterpart

    private static function getIvLength() {
        return openssl_cipher_iv_length(self::$ciphering);
    }
    public static function encrypt( $input, $key, $iv = '' ) {
        $iv_length = self::getIvLength();
        $iv = substr( str_pad($iv, $iv_length, '0', \STR_PAD_RIGHT), 0, $iv_length);
        return openssl_encrypt($input, self::$ciphering, $key, 0, $iv);
    }
    public static function decrypt( $input, $key, $iv = '' ) {
        $iv_length = self::getIvLength();
        $iv = substr( str_pad($iv, $iv_length, '0', \STR_PAD_RIGHT), 0, $iv_length);
        return openssl_decrypt($input, self::$ciphering, $key, 0, $iv);
    }

    public static function base64UrlEncode( $input ) {
        return rtrim(strtr( base64_encode($input), '+/', '-_'), '=');
    }

    public static function base64UrlDecode( $input ) {
        return base64_decode( strtr($input, '-_', '+/') );
    }    

}