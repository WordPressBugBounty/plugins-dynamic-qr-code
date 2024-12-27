<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\Stats;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class GraphType
{
    const NONE = 0;
    const PIE = 1;
    const BAR = 2;

    public static function getDescription( $value ) {
        $ret = '';
        switch ($value) {
            case self::PIE:
                $ret = 'pie chart';
                break;
            case self::BAR:
                $ret = 'bar chart';
                break;
        }
        return $ret;
    }

    public static function getList() {
        return [
             self::PIE => self::getDescription(self::PIE)
            ,self::BAR => self::getDescription(self::BAR)
        ];
    }


}