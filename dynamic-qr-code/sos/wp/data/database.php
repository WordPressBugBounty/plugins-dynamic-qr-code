<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Database
{
    private $_native;

    public function __construct( $prefix ) {

        $this->_native = new WpDatabase( $prefix );

    }

    private function createTables() {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ( $properties as $property ) {
            $propertyName = $property->getName();
            if ( str_starts_with($propertyName, '_') ) {
                continue;
            }
            $propertyValue = $property->getValue($this);
            if ( !is_object($propertyValue) ) {
                continue;
            }
            if ( !($propertyValue instanceof Table ) ) {
                continue;
            }
            $propertyValue->create( $this->_native );
        }
    }

    public function create() {
        $this->createTables();
        $this->_native->create();
    }

    public function tableExists( $name, $without_sos_prefix = false ) {
        return $this->_native->tableExists( $name, $without_sos_prefix );
    }

}