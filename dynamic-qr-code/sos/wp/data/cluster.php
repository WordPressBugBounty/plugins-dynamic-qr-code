<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Base class for the data classes 'Section' and 'Group'
 * Data are saved in the 'options' table of the database
 */
class Cluster
{
    use SOS_WP\Property {
        SOS_WP\Property::__set as __setProp;
    }
    use SOS_WP\Translation;

    protected static $plugin = null;

    public $title;
    public $description;
    public $encrypted;

    public $fields;

    public $page;
    public $validate;

    public function __construct($key, $title) {
        $this->_addProperty('key', '');
        $this->key = $key;

        $this->title = $title;

        $this->description = '';
        $this->encrypted = false;

        $this->page = '';
        $this->validate = null;
        $this->fields = array();

        if ( is_null(self::$plugin) ) {
            self::$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
        }
    }

    public function __set($name, $value) {
        switch ($name) {
            case 'key':
                $value = self::checkId($value);
                break;
        }
        return $this->__setProp($name, $value);
    }

    /**
     * Creates a data field and adds it to the fields array
     * 
     * @param string $name : (unique) ID of the field
     * @param string $title : title (used for the label)
     * @param string $value: default value
     * @param FieldType $type : type of layout (form control)
     * 
     * $return a Field object
     */
    public function addField( $name, $title, $value = null, $type = FieldType::TEXT ) {
        $field = Field::create($this, $name, $title, $value, $type);
        $this->fields[] = $field;
        return $field;
    }

    public function addFieldNumber( $name, $title, $value = null, $min = 0, $max = PHP_INT_MAX, $step = 1 ) {
        $ret = $this->addField( $name, $title, $value, FieldType::NUMBER );
        $ret->min = $min;
        $ret->max = $max;
        $ret->step = $step;
        return $ret;
    }

    public function addFieldColor( $name, $title, $value = null ) {
        return $this->addField( $name, $title, $value, FieldType::COLOR );
    }

    public function addFieldSelect( $name, $title, $value = null ) {
        return $this->addField( $name, $title, $value, FieldType::SELECT );
    }

    protected function getFieldIndex( $key ) {
        $ret = false;
        for ( $n=0; $n<count($this->fields); $n++ ) {
            $field = $this->fields[$n];
            if ( $field->key == $key ) {
                $ret = $n;
                break;
            }
        }
        return $ret;
    }

    public function getField( $key ) {
        $ret = null;
        for ( $n=0; $n<count($this->fields); $n++ ) {
            $field = $this->fields[$n];
            if ( $field->key == $key ) {
                $ret = $field;
                break;
            }
        }
        return $ret;
    }

    /**
     * Assignes an admin page to the data cluster (or, if you prefer, assignes a data cluster to an admin page)
     * 
     * @param string | BE\Page $page : ID of the page or the page itself
     * 
     */
    public function setPage( $page ) {
        if ( is_string($page) ) {
            $this->page = $page;
        } else {
            $this->page = $page->key;
        }
    }

    /**
     * Loads the fields value from the database
     */
    public function load() {
        // it will be overridden by the inherited classes
    }

    protected function translate() {
        $this->title = self::t_( $this->title );
        $this->description = self::t_( $this->description );
        
        for ( $n=0; $n<count($this->fields); $n++ ) {
            $field = $this->fields[$n];
            $field->translate();
        }
    }

    protected function initialize() {
        $plugin = self::$plugin;
        if ( $plugin::$internationalized ) {
            $this->translate();
        }

        $callback = $this->description != '' ? function() { echo sosidee_kses($this->description); } : null;

        add_settings_section(
                 $this->key
                ,$this->title
                ,$callback
                ,$this->page
            );
    }
    
    public function register() {

        $this->initialize();
        for ( $n=0; $n<count($this->fields); $n++ ) {
            $field = $this->fields[$n];
            if ( $field->encrypted ) {
                $this->encrypted = true;
            }
            $field->initialize();
        }
    }
    
    public function html($no_submit = false) {
        $this->load();

        settings_fields( $this->key );
        do_settings_sections( $this->page );
        
        if ( !$no_submit ) {
            submit_button();
        }
    }
    
}