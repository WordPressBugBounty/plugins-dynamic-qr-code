<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOSWP;

class OTKey
{

    const COOKIENAME = 'sos_dynqrcode_ot_key';

    public static function getNew() {
        return bin2hex( random_bytes(40) );
    }

    public static function setCookie( $value ) {
        SOSWP\Cookie::set( self::COOKIENAME, $value );
    }

    public static function getCookie() {
        return SOSWP\Cookie::get( self::COOKIENAME );
    }

    public static function deleteCookie() {
        return SOSWP\Cookie::del( self::COOKIENAME );
    }


}