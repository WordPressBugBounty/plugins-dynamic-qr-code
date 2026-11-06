<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SosPlugin;

class Shortcode
{
    public const AUTH = 'auth';
    private const FORM = 'form';

    private const CSS_CLASS = 'class';
    public const DISPLAY = 'display';
    public const PAD = 'border';

    public const IMAGE = 'image';

    public const SIZE = 'size';

    public const QUALITY = 'quality';

    public const TIMEOUT = 'timeout';
    public const FORECOLOR = 'forecolor';
    public const BACKCOLOR = 'backcolor';

    public const TAG = 'dynqrcode';

    public $mode;

    public $id;
    public $hasForm;

    public $imageSize;
    public $imagePad;
    public $imageQuality;
    public $cssClass;
    public $imageType;
    public $timeout;
    public $colorFore;
    public $colorBack;

    public function __construct( $args ) {
        $this->mode = ShortcodeMode::NONE;

        $this->id = 0;
        $this->hasForm = false;
        $this->imageSize = 0;
        $this->imagePad = 0;
        $this->imageQuality = QrCodeLevel::UNKNOWN;
        $this->cssClass = null;
        $this->imageType = 'enhanced';
        $this->timeout = 0;
        $this->colorFore = '';
        $this->colorBack = '';

        $this->load($args);
    }


    private function load( $args ) {

        $this->id = intval( $args[self::AUTH] ?? 0 );
        if ( $this->id > 0 ) {
            $this->mode = (int)ShortcodeMode::HIDE_CONTENT;
        } else {
            $this->id = intval( $args[self::DISPLAY] ?? 0 );
            if ( $this->id > 0 ) {
                $this->mode = (int)ShortcodeMode::DISPLAY_IMAGE;
            }
        }

        if ( $this->mode == ShortcodeMode::HIDE_CONTENT ) {

            $this->hasForm = isset($args[self::FORM]) && ( intval($args[self::FORM]) == 1 || strcasecmp( trim($args[self::FORM]), 'true') == 0 );

        } else if ( $this->mode == ShortcodeMode::DISPLAY_IMAGE ) {

            $plugin = SosPlugin::instance();

            $this->imageSize = intval( $args[self::SIZE] ?? 0 );

            $this->imagePad = array_key_exists( self::PAD, $args )
                ? intval( $args[self::PAD] )
                : -1;

            if ( isset($args[self::IMAGE]) && strtolower($args[self::IMAGE]) == 'standard' ) {
                $this->imageType = 'standard';
            }

            $this->cssClass = $args[self::CSS_CLASS] ?? null;
            $this->timeout = intval( $args[self::TIMEOUT] ?? 0 );
            $this->colorFore = trim( $args[self::FORECOLOR] ?? '' );
            $this->colorBack = trim( $args[self::BACKCOLOR] ?? '');

            $plugin->doAction('_shortcode_load', $this, $args );

        }
    }

    public function fillUp( $qrcode ) {
        $plugin = SosPlugin::instance();
        $config = $plugin->config;

        if ( $this->colorFore == '') {
            $this->colorFore = $qrcode->img_forecolor;
            if ( $this->colorFore == '') {
                $this->colorFore = $config->imgForeColor->getValue();
            }
        }
        if ( $this->colorBack == '') {
            $this->colorBack = $qrcode->img_backcolor;
            if ( $this->colorBack == '') {
                $this->colorBack = $config->imgBackColor->getValue();
            }
        }


        $plugin->doAction('_shortcode_fillup', $this, $qrcode, $config );

        if ( $this->imageSize < QrCode::getImageMinSize() || $this->imageSize > QrCode::getImageMaxSize() ) {
            $this->imageSize = $config->imgSize->getValue();
            if ( $this->imageSize < QrCode::getImageMinSize() || $this->imageSize > QrCode::getImageMaxSize() ) {
                $this->imageSize = QrCode::getImageDefSize();
            }
        }

        if ( $this->imagePad < QrCode::getImageMinPad() || $this->imagePad > QrCode::getImageMaxPad() ) {
            $this->imagePad = $config->imgPad->getValue();
            if ( $this->imagePad < QrCode::getImageMinPad() || $this->imagePad > QrCode::getImageMaxPad() ) {
                $this->imagePad = QrCode::getImageMinPad();
            }
        }

        if ( $this->imageQuality == QrCodeLevel::UNKNOWN ) {
            $this->imageQuality = QrCodeLevel::getDefault();
        }

    }

    public static function getTemplate1( $id ) {
        $ret = '[' . self::TAG . ' ' . self::AUTH . "={$id}]";
        $ret .= 'content displayed to QR code scanners';
        $ret .= '[/' . self::TAG . ']';
        return $ret;
    }

    public static function getTemplate2( $id, $standard ) {
        $ret = '[' . self::TAG . ' ' . self::DISPLAY . "={$id}";
        if ( $standard ) {
            $ret .= ' ' . self::IMAGE . '="standard"';
        }
        $ret .= ']';
        return $ret;
    }


}