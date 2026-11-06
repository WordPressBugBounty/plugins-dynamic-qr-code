<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\ErrorCorrectionLevel;

class QrCodeLevel
{
    const UNKNOWN = 0;
    const LOW = 1;
    const MEDIUM = 2;

    const QUARTILE = 3;
    const HIGH = 4;

    public static function getDefault() {
        return self::HIGH;
    }

    public static function getNative( $value ) {
        switch ($value) {
            case self::HIGH:
                return ErrorCorrectionLevel::High;
            case self::QUARTILE:
                return ErrorCorrectionLevel::Quartile;
            case self::MEDIUM:
                return ErrorCorrectionLevel::Medium;
            case self::LOW:
                return ErrorCorrectionLevel::Low;
        }
        return ErrorCorrectionLevel::High;
    }

    public static function getValueList( $skip_unknown = false ) {
        $ret = array();
        if ( $skip_unknown === false ) {
            $ret[] = self::UNKNOWN;
        }
        $ret[] = self::HIGH;
        $ret[] = self::QUARTILE;
        $ret[] = self::MEDIUM;
        $ret[] = self::LOW;
        return $ret;
    }

    public static function getDescList( $skip_unknown = false ) {
        $ret = array();
        $values = self::getValueList($skip_unknown);
        foreach ( $values as $value ) {
            $ret[] = self::getDescription($value);
        }
        return $ret;
    }

    public static function getDescription( $value, $default = '' ) {
        $ret = $default;
        switch ($value) {
            case self::HIGH:
                $ret = 'Maximum';
                break;
            case self::QUARTILE:
                $ret = 'Strong';
                break;
            case self::MEDIUM:
                $ret = 'Standard';
                break;
            case self::LOW:
                $ret = 'Basic';
                break;
        }
        return $ret;
    }

    public static function getList( $caption = false ) {
        $ret = array();

        if ( $caption !== false ) {
            $ret[self::UNKNOWN] = $caption;
        }
        $values = self::getValueList(true);
        foreach ( $values as $value ) {
            $ret[$value] = self::getDescription($value);
        }
        return $ret;
    }


}