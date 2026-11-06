<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TDateTime
{


    public static function getDatetimeAsString( $value = 'now', $format = 'Y-m-d H:i:s' ) {
        if ( is_null($value) ) {
            return '';
        }
        if ( is_string($value) && strcasecmp( (string)$value, 'now' ) === 0 ) {
            $value =  sosidee_current_datetime();
        }
        if ( $value instanceof \DateTimeInterface ) {
            return $value->format($format);
        /*
        } elseif ( is_int($value) && $value >= 0 ) {
            try {
                $dt = new \DateTimeImmutable('@' . intval($value));
                return $dt->format($format);
            } catch (\Exception $e) {
                sosidee_log($e);
                return '';
            }
        } elseif ( is_string( $value ) ) {
            try {
                $value = trim($value);
                if ( strtolower( $value ) === 'null' ) {
                    return null;
                } elseif ( self::isZeroDatetime($value) ) {
                    return self::getZeroDatetime($format);
                } else {
                    $dt = new \DateTimeImmutable($value);
                    return $dt->format($format);
                }
            } catch (\Exception $e) {
                sosidee_log($e);
                return '';
            }
        */
        } else {
            sosidee_log("TDateTime::getDatetimeAsString() - value is not a DateTimeInterface: " . gettype($value));
            return '';
        }
    }

    public static function getDateAsString( $value = 'now', $format = 'Y-m-d' ) {
        return self::getDatetimeAsString($value, $format);
        /*
        if ( $ret == '0000-00-00 00:00:00') {
            $ret = '00:00:00';
        }
        return $ret;
        */
    }

    public static function getTimeAsString( $value = 'now', $format = 'H:i:s' ) {
        return self::getDatetimeAsString($value, $format);
        /*
        if ( $ret == '0000-00-00 00:00:00') {
            $ret = '00:00:00';
        }
        return $ret;
        */
    }

    private static function parseDatetimeWithFormat( $value, $format ) {
        if ( !is_string( $value ) ) {
            sosidee_log("TDateTime::parseDatetimeWithFormat() - value is not a string: " . gettype($value));
            return null;
        }
        $value = trim($value);
        if ( empty($value) ) {
            sosidee_log("TDateTime::parseDatetimeWithFormat() - value is an empty string after trim.");
            return null;
        }
        $ret = \DateTimeImmutable::createFromFormat($format, trim($value), wp_timezone());
        $errors = \DateTimeImmutable::getLastErrors();
        if ( $ret === false || !empty($errors['warning_count']) || !empty($errors['error_count']) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
            // print_r() is used correctly here, with its second parameter set to true.
            sosidee_log("TDateTime::parseDatetimeWithFormat() - error(s) occurred: " . print_r($errors, true));
            return null;
        }

        return $ret;
    }

    public static function getDatetimeFromString( $value, $format = 'Y-m-d H:i:s' ) {
        return self::parseDatetimeWithFormat( $value, $format );
    }

    public static function getDateFromString( $value, $format = 'Y-m-d' ) {
        if ( strpos($format, '!') !== 0 ) {
            $format = "!" . $format;
        }
        return self::parseDatetimeWithFormat( $value, $format );
    }

    public static function getTimeFromString( $value, $format = 'H:i:s' ) {
        if ( strpos($format, '!') !== 0 ) {
            $format = "!" . $format;
        }
        return self::parseDatetimeWithFormat( $value, $format );
    }

    public static function getDayOfTheWeek( $value = 'now', $format = 'Y-m-d' ) {
        if ( is_null($value) ) {
            return null;
        }

        if ( is_string($value) ) {
            if ( strcasecmp( $value, 'now' ) === 0 ) {
                $value =  sosidee_current_datetime();
            } else {
                $value = self::parseDatetimeWithFormat( $value, $format );
                if ( is_null($value) ) {
                    return null;
                }
            }
        }

        if ( $value instanceof \DateTimeInterface ) {
            return (int) $value->format('w');
        }

        return null;
    }

    public static function getDayNameOfTheWeek( $value = 'now', $format = 'Y-m-d' ) {
        if ( is_null($value) ) {
            return '';
        }
        if ( is_int($value) ) {
            $index = $value;
        } else {
            $index = self::getDayOfTheWeek( $value, $format );
        }
        if ( is_int($index) && $index >=0 && $index < 7 ) {
            global $wp_locale;
            return $wp_locale->get_weekday($index);
        } else {
            return '';
        }
    }

    public static function getWeekDays($mode = 'all') {
        $rets = [];
        $i0 = get_option( 'start_of_week' );
        for ( $i=$i0; $i < $i0 + 7; $i++ ) {
            $index = $i % 7;
            $name = self::getDayNameOfTheWeek($index);
            if ( $mode == 'index' ) {
                $rets[] = $index;
            } elseif ( $mode == 'name' ) {
                $rets[] = $name;
            } else {
                $rets[] = ['index' => $index, 'name' => $name];
            }
        }
        return $rets;
    }
}