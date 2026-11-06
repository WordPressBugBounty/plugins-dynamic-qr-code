<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TCluster
{
    private $native; // Cluster

    public function addFieldNumber( $key, $title, $value = null, $min = 0, $max = PHP_INT_MAX, $step = 1 ) {
        $field = $this->native->addField( $key, $title, $value, FieldType::NUMBER );
        $field->min = $min;
        $field->max = $max;
        $field->step = $step;
    }
    public function insertFieldNumber( $index, $key, $title, $value = null, $min = 0, $max = PHP_INT_MAX, $step = 1 ) {
        $field = $this->native->insertField( $index, $key, $title, $value, FieldType::NUMBER );
        $field->min = $min;
        $field->max = $max;
        $field->step = $step;
    }

    public function addFieldText( $name, $label, $default = '' ) {
        return $this->native->addField( $name, $label, $default, FieldType::TEXT );
    }
    public function insertFieldText( $index, $name, $label, $default = '' ) {
        return $this->native->insertField( $index, $name, $label, $default, FieldType::TEXT );
    }

    public function addFieldCheck( $name, $label, $default = false ) {
        return $this->native->addField( $name, $label, $default, FieldType::CHECK );
    }
    public function insertFieldCheck( $index, $name, $label, $default = false ) {
        return $this->native->insertField( $index, $name, $label, $default, FieldType::CHECK );
    }

    public function addFieldColor( $key, $title, $value = null ) {
        return $this->native->addField( $key, $title, $value, FieldType::COLOR );
    }
    public function insertFieldColor( $index, $key, $title, $value = null ) {
        return $this->native->insertField( $index, $key, $title, $value, FieldType::COLOR );
    }

    public function addFieldSelect( $key, $title, $value = '' ) {
        return $this->native->addField( $key, $title, $value, FieldType::SELECT );
    }
    public function insertFieldSelect( $index, $key, $title, $value = '' ) {
        return $this->native->insertField( $index, $key, $title, $value, FieldType::SELECT );
    }

    public function addFieldCombo( $key, $title, $value = '' ) {
        return $this->native->addField( $key, $title, $value, FieldType::COMBOBOX );
    }
    public function insertFieldCombo( $index, $key, $title, $value = '' ) {
        return $this->native->insertField( $index, $key, $title, $value, FieldType::COMBOBOX );
    }

    public function addFieldLabel( $name, $label, $default = '' ) {
        return $this->native->addField( $name, $label, $default, FieldType::LABEL );
    }
    public function insertFieldLabel( $index, $name, $label, $default = '' ) {
        return $this->native->insertField( $index, $name, $label, $default, FieldType::LABEL );
    }


    public function getField($key) {
        return $this->native->getField($key);
    }

    public function load() {
        $this->native->load();
    }

    public function html() {
        if ( is_callable( [ $this, 'initialize' ] ) ) {
            $this->initialize();
        } else {
            sosidee_log("TCluster::html() - method initialize() is not callable.");
        }
        $this->native->html();
    }

}