<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class CheckMode
{
    const METHOD = 0;
    const REFERER = 1;
    const FIELD = 2;

    public static function getList() {
        $ret = array();
        $ret[self::METHOD] = self::getDescription(self::METHOD);
        $ret[self::REFERER] = self::getDescription(self::REFERER);
        $ret[self::FIELD] = self::getDescription(self::FIELD);

        return $ret;
    }

    public static function getDescription( $value ) {
        $ret = "";
        switch ($value) {
            case self::METHOD:
                $ret = 'only request method (POST)';
                break;
            case self::REFERER:
                $ret = 'request method (POST) and referer (same post/page)';
                break;
            case self::FIELD:
                $ret = 'request method (POST) and custom field in form';
                break;
        }
        return $ret;
    }

}