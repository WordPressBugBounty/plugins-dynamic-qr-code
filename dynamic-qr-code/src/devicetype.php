<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class DeviceType
{
    const UNKNOWN = 0;
    const MOBILE = 1;
    const NOT_MOBILE = -1;

    public static function getValueList( $skip_unknown = false ) {
        $ret = array();
        if ( $skip_unknown === false ) {
            $ret[] = self::UNKNOWN;
        }
        $ret[] = self::MOBILE;
        $ret[] = self::NOT_MOBILE;
        return $ret;
    }

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

    public static function getDescList( $skip_unknown = false ) {
        $ret = array();
        $values = self::getValueList($skip_unknown);
        foreach ( $values as $value ) {
            $ret[] = self::getDescription($value);
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