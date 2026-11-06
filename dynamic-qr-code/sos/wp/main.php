<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Main
{
    use Tmessage; //TBase,

    //protected $key;
    protected static $native = null;

    //protected $versionMin;
    protected $compatible;

    //protected $installed; // questa proprietà non ha ragione di esistere
    //protected $activated; // questa proprietà non ha ragione di esistere

    public function __construct() {
        //$this->key = $key;

        //$this->installed = false;
        //$this->activated = false;
        $this->compatible = false;

        //$this->versionMin = '';
    }

    /*
    public function isInstalled() {
        return $this->installed;
    }

    public function isActivated() {
        return !is_null(self::$native) && $this->activated;
    }
    */

    public function isCompatible() {
        return $this->compatible;
    }

    public function getDatabase() {
        return self::$native->getDatabase();
    }


    /*
    public function isReady() {
        return $this->isCompatible();
    }
    */

    public function getKey() {
        if ( !is_null( self::$native ) ) {
            return self::$native->key;
        } else {
            //log
            return '';
        }
    }

    public function getName() {
        if ( !is_null( self::$native ) ) {
            return self::$native->name;
        } else {
            //log
            return '';
        }
    }

    public function getVersion() {
        if ( !is_null( self::$native ) ) {
            return self::$native->version;
        } else {
            //log
            return '';
        }
    }

    // usata in TAddon::checkVersionCompatibility()
    public function setNative( $class ) {
        if ( class_exists($class, false) ) {
            self::$native = $class::instance();
        } else {
            //log
        }
    }

    // usata in TAddon::checkVersionCompatibility()
    public function setCompatible( $value ) {
        $this->compatible = $value;
    }

    /*
    public function checkPlugin1( $infos, $class ) {
        if ( !class_exists($class) ) {
            sosidee_log("Main::checkPlugin() - class '$class' does not exist.");
            return [
                'error' => true
            ];
        }
        if ( !method_exists($class, 'instance') ) {
            sosidee_log("Main::checkPlugin() - method 'instance()' does not exist for the class '$class'.");
            return [
                'error' => true
            ];
        }
        self::$native = $class::instance();
        //$this->key = self::$native->key;

        $plugin = self::plugin(); // plugin addon

        $this->compatible = version_compare(self::$native->version, $plugin->versionMin, '>=');

        return [
             'addon_name' => $plugin->name
            ,'addon_version' => $plugin->version
            ,'addon_basename' => $plugin->basename
            ,'main_compatible' => $this->compatible
            ,'main_version_min' => $plugin->versionMin
        ];
    }
    */

    public static function database() {
        if ( !is_null( self::$native ) ) {
            return self::$native::database();
        } else {
            //log
            return null;
        }
    }

    public function getStyle( $file ) {
        if ( !is_null( self::$native ) ) {
            return self::$native->getStyle( $file );
        } else {
            //log
            return null;
        }
    }

    public function getScript( $file ) {
        if ( !is_null( self::$native ) ) {
            return self::$native->getScript( $file );
        } else {
            //log
            return null;
        }
    }

}