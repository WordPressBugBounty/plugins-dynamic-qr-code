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
        $namespacedKey = $this->key . '-' . $key;
        $ret = parent::addField( $namespacedKey, $title, $value, $type );
        $ret->name = $key;
        return $ret;
    }
    
    public function load() {
        foreach ( $this->fields as $field ) {
            $field->load();
        }
    }
    
    public function register() {
        parent::register();

        foreach ( $this->fields as $field ) {
            $field->validate = $this->validate;
            $field->register();
        }
    }
    
}