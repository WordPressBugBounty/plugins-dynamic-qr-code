<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Adds functions for the internationalization of the plugin
 * It works with the file 'translations.php', located in the plugin folder
 */
trait Translation
{
    private static $translator = null;

    private static $translation_file = false;
    public static $internationalized = false;

    /**
     * Checks for the key defined in the file $translation_file
     * Handles placeholders with recursive translations
     *
     * @param string $key: key to be translated
     * @param array $args associative array: [placeholder => key_value] where key_value is a key to be translated or a value to substitute the placeholder)
     * @return string
     */
    public static function t_( $key, $args = null ) {
        $ret = $key;

        if ( self::$internationalized !== false ) {

            if ( $key != '' ) {
                $ret = require( self::$translation_file );
                if ( is_array($args) && count($args) > 0 ) {
                    foreach( $args as $k => $v ) {
                        $t = self::t_($v);
                        $ret = str_replace($k, $t, $ret);
                    }
                }
            }

        } else {
            if ( self::class !== 'SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Plugin' ) {
                if ( is_null(self::$translator) ) {
                    self::$translator = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
                }
                $ret = self::$translator::t_($key, $args);
            }
        }

        return $ret;
    }
    
    public static function te($key, $args = null) {
        echo sosidee_kses( self::t_($key, $args) );
    }

    /**
     * N.B. this function must be called only by a SOS\WP\Plugin object
     *
     * @param string $text_domain : Text Domain of the plugin
     */
    protected function internationalize( $text_domain ) {
        if ( $this instanceof Plugin ) {
            $folder = sosidee_dirname( plugin_dir_path( __FILE__ ), 2);
            $file = $folder . DIRECTORY_SEPARATOR . 'translations.php';
            if ( file_exists($file) ) {
                self::$translation_file = $file;
                self::$internationalized = true;
            }

            add_action( 'plugins_loaded', function() use ($text_domain, $folder) {
                load_plugin_textdomain( $text_domain, false, basename( $folder ) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR );
            });
        } else {
            trigger_error('The method SOS\WP\Translation::internationalize() is supposed to be called only by a SOS\WP\Plugin object.', E_USER_WARNING);
        }
    }
}