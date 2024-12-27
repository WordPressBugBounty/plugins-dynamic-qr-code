<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

class Datetime
{

    public static function getFromString( $string, $format = 'YmdHis' ) {
        $ret = false;
        try {
            $ret = \DateTime::createFromFormat($format, $string);
            if ( !($ret instanceof \DateTime) ) {
                $ret = false;
            }
        } catch (\Exception $e) {}
        return $ret;
    }

    public static function getFromObject( $object ) {
        $ret = null;
        if ( !is_null($object) ) {
            try {
                $date = $object->date;
                $ret = \DateTime::createFromFormat('Y-m-d H:i:s.u', $date);
                if ( $ret instanceof \DateTime ) {
                    if ($object->timezone_type === 1) {
                        $ret->setTimezone(new \DateTimeZone('UTC'));
                    } elseif ($object->timezone_type === 2) {
                        $ret->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                    } elseif ($object->timezone_type === 3) {
                        $ret->setTimezone(new \DateTimeZone($object->timezone));
                    }
                } else {
                    $ret = null;
                }
            } catch (\Exception $e) {}
        }
        return $ret;
    }

    public static function getWithMicro() {
        return new \DateTime(date("Y-m-d H:i:s.").explode(".",microtime(true))[1] );
    }

    public static function getString( $value = null, $format = 'YmdHis' ) {
        if ( is_null($value) ) {
            $value = new \DateTime();
        }
        if ( is_string($value) ) {
            $format = $value;
            $value = new \DateTime();
        }
        if ( $format == '12' ) {
            $c = self::getMonth($value) + 64;
            $format = 'y\\' . chr($c) . 'dHis';
        }
        return $value->format($format);
    }

    private static function _getX( $v, $p, $str = false ) {
        if ( is_null($v) ) {
            if ( $p != 'u' ) {
                $v = new \DateTime();
            } else {
                $v = self::getWithMicro();
            }
        }
        $ret = $v->format($p);
        if ( !$str ) { $ret = intval($ret); }
        return $ret;
    }
    public static function getYear( $value = null ) {
        return self::_getX($value, "Y");
    }
    public static function getMonth( $value = null ) {
        return self::_getX($value, "n");
    }
    public static function getDay( $value = null ) {
        return self::_getX($value, "j");
    }
    public static function getHour( $value = null ) {
        return self::_getX($value, "G");
    }
    public static function getMinute( $value = null ) {
        return self::_getX($value, "i");
    }
    public static function getSecond( $value = null, $round_ms = false ) {
        return self::_getX($value, "s");
    }
    public static function getMicro( $value = null ) {
        return self::_getX($value, "u");
    }
    //Dotw: day of the week [0=sunday]
    public static function getDotw( $value ) {
        return self::_getX($value, "w", true);
    }
    public static function getMonthName( $value = null, $length = 0 ) {
        global $SOS_USR;
        if ( is_null($value) ) {
            $value = new \DateTime();
        }
        if ( isset($SOS_USR) ) {
            $months = Lang::$MONTHS[$SOS_USR->lang];
            $ret = $months[self::getMonth($value)];
        } else {
            $ret = self::_getX($value, "F", true);
        }
        if ( $length > 0 ) {
            $ret = substr($ret, 0, $length);
        }
        return $ret;
    }
    // Dow: day of the week
    public static function getDotwName( $value = null, $length = 0 ) {
        global $SOS_USR;
        if ( is_null($value) ) {
            $value = new \DateTime();
        }
        if ( isset($SOS_USR) ) {
            $days = Lang::$DAYS[$SOS_USR->lang];
            $ret = $days[self::getDotw($value)];
        } else {
            $ret = self::_getX($value, "l", true);
        }
        if ( $length > 0 ) {
            $ret = substr($ret, 0, $length);
        }
        return $ret;
    }
    public static function setYear( $datetime, $value ) {
        return $datetime->setDate($value, self::getMonth($datetime), self::getDay($datetime));
    }
    public static function setMonth( $datetime, $value ) {
        return $datetime->setDate(self::getYear($datetime), $value, self::getDay($datetime));
    }
    public static function setDay( $datetime, $value ) {
        return $datetime->setDate(self::getYear($datetime), self::getMonth($datetime), $value);
    }
    public static function setHour( $datetime, $value ) {
        return $datetime->setTime($value, self::getMinute($datetime), self::getSecond($datetime));
    }
    public static function setMinute( $datetime, $value ) {
        return $datetime->setTime(self::getHour($datetime), $value, self::getSecond($datetime));
    }
    public static function setSecond( $datetime, $value ) {
        return $datetime->setTime(self::getHour($datetime), self::getMinute($datetime), $value);
    }
    public static function setMicro( $datetime, $value ) {
        $str = $datetime->format("YmdHis") . sprintf('.%06d', $value);
        return \DateTime::createFromFormat("YmdHis.u", $str);
    }

    public static function roundMicro( $datetime ) {
        $ms = self::getMicro($datetime);
        $ret = self::setMicro($datetime, 0);
        if ( $ms >= 500000 ) {
            $ret = $ret->add( new \DateInterval('PT1S') );
        }
        return $ret;
    }

    public static function getFromValues( $year, $month, $day, $hour = 0, $minute = 0, $second = 0 ) {
        $value = sprintf('%4d%02d%02d%02d%02d%02d', $year, $month, $day, $hour, $minute, $second);
        return self::getFromString($value);
    }

    public static function getTime( $hour = 0, $minute = 0, $second = 0 ) {
        $now = new \DateTime();
        $year = self::getYear($now);
        $month = self::getMonth($now);
        $day = self::getDay($now);
        $value = sprintf( '%4d%02d%02d%02d%02d%02d', $year, $month, $day, $hour, $minute, $second );
        return self::getFromString($value);
    }

    private static function _addX( $interval, $datetime, $negative ) {
        if ( is_null($datetime) ) {
            $dt = new \DateTime();
        } else {
            $dt = clone $datetime;
        }
        if ( !$negative ) {
            return $dt->add( new \DateInterval($interval) );
        } else {
            return $dt->sub( new \DateInterval($interval) );
        }
    }
    public static function addYear( $value, $datetime = null ) {
        $avalue = abs($value);
        return self::_addX("P{$avalue}Y", $datetime, $value < 0);
    }
    public static function addMonth( $value, $datetime = null ) {
        $avalue = abs($value);
        return self::_addX("P{$avalue}M", $datetime, $value < 0);
    }
    public static function addDay( $value, $datetime = null ) {
        $avalue = abs($value);
        return self::_addX("P{$avalue}D", $datetime, $value < 0);
    }
    public static function addHour( $value, $datetime = null ) {
        $avalue = abs($value);
        return self::_addX("PT{$avalue}H", $datetime, $value < 0);
    }
    public static function addMinute( $value, $datetime = null ) {
        $avalue = abs($value);
        return self::_addX("PT{$avalue}M", $datetime, $value < 0);
    }
    public static function addSecond( $value, $datetime = null ) {
        $avalue = abs($value);
        return self::_addX("PT{$avalue}S", $datetime, $value < 0);
    }
    public static function compare( $datetime1, $datetime2 = null ) {
        if ( is_null($datetime2) ) {
            $datetime2 = new \DateTime();
        }
        if ( $datetime1 == $datetime2 ) {
            return 0;
        } else if ( $datetime1 > $datetime2 ) {
            return 1;
        } else {
            return -1;
        }
    }

}