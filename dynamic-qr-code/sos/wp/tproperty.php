<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * HOW TO: override specified properties
 *      use \NAMESPACE_ROOT\SOS\WP\TProperty {
 *          __get as _getProp;
 *          __set as _setProp;
 *      }
 *      public function __get( $name ) {
 *          switch( $name ) {
 *              case 'key_1': //specific case
 *                  $ret = $this->_getProp( $name );
 *                  return "do something here with $ret";
 *              default:
 *                  return $this->_getProp( $name );
 *          }
 *      }
 *      public function __set( $name, $value ) {
 *          switch( $name ) {
 *              case 'key_2': //specific case
 *                  $value = "do something else with $value";
 *                  return $this->_setProp( $name, $value );
 *              default:
 *                  return $this->_setProp( $name, $value );
 *          }
 *      }
**/

trait TProperty
{
    private $_dynamic_properties = [];

    public function __set( $name, $value ) {
        $this->_dynamic_properties[$name] = $value;
    }

    public function __get( $name ) {
        return $this->_dynamic_properties[$name] ?? null;
    }

    public function __isset( $name ) {
        return isset( $this->_dynamic_properties[$name] );
    }

    public function __unset( $name ) {
        unset( $this->_dynamic_properties[$name] );
    }
    
}