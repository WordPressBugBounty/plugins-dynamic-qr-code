<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class DeviceType
{
    const UNKNOWN = 0;
    const MOBILE = 1;
    const NOT_MOBILE = -1;

    public static function getDescription( $value ) {
        $ret = '?';
        switch ( $value ) {
            case self::UNKNOWN:
                $ret = 'unknown';
                break;
            case self::MOBILE:
                $ret = 'mobile';
                break;
            case self::NOT_MOBILE:
                $ret = 'not mobile';
                break;
        }
        return $ret;
    }

    public static function getlist( $caption = false ) {
        $ret = array();

        if ($caption !== false) {
            $ret[self::UNKNOWN] = $caption;
        }
        $ret[self::MOBILE] = 'only mobile';
        $ret[self::NOT_MOBILE] = 'only not mobile';

        return $ret;
    }

}