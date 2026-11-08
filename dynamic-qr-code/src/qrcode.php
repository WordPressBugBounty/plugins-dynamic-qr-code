<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Request;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TBase;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TMessage;
use SOSIDEE_DYNAMIC_QRCODE\SRC\IO as TmpIO;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Builder\Builder;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Encoding\Encoding;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\ErrorCorrectionLevel; // È un Enum in v5 con PHP 8.1+
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Color\Color;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\PngWriter;

// per label o logo:
// use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\LabelAlignment;
// use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\Font\OpenSans; // o un altro font
// use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\RoundBlockSizeMode;

class QrCode
{

    use TBase;
    use TMessage;

    const IMAGE_MIN_SIZE = 64;
    const IMAGE_MAX_SIZE = 2048;

    const IMAGE_DEF_SIZE = 256;

    const IMAGE_MIN_PAD = 0;
    const IMAGE_MAX_PAD = 32;

    const CYPHER_LENGTH = 66;
    const CYPHER_LENGTH_MIN = 16;

    public const IMAGE_FOREGROUND = '#000000';
    const IMAGE_BACKGROUND = '#ffffff';

    private const PNG_END_CHUNK = "\x00\x00\x00\x00IEND\xAE\x42\x60\x82";

    const DATETIME_MODE_FROM = 1;
    const DATETIME_MODE_TO = 2;

    const REDIRECT_KEY_NAME = 'sos-dqc-key';

    private static function getDateTime( $date, $time, $mode ) {
        if ( is_null($date) && is_null($time) ) {
            return null;
        } else {
            if ( is_null($date) ) {
                $date = sosidee_current_datetime();
            } else {
                if ( !$date instanceof \DateTimeInterface ) {
                    sosidee_log("QrCode::getDateTime() - invalid type for date: " . gettype( $date ) );
                    return null;
                }
                if ( $date instanceof \DateTime ) {
                    $date = \DateTimeImmutable::createFromMutable($date);
                }
            }
            $ret = null;
            if ( !is_null($time) ) {
                if ( !$time instanceof \DateTimeInterface ) {
                    sosidee_log("QrCode::getDateTime() - invalid type for \$time.");
                    return null;
                }
                $ret = $date->setTime( $time->format('G'), intval($time->format('i')), intval($time->format('s')) );
            } else {
                if ( $mode == self::DATETIME_MODE_FROM ) {
                    $ret = $date->setTime( 0, 0, 0, 0 );
                } elseif ( $mode == self::DATETIME_MODE_TO ) {
                    $ret = $date->setTime( 23, 59, 59, 999999 );
                } else {
                    sosidee_log("QrCode::getDateTime() - invalid mode ($mode).");
                }
            }
            return $ret;
        }
    }

    public static function getStatus( $item, $info ) {
        if ( $item->disabled ) {
            return QrCodeStatus::DISABLED;
        }

        if ( $item->device_os != OS::UNKNOWN ) {
            if ( !OS::isValid( $item->device_os ) ) {
                return QrCodeStatus::EXCLUDED;
            }
        }

        if ( !DotW::isValid($item->dotw) ) {
            return QrCodeStatus::EXCLUDED;
        }

        $datetime_from = self::getDateTime( $item->date_from, $item->time_from, self::DATETIME_MODE_FROM);
        $datetime_to = self::getDateTime( $item->date_to, $item->time_to, self::DATETIME_MODE_TO);
        if ( !is_null($datetime_from) || !is_null($datetime_to) ) {
            $now = sosidee_current_datetime();
            if ( !is_null($datetime_from) && $datetime_from > $now ) {
                return QrCodeStatus::INACTIVE;
            } else if ( !is_null($datetime_to) && $datetime_to < $now ) {
                return QrCodeStatus::EXPIRED;
            }
        }

        if ( $item->device_lang != '' ) {
            $languages = Request::getBrowserLanguages();
            if ( is_array($languages) ) {
                $found = false;
                foreach ($languages as $lang) {
                    if ( $item->device_lang == $lang ) {
                        $found = true;
                        break;
                    }
                }
                if ( !$found ) {
                    return QrCodeStatus::EXCLUDED;
                }
            } else {
                if ( $info['preview'] ) {
                    return QrCodeStatus::EXCLUDED;
                }
            }
        }

        if ( $item->max_scan_tot > 0 ) {
            //self::plugin()->config->sharedCodeEnabled->load();
            //$by_key = $info['shared'];
            if ( $info['shared'] ) {
                $current = self::database()->logs->countByCode( $item->code );
            } else {
                $current = self::database()->logs->countById( $item->qrcode_id );
            }
            if ( $current !== false ) {
                $current++; //tengo conto della chiamata in corso
                if ( $current > $item->max_scan_tot ) {
                    return QrCodeStatus::FINISHED;
                }
            } else {
                if ( $info['shared'] ) {
                    sosidee_log("DB\Log::countByCode({$item->code}) returned false.");
                } else {
                    sosidee_log("DB\Log::countById({$item->qrcode_id}) returned false.");
                }
            }
        }

        return QrCodeStatus::ACTIVE;
    }

    private static function selectUrl($specific, $general, $error ) {
        return !empty($specific) ? $specific : (!empty($general) ? $general : $error);
    }

    public static function getRedirectUrl( $item, $status, $config ) {
        $error = $config->urlError->value;
        $ret = $error;

        if ( $status == QrCodeStatus::ACTIVE ) {
            $ret = $item->url_redirect;
        } else {
            if ( $status == QrCodeStatus::INACTIVE ) {
                $ret = self::selectUrl( $item->url_inactive, $config->urlInactive->value, $error );
            } elseif ( $status == QrCodeStatus::EXPIRED ) {
                $ret = self::selectUrl( $item->url_expired, $config->urlExpired->value, $error );
            } elseif ( $status == QrCodeStatus::FINISHED ) {
                $ret = self::selectUrl( $item->url_finished, $config->urlFinished->value, $error );
            }
        }

        return self::plugin()->applyFilter( '_qrcode_get_redirect_url', $ret, $item);
    }


    private function __construct()
    {
    }

    private static function getResult( $parameters ) {
        try {
            $text = $parameters['text'] ?? '';
            $size = $parameters['size'] ?? self::IMAGE_DEF_SIZE;
            $size = max(self::IMAGE_MIN_SIZE, min($size, self::IMAGE_MAX_SIZE));
            $pad = $parameters['pad'] ?? 0;
            $pad = max(0, min($pad, self::IMAGE_MAX_PAD));
            $foreColorHex = $parameters['foreColor'] ?? 0x000000;
            $backColorHex = $parameters['backColor'] ?? 0xFFFFFF;
            $level = $parameters['level'] ?? QrCodeLevel::getDefault();
            $level = QrCodeLevel::getNative($level);

            // Estrazione dei componenti RGB dai colori esadecimali
            $fgR = ($foreColorHex >> 16) & 0xFF;
            $fgG = ($foreColorHex >> 8) & 0xFF;
            $fgB = $foreColorHex & 0xFF;

            $bgR = ($backColorHex >> 16) & 0xFF;
            $bgG = ($backColorHex >> 8) & 0xFF;
            $bgB = $backColorHex & 0xFF;

            $builder = new Builder(
                data: $text,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: $level,
                size: $size,
                margin: $pad,
                foregroundColor: new Color($fgR, $fgG, $fgB),
                backgroundColor: new Color($bgR, $bgG, $bgB),
                writer: new PngWriter()
            // Opzioni Label (di default non viene creata la label)
            // labelText: '',
            // labelFont: new Font(...), // Richiederebbe il percorso corretto al font
            // labelAlignment: LabelAlignment::Center,
            // labelMargin: new Margin(...),
            // labelTextColor: new Color(...),
            // Opzioni Logo
            // logoPath: '',
            // logoResizeToWidth: null,
            // logoPunchoutBackground: false
            );

            return $builder->build();

        } catch (\Throwable $e) {
            sosidee_log("SRC\QrCode::getResult() - An error occurred while generating the QR Code: " . $e->getMessage());
            return null;
        }
    }

    public static function saveImg( $name, $parameters ) {
        $name = empty($name) ? '' : sanitize_file_name($name);
        if ( $name === '' ) {
            sosidee_log("SRC\QrCode::saveImg() - Name is empty.");
            return false;
        }

        try {
            $folder = TmpIO::getImgPath();
            if ( empty($folder) ) {
                sosidee_log("SRC\QrCode::saveImg() - An error occurred while retrieving the image file path.");
                return false;
            }

            $filepath = $folder . $name;
            // Older releases could leave a truncated PNG in the cache after a failed write.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            if ( is_file($filepath) && @file_get_contents($filepath, false, null, -strlen(self::PNG_END_CHUNK)) === self::PNG_END_CHUNK ) {
                return true;
            }

            $result = self::getResult($parameters);
            if ( null === $result ) {
                return false;
            }

            $contents = $result->getString();
            $temporary = $folder . '.qr-' . bin2hex(random_bytes(8)) . '.tmp';
            try {
                // Publish the local cache file only after the full PNG has been written.
                // phpcs:ignore WordPress.WP.AlternativeFunctions
                if ( $contents !== '' && @file_put_contents($temporary, $contents) === strlen($contents) && @rename($temporary, $filepath) ) {
                    return true;
                }
            } finally {
                if ( is_file($temporary) ) {
                    wp_delete_file($temporary);
                }
            }

            sosidee_log("SRC\QrCode::saveImg() - An error occurred while saving the image file ($filepath).");
        } catch (\Throwable $e) {
            sosidee_log("SRC\QrCode::saveImg() - An error occurred while caching the image: " . $e->getMessage());
        }

        return false;
    }


    public static function getUniqueName( $pars, $prefix = '' ) {
        if ( !empty($prefix) ) {
            $prefix = rtrim($prefix, '_') . '_';
        }
        ksort($pars);
        $name = $prefix . sha1(json_encode($pars)); // 40 chars + prefix
        $filename = rtrim($name, '.') . '.png';
        return sanitize_file_name($filename);
    }

    public static function getUrl( $filename ) {
        $url = TmpIO::getImgUrl();
        if ( empty($url) ) {
            sosidee_log("SRC\QrCode::getUrl() - An error occurred while retrieving the image file url.");
            return '';
        }
        return $url . sanitize_file_name($filename);
    }

    public static function getString($parameters) {
        $result = self::getResult($parameters);

        if ( null === $result ) {
            return '';
        }

        return base64_encode($result->getString());
    }


    public static function getImageMaxSize() {
        return self::IMAGE_MAX_SIZE;
    }
    public static function getImageMinSize() {
        return self::IMAGE_MIN_SIZE;
    }
    public static function getImageDefSize() {
        return self::IMAGE_DEF_SIZE;
    }
    public static function getImageMaxPad() {
        return self::IMAGE_MAX_PAD;
    }
    public static function getImageMinPad() {
        return self::IMAGE_MIN_PAD;
    }

    public static function getColor( $value ) {
        return hexdec( str_replace('#', '', $value) );
    }

    public static function roll( $count ) {
        $max = $count - 1;
        try {
            $ret = random_int( 0 , $max ); //pseudorandom number ( https://www.php.net/manual/en/function.random-int )
        } catch (\Exception $ex) {
            $ret = mt_rand( 0 , $max );
            sosidee_log("QrCode.roll($count): random_int() arose an exception.");
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
        $length = self::plugin()->config->cypherLength->getValue();
        return bin2hex( random_bytes($length) );
    }

    public static function getB64Len( $value ) {
        return 4 * ceil( 2 * $value / 3 );
    }
    public static function getDecLen( $value ) {
        return floor(3 * $value / 8 );
    }

    public static function loadQrCodeList( $caption = false, $include_cancelled = false ) {
        $ret = [];
        if ( $caption !== false ) {
            $ret[0] = $caption;
        }

        $table = self::database()->qrcodes;
        $orders = [ $table->description->name ];
        $results = $table->loadList( [], $orders, $include_cancelled );

        if ( is_array($results) ) {
            if ( count($results) > 0 ) {
                for ( $n=0; $n<count($results); $n++ ) {
                    $ret[ $results[$n]->qrcode_id ] = $results[$n]->description;
                }
            }
        } else {
            self::msgErr( 'A problem occurred while reading the qr code list from the database.' );
        }
        return $ret;
    }

}
