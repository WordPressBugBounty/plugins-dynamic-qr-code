<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class RealBrowserThreshold
{
    const NONE = -666;
    const LOW = 1;
    const BALANCED = 3;
    const HIGH = 5;

    public static function getList($caption = false) {
        $ret = array();

        if ($caption !== false) {
            $ret[self::NONE] = $caption;
        }
        $ret[self::LOW] = self::getDescription(self::LOW);
        $ret[self::BALANCED] = self::getDescription(self::BALANCED);
        $ret[self::HIGH] = self::getDescription(self::HIGH);

        return $ret;
    }

    public static function getDescription( $value ) {
        $ret = "";
        switch ($value) {
            case self::LOW:
                $ret = 'Low blocking';
                break;
            case self::BALANCED:
                $ret = 'Balanced (recommended)';
                break;
            case self::HIGH:
                $ret = 'Strict blocking';
                break;
        }
        return $ret;
    }

}