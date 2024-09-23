<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Elementor;
use \Elementor as NativeElementor;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Class Widget
 * @package SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Elementor
 *
 * Helps to create widgets for Elementor
 *
 * The method setKey() must be overridden to define an unique name for the widget
 *
 * // icon list at https://elementor.github.io/elementor-icons/
 */
abstract class Widget extends NativeElementor\Widget_Base
{

    // custom properties
        protected $controls;

        public $section;

        public $key;
        public $title;
        public $icon;
        public $category;
    //

    protected static function plugin() {
        return \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
    }

    /**
     * Sets the widget key used by the get_name() method
     * Must be unique for each widget

     * Example:
        public function setKey()
        {
            $this->key = self::plugin()->key . '_unique_key';
        }
     */
    public abstract function setKey();

    /**
     * Sets the custom properties of the class
     */
    public function initialize() {
        $this->controls = array();
        $this->setKey();

        $this->section = new Section($this->key . '-sect' );

        $this->title = $this->key;
        $this->icon = 'eicon-star-o';
        $this->category = 'general';
    }

    public function get_name() {
        return $this->key;
    }

    public function get_title() {
        return $this->title;
    }

    public function get_icon() {
        return $this->icon;
    }

    public function get_categories() {
        return is_array($this->category) ? $this->category : [ $this->category ];
    }

    /**
     * Adds a SOS\WP\Elementor\Control to the widget
     *
     * @param string $title : label of the control
     * @param string $type : NativeElementor\Controls_Manager control type
     * @return Control : the control added to the widget
     */
    protected function addControl( $title, $type ) {
        $index = count($this->controls);
        $ret = new Control( $this->key . "-ctrl{$index}", $type );
        $ret->title = $title;
        $this->controls[] = $ret;
        return $ret;
    }

    private function _checkCustomProperties() {
        // it seems that sometimes the custom properties are reset by Elementor...
        if ( is_null($this->section) || is_null($this->controls) ) {
            $this->initialize();
        }
    }


    /**
     * Uses the custom properties to register the section and its controls
     */
    protected function _register_controls() {
        $this->_checkCustomProperties();

        $args = $this->section->getArgs();
        $this->start_controls_section( $this->section->key, $args );

        for ( $n=0; $n<count($this->controls); $n++ ) {
            $ctrl = $this->controls[$n];
            $args = $ctrl->getArgs();
            $this->add_control( $ctrl->key , $args );
        }

        $this->end_controls_section();
    }

    protected function render() {
        $this->_checkCustomProperties();
    }

}