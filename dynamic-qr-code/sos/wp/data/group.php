<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Data cluster
 * The values of all the fields are saved in the same record as associative array
 */
class Group extends Cluster
{
    private $loaded;

    protected $values;

    public $handled; // prevents the (automatic) double call when the record does not exist (and then it is being inserted)
    
    public function __construct( $key, $title ) {
        parent::__construct($key, $title);

        $this->values = array();
        $this->handled = false;

        $this->loaded = false;
    }

    public function addField( $key, $title, $value = null, $type = FieldType::TEXT ) {
        $ret = parent::addField( $key, $title, $value, $type );
        $this->values[$ret->key] = $value;
        return $ret;
    }
    
    public function load() {
        if ( $this->loaded ) {
            return true;
        }
        $results = get_option($this->key);
        if ( is_array($results) ) {
            foreach ( $this->fields as $field ) {
                $key = $field->key;
                if ( array_key_exists( $key, $results ) ) {
                    $value = $field->getReadableValue( $results[$key] );
                    $this->values[$key] = $value;
                    $field->setValue( $value );
                    $field->loaded = true;
                }
            }
        }
        $this->loaded = true;
    }

    /**
     * $input is an associative array of elements field_key => field_value
     */
    public function callback( $inputs ) {
        if ($this->handled) {
            return $inputs;
        }
        $this->handled = true;

        $results = $inputs;
        if ( is_callable($this->validate) ) {
            $results = call_user_func($this->validate, $this->key, $inputs);
        }

        $writableValues = [];
        if ( is_array($results) ) {
            foreach ( $results as $key => $value ) {
                $field = $this->getField($key);
                if ( $field instanceof Field ) {
                    $writableValues[$key] = $field->getWritableValue($value);
                }
            }
        }

        return $writableValues;
    }

    public function sanitize($input) {
        $input = sosidee_sanitize_recursive($input);
        return $this->callback($input);
    }

    public function register() {
        parent::register();

        register_setting(
             $this->key
            ,$this->key
            ,['sanitize_callback' => array($this, 'sanitize')]
        );

    }

    public function loadFields( $data ) {
        $count = 0;
        foreach ( $data as $key => $value ) {
            $index = $this->getFieldIndex($key);
            if ( $index !== false ) {
                $field = $this->fields[$index];
                $field->setValue( $value );
                $count++;
            }
        }
        return $count == count($this->fields);
    }

}