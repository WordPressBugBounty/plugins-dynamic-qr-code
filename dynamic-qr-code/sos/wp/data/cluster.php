<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

/**
 * Base class for the data classes 'Section' and 'Group'
 * Data are saved in the 'options' table of the database
 */
class Cluster
{
    use SOS_WP\TBase;
    use SOS_WP\TTranslation;

    public $key;
    public $title;
    public $description;
    public $encrypted;

    public $fields;

    public $page;
    public $validate;

    public function __construct( $key, $title ) {
        $this->key = sosidee_check_id($key);

        $this->title = $title;

        $this->description = '';
        $this->encrypted = false;

        $this->page = '';
        $this->validate = null;
        $this->fields = [];
    }

    private function createField( $key, $title, $value, $type ) {
        if ( $type == FieldType::COMBOBOX ) {
            self::plugin()->addFormJs();
        }
        return Field::create( $this, $key, $title, $value, $type );
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
    public function addField( $key, $title, $value = null, $type = FieldType::TEXT ) {
        $field = $this->createField( $key, $title, $value, $type );
        $this->fields[] = $field;
        return $field;
    }

    public function insertField( $index, $key, $title, $value = null, $type = FieldType::TEXT ) {
        $field = $this->createField( $key, $title, $value, $type );
        $index = max(0, $index);
        if ( $index < count($this->fields) ) {
            array_splice($this->fields, $index, 0, [$field]);
        } else {
            $this->fields[] = $field;
        }
        return $field;
    }

    protected function getFieldIndex( $key ) {
        $ret = false;
        foreach ( $this->fields as $index => $field ) {
            if ( $field->key === $key ) {
                $ret = $index;
                break;
            }
        }
        return $ret;
    }

    public function getField( $key ) {
        $ret = null;
        foreach ( $this->fields as $field ) {
            if ( $field->key === $key ) {
                $ret = $field;
                break;
            }
        }
        return $ret;
    }

    /**
     * Assignes an admin page to the data cluster (or, if you prefer, assignes a data cluster to an admin page)
     * 
     * @param string | SOS_WP\BE\Page $page : ID of the page or the page itself
     * 
     */
    public function setPage( $page ) {
        $this->page = ($page instanceof SOS_WP\BE\Page) ? $page->key : (string) $page;
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

        foreach ( $this->fields as $field ) {
            $field->translate();
        }
    }

    protected function initialize() {
        if ( self::plugin()::$internationalized ) {
            $this->translate();
        }

        $me = $this;
        $callback = $this->description != '' ? function() use ($me) {
                echo wp_kses( $me->description, sosidee_allowed_html() );
            } : null;

        add_settings_section(
                 $this->key
                ,$this->title
                ,$callback
                ,$this->page
            );
    }
    
    public function register() {

        $this->initialize();
        foreach ( $this->fields as $field ) {
            if ( $field->encrypted ) {
                $this->encrypted = true;
            }
            $field->initialize();
        }
    }
    
    public function html( $no_submit = false ) {
        $this->load();

        settings_fields( $this->key );
        do_settings_sections( $this->page );
        
        if ( !$no_submit ) {
            submit_button();
        }
    }
    
}