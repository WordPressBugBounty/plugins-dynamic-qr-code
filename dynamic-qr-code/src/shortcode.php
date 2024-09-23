<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );
use SOSIDEE_DYNAMIC_QRCODE\SosPlugin;

class Shortcode
{
    public const AUTH = 'auth';
    private const FORM = 'form';
    public const DISPLAY = 'display';
    private const SIZE = 'size';
    private const PAD = 'border';
    private const CSS_CLASS = 'class';
    public const IMAGE = 'image';
    public const TIMEOUT = 'timeout';
    public const FORECOLOR = 'forecolor';
    public const BACKCOLOR = 'backcolor';

    public const TAG = 'dynqrcode';

    public $mode;

    public $id;
    public $hasForm;
    public $imageSize;
    public $imagePad;
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
        $this->cssClass = null;
        $this->imageType = 'enhanced';
        $this->timeout = 0;
        $this->colorFore = '';
        $this->colorBack = '';

        $this->load($args);
    }


    private function load( $args ) {
        $plugin = SosPlugin::instance();

        $this->id = intval( $args[self::AUTH] ?? 0 );
        if ( $this->id > 0 ) {
            $this->mode = ShortcodeMode::HIDE_CONTENT;
        } else {
            $this->id = intval( $args[self::DISPLAY] ?? 0 );
            if ( $this->id > 0 ) {
                $this->mode = ShortcodeMode::DISPLAY_IMAGE;
            }
        }

        if ( $this->mode == ShortcodeMode::HIDE_CONTENT ) {
            $this->hasForm = isset($args[self::FORM]) && ( intval($args[self::FORM]) == 1 || strcasecmp( trim($args[self::FORM]), 'true') == 0 );
        } else if ( $this->mode == ShortcodeMode::DISPLAY_IMAGE ) {
            $this->imageSize = intval( $args[self::SIZE] ?? 0 );
            if ( $this->imageSize == 0 ) {
                $this->imageSize = $plugin->config->imgSize->getValue();
            }
            $this->imageSize = intval( $this->imageSize );
            $this->imagePad = intval( $args[self::PAD] ?? 0 );
            if ( $this->imagePad == 0 ) {
                $this->imagePad = $plugin->config->imgPad->getValue();
            }
            $this->imagePad = intval( $this->imagePad );
            $this->cssClass = $args[self::CSS_CLASS] ?? null;
            if ( isset($args[self::IMAGE]) && strtolower($args[self::IMAGE]) == 'standard' ) {
                $this->imageType = 'standard';
            }
            $this->timeout = intval( $args[self::TIMEOUT] ?? 0 );
            $this->colorFore = $args[self::FORECOLOR] ?? '';
            $this->colorBack = $args[self::BACKCOLOR] ?? '';
        }
    }

}