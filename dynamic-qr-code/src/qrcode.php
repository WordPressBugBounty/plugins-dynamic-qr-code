<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SosPlugin;

class QrCode
{

    const CYPHER_LENGTH = 66;
    const CYPHER_LENGTH_MIN = 16;
    const IMAGE_PAD_MAX = 32;

    const IMAGE_FOREGROUND = '#000000';
    const IMAGE_BACKGROUND = '#ffffff';

    const DATETIME_MODE_FROM = 1;
    const DATETIME_MODE_TO = 2;

    private static function getDateTime($date, $time, $mode) {
        if ( is_null($date) && is_null($time)) {
            return null;
        } else {
            if ( is_null($date) ) {
                $date = sosidee_current_datetime();
            }
            if ( !is_null($time) ) {
                $date = $date->setTime( $time->format('G'), intval($time->format('i')), intval($time->format('s')) );
            } else {
                if ( $mode == self::DATETIME_MODE_FROM ) {
                    $date = $date->setTime( 0, 0, 0 );
                } else if ( $mode == self::DATETIME_MODE_TO ) {
                    $date = $date->setTime( 23, 59, 59 );
                }
            }
            return $date;
        }
    }

    public static function getStatus( $item ) {
        $ret = QrCodeStatus::NONE;

        if ( !$item->disabled ) {
            if ( OS::isValid( $item->device_os) ) {

                $datetime_from = self::getDateTime( $item->date_from, $item->time_from, self::DATETIME_MODE_FROM );
                $datetime_to = self::getDateTime( $item->date_to, $item->time_to, self::DATETIME_MODE_TO );

                if ( is_null($datetime_from) && is_null($datetime_to)  ) {
                    $ret = QrCodeStatus::ACTIVE;
                } else {
                    $now = sosidee_current_datetime();
                    if ( !is_null($datetime_from) && $datetime_from > $now ) {
                        $ret = QrCodeStatus::INACTIVE;
                    } else if ( !is_null($datetime_to) && $datetime_to < $now ) {
                        $ret = QrCodeStatus::EXPIRED;
                    } else {
                        $ret = QrCodeStatus::ACTIVE;
                    }
                }

                if ( $ret == QrCodeStatus::ACTIVE ) {
                    if ( !DotW::isValid($item->dotw) ) {
                        $ret = QrCodeStatus::DISABLED;
                    }
                }

                if ( $ret == QrCodeStatus::ACTIVE && $item->max_scan_tot > 0 ) {
                    $plugin = SosPlugin::instance();
                    $current = $plugin->database->countActiveLogs( $item->code );
                    if ( $current !== false ) {
                        if ( $current > $item->max_scan_tot ) {
                            $ret = QrCodeStatus::FINISHED;
                        }
                    } else {
                        sosidee_log("database.countActiveLogs($item->code) returned false.");
                    }
                }

                if ( $ret == QrCodeStatus::ACTIVE && $item->device_lang != '' ) {
                    $languages = Mobile::getLanguages();
                    if ( is_array($languages) ) {
                        $found = false;
                        foreach ($languages as $lang) {
                            if ( $item->device_lang == $lang ) {
                                $found = true;
                                break;
                            }
                        }
                        if ( $found == false ) {
                            $ret = QrCodeStatus::DISABLED;
                        }
                    }
                }

            } else {
                $ret = QrCodeStatus::DISABLED;
            }
        } else {
            $ret = QrCodeStatus::DISABLED;
        }
        return $ret;
    }

    private static function getRedirUrl( $specific, $general, $error ) {
        return $specific != '' ? $specific : ($general != '' ? $general : $error);
    }

    public static function getRedirectUrl( $item, $config ) {
        $ret = '';

        $status = $item->status;
        if ( $status == QrCodeStatus::ACTIVE ) {
            $ret = $item->url_redirect;
        } else {
            $error = $config->urlError->value;
            if ( $status == QrCodeStatus::INACTIVE ) {
                $ret = self::getRedirUrl( $item->url_inactive, $config->urlInactive->value, $error);
            } else if ( $status == QrCodeStatus::EXPIRED ) {
                $ret = self::getRedirUrl( $item->url_expired, $config->urlExpired->value, $error);
            } else if ( $status == QrCodeStatus::FINISHED ) {
                $ret = self::getRedirUrl( $item->url_finished, $config->urlFinished->value, $error);
            } else if ( $status == QrCodeStatus::DISABLED ) {
                $ret = $error;
            }
        }

        return $ret;
    }


    private function __construct()
    {
    }


    private static $initialized = false;
    private static $rootFolder = false;
    private static $rootUrl = false;

    private static function initializeLibrary() {
        if ( !self::$initialized ) {
                $plugin = SosPlugin::instance();
                $folder = $plugin->getTempFolder();
                self::$rootFolder = $folder['basedir'];
                self::$rootUrl = $folder['baseurl'];
                require_once realpath(__DIR__ . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "phpqrcode.php" );
                self::$initialized = true;
        }
    }

    public static function getUrl( $name, $text, $size, $pad = 0, $foreColor = 0x000000, $backColor = 0xFFFFFF ) {
        self::initializeLibrary();
        $filename = "{$name}.png";
        $filepath = self::$rootFolder . DIRECTORY_SEPARATOR . $filename;

        //$text = 'https://redirect.soslink.net/dynamic-qr-code/demo'; // only for the demo

        if ( !file_exists($filepath) ) {
            LIB\QRimage::$FIXED_SIZE = $size;
            LIB\QRimage::$PADDING = $pad;
            LIB\QRcode::png( $text, $filepath, DELTALAB_QR_ECLEVEL_H, $size, 0, false, $backColor, $foreColor );
            LIB\QRimage::$FIXED_SIZE = 0;
            LIB\QRimage::$PADDING = 0;
        }

        return self::$rootUrl . "/" . $filename;
    }

    public static function getString( $text, $size, $pad = 0, $foreColor = 0x000000, $backColor = 0xFFFFFF ) {
        self::initializeLibrary();
        ob_start();
        LIB\QRimage::$FIXED_SIZE = $size;
        LIB\QRimage::$PADDING = $pad;
        LIB\QRcode::png($text, null, DELTALAB_QR_ECLEVEL_H, $size, 0, false, $backColor, $foreColor);
        LIB\QRimage::$FIXED_SIZE = 0;
        LIB\QRimage::$PADDING = 0;
        $ret = base64_encode( ob_get_contents() );
        ob_end_clean();
        return $ret;
    }

    public static function getImageMaxSize() {
        self::initializeLibrary();
        return DELTALAB_QR_PNG_MAXIMUM_SIZE;
    }

    public static function getColor( $value ) {
        return hexdec( str_replace('#', '', $value) );
    }

    public static function roll( $count ) {
        $ret = false;
        try {
            $max = $count - 1;
            $ret = random_int( 0 , $max ); //pseudorandom number ( https://www.php.net/manual/en/function.random-int )
        } catch (\Exception $ex) {
            sosidee_log("QrCode.roll($count): arose an exception.");
            sosidee_log($ex);
        }
        return $ret;
    }

    public static function getQID( $id ) {
        if ( $id > 0 ) {
            return 'Q-' . $id;
        } else {
            return '';
        }
    }

    public static function getNewCypher() {
        $plugin = SosPlugin::instance();
        $length = $plugin->config->cypherLength->getValue();
        return bin2hex( random_bytes($length) );
    }

    public static function getB64Len( $value ) {
        return 4 * ceil( 2 * $value / 3 );
    }
    public static function getDecLen( $value ) {
        return floor(3 * $value / 8 );
    }

}