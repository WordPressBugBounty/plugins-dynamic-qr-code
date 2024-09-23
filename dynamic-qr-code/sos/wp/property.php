<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );
/**
 * USAGE *
 * 
 * class MyPluginClass
 * {
 *      use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Property
 *      {
 *          __get as __getProp;
 *          __set as __setProp;
 *      }
 *   
 *      public function __construct()
 *      {
 *          //initialize properties
 *          $this->_addProperty('key_1', 'value_1');
 *          $this->_addProperty('key_2', 'value_2');
 *      }
 * 
 *      //override specific properties
 *      public function __get($name)
 *      {
 *          switch($name)
 *          {
 *              case 'key_1': //specific case
 *                  $ret = $this->__getProp($name);
 *                  $ret = "do something here (while getting $ret)";
 *                  break;
 *              default:
 *                  $ret = $this->__getProp($name);
 *          }
 *          return $ret;
 *      }
 *      public function __set($name, $value)
 *      {
 *          switch($name)
 *          {
 *              case 'key_1': //specific case
 *                  $value = "do something here (while setting $value)" ;
 *                  $ret = $this->__setProp($name, $value);
 *                  break;
 *              default:
 *                  $ret = $this->__getProp($name);
 *          }
 *          return $ret;
 *      }
 * }
**/
trait Property
{
    protected $_properties = array();
    
    public function __get($name) {
        $ret = null;
        switch($name) {
            case '???': //caso particolare
                $ret = '???';
                break;
            default:
	            $ret = $this->_properties[$name];
        }
        return $ret;
    }

    public function __set($name, $value) {
        switch($name) {
            case '???': //caso particolare
                $this->_properties[$name] = $value;
                break;
            default:
                $this->_properties[$name] = $value;
        }
        return $this;
    }
    
    protected function _addProperty($name, $value = null) {
        $this->_properties[$name] = $value;
        return $this;
    }
    
    public static function checkId($value) {
        return str_replace(' ', '-', strtolower(trim($value)));
    }
    
}