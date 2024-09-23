<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class App
{
    private static $cookies = null;
    /**
        $id = $cookies[0];
        $version = $cookies[1];
        $lang = $cookies[2];
        $user = $cookies[3];
     */
    private static function getCookie($index) {
        $ret = false;
        if ( is_null(self::$cookies) ) {
            $name = 'myfastapp-cli';
            if ( isset($_COOKIE[$name]) ) {
                $decoded = base64_decode( sanitize_text_field( $_COOKIE[$name] ) );
                self::$cookies = json_decode( $decoded );
            }
        }
        if ( is_array(self::$cookies) ) {
            $ret = self::$cookies[$index];
        }
        return $ret;
    }

    public static function getUserId() {
        $ret = self::getHttpHeader('X-OnesignalUserId');
        if ( $ret === false ) {
            $ret = self::getCookie(3);
        }
        return $ret;
    }

    public static function isMyFastApp() {
        $ret = false;
        $id = self::getHttpHeader('X-AppId');
        if ( $id === false ) {
            $id = self::getCookie(0);
            if ( $id === false ) {
                $ua = self::getHttpHeader('User-Agent');
                if ($ua !== false) {
                    $ret = strpos( strtolower($ua), 'myfastapp' ) !== false;
                }
            } else {
                $ret = true;
            }
        } else {
            $ret = true;
        }
        return $ret;
    }

    private static function getHttpHeader( $key ) {
        $ret = false;
        $http = 'HTTP_' . str_replace('-', '_', strtoupper($key));
        if ( array_key_exists($http, $_SERVER) && !empty($_SERVER[$http]) ) {
            $ret = $_SERVER[$http];
        }
        return $ret;
    }

}