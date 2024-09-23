<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Db
{

    public static function getDatetimeAsString( $value = null, $quoted = false ) {
        if ( is_null($value) ) {
            $value =  sosidee_current_datetime();
        }
        $quote = $quoted ? "'" : "";
        if ( $value instanceof \DateTime || $value instanceof \DateTimeImmutable ) {
            return $quote . $value->format('Y-m-d H:i:s') . $quote;
        } elseif ( $value === 0 || $value == '0' ) {
            return "{$quote}0000-00-00 00:00:00{$quote}";
        } elseif ( $value == 'CURRENT_TIMESTAMP' ) {
            return $value;
        } else {
            return "$quote{$value}$quote";
        }
    }

    public static function getDatetimeFromString( $value ) {
        try {
            $ret = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
            if ( !($ret instanceof \DateTime) ) {
                $ret = null;
            }
        } catch (\Exception $e) {
            sosidee_log($e);
            $ret = null;
        }
        return $ret;
    }

    public static function getTimeFromString( $value ) {
        try {
            $ret = \DateTime::createFromFormat('H:i:s', $value);
            if ( !($ret instanceof \DateTime) ) {
                $ret = null;
            }
        } catch (\Exception $e) {
            sosidee_log($e);
            $ret = null;
        }
        return $ret;
    }

    public static function getTimeAsString( $value, $quoted = false ) {
        if ( is_null($value) ) {
            $value = "";
        }

        $quote = $quoted ? "'" : "";
        if ( $value instanceof \DateTime || $value instanceof \DateTimeImmutable ) {
            $ret = $quote . $value->format('H:i:s') . $quote;
        } else if ( intval($value) == 0 ) {
            $ret = "{$quote}00:00:00{$quote}";
        } else {
            $ret = "$quote{$value}$quote";
        }
        return $ret;
    }


}