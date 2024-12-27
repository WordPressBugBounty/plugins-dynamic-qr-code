<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

class Mobile
{
    private static $native = null;

    private static function instance() {
        if ( is_null(self::$native) ) {
            LIBS\Psr\Loader::execute();
            LIBS\Detection\Loader::execute();
            self::$native = new LIBS\Detection\MobileDetect();
        }
        return self::$native;
    }

    public static function is() {
        return self::instance()->isMobile();
    }
    public static function tablet() {
        return self::instance()->isTablet();
    }

    public static function ios() {
        return self::instance()->isiOS();
    }
    public static function android() {
        return self::instance()->isAndroidOS();
    }

    public static function isBrowser() {
        $ret = false;
        foreach ( self::instance()->getBrowsers() as $key => $value ) {
            if ( self::instance()->is($key) ) {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

}