<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\Stats;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class DTMode
{
    const NONE = 0;
    const HOUR = 1;
    const DAY = 2;

    public static function getDescription( $value ) {
        $ret = '';
        switch ($value) {
            case self::HOUR:
                $ret = 'hour';
                break;
            case self::DAY:
                $ret = 'day';
                break;
        }
        return $ret;
    }


    public static function getList() {
        return [
             self::HOUR => self::getDescription(self::HOUR)
            ,self::DAY => self::getDescription(self::DAY)
        ];
    }

}