<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Elementor;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Class Control
 * Represents a widget control
 * Properties are used to register a control in an Elementor widget
 *
 * @package SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Elementor
 */
class Control
{
    public $key;
    public $title;
    public $type; // NativeElementor\Controls_Manager
    public $description;
    public $placeholder;
    public $default;
    public $options;
    public $text;

    public function __construct( $key, $type ) {
        $this->key = $key;
        $this->type = $type;
        $this->title = $key;
        $this->description = '';
        $this->placeholder = '';
        $this->default = '';
        $this->options = array();
        $this->text = '';
    }

    public  function getArgs() {
        $ret = array();
        if ( $this->title != '' ) {
            $ret['label'] = $this->title;
        }
        if ( $this->type != '' ) {
            $ret['type'] = $this->type;
        }
        if ( $this->description != '' ) {
            $ret['description'] = $this->description;
        }
        if ( $this->text != '' ) {
            $ret['text'] = $this->text;
        }
        if ( $this->placeholder != '' ) {
            $ret['placeholder'] = $this->placeholder;
        }
        if ( $this->default != '' ) {
            $ret['default'] = $this->default;
        }
        if ( count($this->options) > 0 ) {
            $ret['options'] = $this->options;
        }
        return $ret;
    }

}