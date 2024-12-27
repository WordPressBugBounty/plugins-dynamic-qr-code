<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\Mobile;

class OS
{

    const UNKNOWN = 0;
    const ANDROID = 1;
    const IOS = 2;
    const OTHER = 3;


    public static function isValid( $value ) {
        if ( $value != self::UNKNOWN) {
            if ( Mobile::android() ) {
                return $value == self::ANDROID;
            } else if ( Mobile::ios() ) {
                return $value == self::IOS;
            } else {
                return $value == self::OTHER;
            }
        } else {
            return true;
        }
    }

    public static function getList( $caption = false ) {
        $ret = array();

        if ($caption !== false) {
            $ret[self::UNKNOWN] = $caption;
        }
        $ret[self::ANDROID] = self::getDescription(self::ANDROID);
        $ret[self::IOS] = self::getDescription(self::IOS);
        $ret[self::OTHER] = self::getDescription(self::OTHER);

        return $ret;
    }

    public static function getDescription( $value ) {
        $ret = 'unknown';
        switch ($value) {
            case self::ANDROID:
                $ret = 'Android';
                break;
            case self::IOS:
                $ret = 'iOS';
                break;
            case self::OTHER:
                $ret = 'others';
                break;
        }
        return $ret;
    }
}