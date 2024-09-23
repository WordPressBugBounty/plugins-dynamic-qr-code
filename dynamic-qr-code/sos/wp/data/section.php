<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Data cluster
 * Each field value is saved in a different record
 */
class Section extends Cluster
{
    
    public function addField( $key, $title, $value = null, $type = FieldType::TEXT ) {
        $name = $key;
        $key = $this->key . '-' . strtolower(trim($key));
        $ret = parent::addField($key, $title, $value, $type);
        $ret->name = $name;
        return $ret;
    }
    
    public function load() {
        for ($n=0; $n<count($this->fields); $n++) {
            $this->fields[$n]->load();
        }
    }
    
    public function register() {
        parent::register();

        for ( $n=0; $n<count($this->fields); $n++ ) {
            $field = $this->fields[$n];
            $field->validate = $this->validate;
            $field->register();
        }
    }
    
}