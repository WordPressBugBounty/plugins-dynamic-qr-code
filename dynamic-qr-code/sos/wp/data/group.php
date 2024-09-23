<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Data cluster
 * The values of all the fields are saved in the same record as associative array
 */
class Group extends Cluster
{

    private $_dbid = 0; // added when the trait Db has been converted to class

    private $loaded;

    protected $values;

    public $handled; // prevent a (automatic) double call when the record does not exist (and then it is being inserted)
    
    public function __construct( $key, $title ) {
        parent::__construct($key, $title);

        $this->_dbid = 0;

        $this->values = array();
        $this->handled = false;

        $this->loaded = false;
    }

    public function addField($key, $title, $value = null, $type = FieldType::TEXT) {
        $name = $key;
        $key = strtolower(trim($key));
        $ret = parent::addField($key, $title, $value, $type);
        $ret->name = $name;
        $this->values[$key] = $value;
        return $ret;
    }
    
    public function load() {
        if ( !$this->loaded ) {
            $results = get_option($this->key, $this->values);
            if ( is_array($results) ) {
                $this->values = $results;
                for ( $n=0; $n<count($this->fields); $n++ ) {
                    $field = $this->fields[$n];
                    if ( key_exists( $field->key, $this->values ) ) {
                        $value = $this->values[$field->key];
                        $field->setValue( $value );
                    }
                }
            }
            $this->loaded = true;
        }
    }

    /**
     * $input is an associative array of elements field_key => field_value
     */
    public function callback( $inputs ) {
        if ( !is_null($this->validate) ) {
            if ( !$this->handled ) {
                $results = call_user_func( $this->validate, $this->key, $inputs );

                if ( $this->encrypted ) { //at least one field is encrypted
                    foreach ( $results as $key => $value ) {
                        $field = $this->getField($key);
                        if ( !is_null($field) && $field->encrypted ) {
                            $results[$field->key] = $field->encrypt( $value );
                        }
                    }
                }

                $this->handled = true; // so it won't be handled anymore
                return $results;
            } else {
                return $inputs;
            }
        }
    }

    public function register() {
        parent::register();

        $callback = is_null($this->validate) ? null : [ "sanitize_callback" => array($this, 'callback') ];
        
        register_setting(
             $this->key
            ,$this->key
            ,$callback
        );

    }

    public function loadFields( $data ) {
        $count = 0;
        foreach ($data as $key => $value) {
            $index = $this->getFieldIndex($key);
            if ( $index !== false ) {
                $field = $this->fields[$index];
                $field->setValue( $value );
                $count++;
            }
        }
        return $count == count($this->fields);
    }

    public function loadByKey($data = false) {
        if ( !is_array($data) ) {
            global $wpdb;
            $data = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name={$this->key}", ARRAY_A );
        }
        $ret = false;
        if ( is_array($data) ) {
            $ret = true;
            foreach ($data as $key => $value) {
                $index = $this->getFieldIndex($key);
                if ( $index !== false ) {
                    $field = $this->fields[$index];
                    $field->setValue( $value );
                } else {
                    $ret = false;
                }
            }
        }
        return $ret;
    }

    /**
     * Retrieve the record id in 'options' table using the object key
     *
     * @return  integer : record id
     *          boolean : false in case of error
     */
    public function getDbId() {
        $ret = $this->_dbid;
        if ( $ret == 0 ) {
            global $wpdb;
            $sql = "SELECT option_id FROM {$wpdb->options} WHERE option_name=%s";
            $query = $wpdb->prepare( $sql, $this->key );
            $result = $wpdb->get_var( $query );
            if ( $result ) {
                $this->_dbid = intval( $result );
                $ret = $this->_dbid;
            } else {
                $ret = false;
            }
        }
        return $ret;
    }

}