<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class QrCodeSearchStatus
{
    const NONE = 0;
    const ENABLED = 1;
    const DISABLED = -1;

    public static function getList($caption = false) {
        $ret = array();

        if ($caption !== false) {
            $ret[self::NONE] = $caption;
        }
        $ret[self::ENABLED] = self::getDescription(self::ENABLED);
        $ret[self::DISABLED] = self::getDescription(self::DISABLED);

        return $ret;
    }

    public static function getDescription( $value ) {
        $ret = "";
        switch ($value) {
            case self::ENABLED:
                $ret = 'enabled';
                break;
            case self::DISABLED:
                $ret = 'disabled';
                break;
        }
        return $ret;
    }

    public static function getStatusIcon($value) {
        $ret = '';
        if ( is_bool($value) ) {
            $value = $value ? self::ENABLED : self::DISABLED;
        }
        switch ($value) {
            case self::DISABLED:
                $ret = self::getIcon('do_not_disturb', 'red', self::getDescription($value));
                break;
            case self::ENABLED:
                $ret = self::getIcon('check_circle', 'green', self::getDescription($value));
                break;
        }
        return $ret;
    }

    private static function getIcon( $label, $color = "", $title = "" ) {
        $color = $color != "" ? " color:$color;" : "";
        return '<i title="' . esc_attr($title) .'" class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit;' . esc_attr($color) . '">' . esc_textarea($label) .'</i>';
    }

}