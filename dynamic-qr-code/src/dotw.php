<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class DotW
{
    public static function isValid( $value ) {
        if ( $value <=0 || $value > 7 ) {
            return true;
        }
        $day = intval( date("w") );
        if ( $value == 7 ) {
            return $day == 0;
        } else {
            return $day == $value;
        }
    }

    public static function getList($caption = false) {
        $ret = array();

        if ($caption !== false) {
            $ret[0] = $caption;
        }
        $ret[1] = 'monday';
        $ret[2] = 'tuesday';
        $ret[3] = 'wednesday';
        $ret[4] = 'thursday';
        $ret[5] = 'friday';
        $ret[6] = 'saturday';
        $ret[7] = 'sunday';

        return $ret;
    }

}