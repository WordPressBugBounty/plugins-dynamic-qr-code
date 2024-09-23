<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class LogStatus
{
    const NONE = QrCodeStatus::NONE;
    const ACTIVE = QrCodeStatus::ACTIVE;
    const INACTIVE = QrCodeStatus::INACTIVE;
    const DISABLED = QrCodeStatus::DISABLED;
    const FINISHED = QrCodeStatus::FINISHED;
    const EXPIRED = QrCodeStatus::EXPIRED;
    const ERROR = QrCodeStatus::ERROR;

    public static function getList($caption = false) {
        $ret = array();

        if ($caption !== false) {
            $ret[self::NONE] = $caption;
        }
        $ret[self::ACTIVE] = self::getDescription(self::ACTIVE);
        $ret[self::INACTIVE] = self::getDescription(self::INACTIVE);
        $ret[self::DISABLED] = self::getDescription(self::DISABLED);
        $ret[self::EXPIRED] = self::getDescription(self::EXPIRED);
        $ret[self::FINISHED] = self::getDescription(self::FINISHED);
        $ret[self::ERROR] = self::getDescription(self::ERROR);

        return $ret;
    }


    public static function getDescription( $value ) {
        $ret = '';
        switch ($value) {
            case self::ACTIVE:
                $ret = 'active';
                break;
            case self::INACTIVE:
                $ret = 'inactive';
                break;
            case self::DISABLED:
                $ret = 'disabled';
                break;
            case self::EXPIRED:
                $ret = 'expired';
                break;
            case self::FINISHED:
                $ret = 'finished';
                break;
            case self::ERROR:
                $ret = 'error';
                break;
        }
        return $ret;
    }

    public static function getStatusIcon($value) {
        $ret = '';
        switch ($value) {
            case self::ACTIVE:
                $ret = self::getIcon('check_circle', 'green', self::getDescription($value));
                break;
            case self::INACTIVE:
                $ret = self::getIcon('hourglass_top', 'blue', self::getDescription($value));
                break;
            case self::DISABLED:
                $ret = self::getIcon('do_not_disturb', 'red', self::getDescription($value));
                break;
            case self::FINISHED:
                $ret = self::getIcon('do_not_disturb_on', '#94989a', self::getDescription($value));
                break;
            case self::EXPIRED:
                $ret = self::getIcon('hourglass_bottom', 'orange', self::getDescription($value));
                break;
            case self::ERROR:
                $ret = self::getIcon('error', 'red', self::getDescription($value));
                break;
        }
        return $ret;
    }

    private static function getIcon( $label, $color = "", $title = "" ) {
        $color = $color != "" ? " color:$color;" : "";
        return '<i title="' . esc_attr($title) .'" class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit;' . esc_attr($color) . '">' . esc_textarea($label) .'</i>';
    }

}

