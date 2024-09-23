<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );
/**
 * USAGE *
 * 
 * class MyPluginClass
 * {
 *      use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Message;
 *   
 *      public function __construct()
 *      {
 *          //optional: insert this command in plugin class after having set the plugin key or name
 *          self::initializeMessage($this);
 *      }
 * }
**/
trait Message
{
    private static $plugin = null;

    protected static $setting = 'sosidee';
    public static function initializeMessage($parent) {
        self::$setting = $parent->key;
    }

    private static function translate($message) {
        if ( is_null(self::$plugin) ) {
            self::$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
        }
        $plug = self::$plugin;
        if ( $plug::$internationalized ) {
            return $plug::t_($message);
        } else {
            return $message;
        }
    }
    
    public static function _add($message, $type, $handled) {
        if ( !$handled ) {
            $message = self::translate($message);
            add_settings_error( self::$setting, '666', $message, $type );
        } else {
            add_action( 'admin_notices', function() use($message, $type) {
                $message = self::translate($message);
                add_settings_error( self::$setting, '666', $message, $type );
            } );
        }
    }
    
    public static function msgInfo($message, $handled = false) {
        self::_add($message, 'info', $handled);
    }
    public static function msgErr($message, $handled = false) {
        self::_add($message, 'error', $handled);
    }
    public static function msgOk($message, $handled = false) {
        self::_add($message, 'success', $handled);
    }
    public static function msgWarn($message, $handled = false) {
        self::_add($message, 'warning', $handled);
    }

    public static function msgHtml() {
        \settings_errors();
    }
    
}