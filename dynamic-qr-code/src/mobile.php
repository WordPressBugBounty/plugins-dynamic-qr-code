<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Mobile
{
    private static $native = null;

    private static function instance() {
        if ( is_null(self::$native) ) {
            self::$native = new LIB\Mobile_Detect();
        }
        return self::$native;
    }

    public static function is() {
        return self::instance()->isMobile();
    }

    public static function isBrowser() {
        $ret = false;
        $instance = self::instance();
        foreach ( $instance::getBrowsers() as $key => $value ) {
            if ( $instance->is($key) ) {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

    public static function android() {
        return self::instance()->isAndroidOS();
    }
    public static function ios() {
        return self::instance()->isiOS();
    }

    public static function getLanguages() {
        $ret = '*';
        if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
            $http = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            if ( strlen($http) > 1) {
                $langs = array();
                $items = explode(',', $http);
                foreach ( $items as $_ ) {
                    $_ = strtolower($_);
                    if ( !sosidee_str_starts_with($_, 'und') ) {
                        $item = preg_replace( '/[^a-z]/', '', substr( $_, 0, 2) );
                        if ( strlen($item) > 1 && !in_array($item, $langs) ) {
                            $langs[] = $item;
                        }
                    }
                }
                if ( count($langs) > 0 ) {
                    $ret = $langs;
                }
            }
        }
        return $ret;
    }

}