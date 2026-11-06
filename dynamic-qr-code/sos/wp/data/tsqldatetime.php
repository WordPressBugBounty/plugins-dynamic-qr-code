<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TSqlDateTime // for WpColumn
{
    use TDateTime;

    private static function getZeroDatetime($format = 'Y-m-d H:i:s') {
        $referenceDate = new \DateTimeImmutable('2000-01-01 00:00:00');
        try {
            $formattedReference = $referenceDate->format($format);
            return preg_replace('/\d/', '0', $formattedReference);
        } catch (\Exception $e) {
            sosidee_log("TSqlDateTime::getZeroDatetime - Invalid format string '{$format}' for DateTimeImmutable. Error: " . $e->getMessage());
            return '0000-00-00 00:00:00';
        }
    }

    private static function getZeroTime($format = 'H:i:s') {
        $ret = self::getZeroDatetime($format);
        if ( $ret == '0000-00-00 00:00:00') {
            $ret = '00:00:00';
        }
        return $ret;
    }

    private static function isZeroDatetime( $value ) {
        $digits = preg_replace('/\D/', '', $value);
        return $digits !== '' && preg_match('/^0+$/', $digits) === 1;
    }

    private static function getQuoted( $value, $quoted ) {
        if ( $value == 'NULL' || $value == 'CURRENT_TIMESTAMP' ) {
            $quoted = false;
        }
        return $quoted ? "'" . esc_sql($value) . "'" : $value;
    }

    private function getNullDatetimeAsSqlString( $quoted ) {
        if ( $this->nullable ) {
            return 'NULL';
        } else {
            return self::getQuoted( self::getZeroDatetime(), $quoted );
        }
    }
    private function getNullTimeAsSqlString( $quoted ) {
        if ( $this->nullable ) {
            return 'NULL';
        } else {
            return self::getQuoted( self::getZeroTime(), $quoted );
        }
    }

    public function getDatetimeValueAsSqlString( $value, $quoted = false ) {
        if ( is_null($value) ) {
            return $this->getNullDatetimeAsSqlString($quoted);
        } elseif ( is_string($value) && strtoupper( trim($value) ) === 'CURRENT_TIMESTAMP' ) {
            return 'CURRENT_TIMESTAMP';
        } else {
            $stringValue = self::getDatetimeAsString( $value );
            if ( !empty( $stringValue ) ) {
                return self::getQuoted( $stringValue, $quoted );
            } else {
                return $this->getNullDatetimeAsSqlString($quoted);
            }
        }
    }

    public function getTimeValueAsSqlString( $value, $quoted = false ) {
        if ( is_null($value) ) {
            return $this->getNullTimeAsSqlString($quoted);
        } else {
            $stringValue = self::getTimeAsString( $value );
            if ( !empty( $stringValue ) ) {
                return self::getQuoted( $stringValue, $quoted );
            } else {
                return $this->getNullTimeAsSqlString($quoted);
            }
        }
    }

    private function getDatetimeValueFromString( $value ) {
        if ( !is_string($value) ) {
            return null;
        } else {
            if ( self::isZeroDatetime( $value ) ) {
                return null;
            } else {
                return self::getDatetimeFromString( $value );
            }
        }
    }

    private function getTimeValueFromString( $value ) {
        if ( is_null($value) ) {
            return null;
        } else {
            if ( self::isZeroDatetime( $value ) ) {
                return null;
            } else {
                return self::getTimeFromString( $value );
            }
        }
    }

}