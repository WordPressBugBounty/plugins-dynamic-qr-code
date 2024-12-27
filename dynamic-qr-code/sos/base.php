<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

class Base
{
    protected static $log = null;
    protected static $db = null;

    protected static function hasLog() {
        return !is_null(self::$log);
    }

    protected static function hasDb() {
        return !is_null(self::$db);
    }

    public static function set( $obj ) {
        if ( is_null(self::$log) && is_a($obj, 'SOSIDEE_DYNAMIC_QRCODE\SOS\Logger', true) ) {
            self::$log = $obj;
        }
        if ( is_null(self::$db) && ( is_a($obj, 'SOSIDEE_DYNAMIC_QRCODE\SOS\Medoo', true) || is_a($obj, 'SOSIDEE_DYNAMIC_QRCODE\SOS\PDO', true) ) ) {
            self::$db = $obj;
        }
    }

    public static function isWp() {
        return defined( 'ABSPATH' ) && defined( 'WPINC' );
    }
    
}