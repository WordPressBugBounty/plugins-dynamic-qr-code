<?php
/**
 * 
 * THIS FILE MUST BE LOCATED IN THE PLUGIN FOLDER *
 * 
**/
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

if( !class_exists('SOSIDEE_CLASS_LOADER') ) {
    /**
     * Class autoloader
     * It works for all sosidee's plugins installed
     * Class namespaces are transformed into a folder structure
     */
    class SOSIDEE_CLASS_LOADER
    {
        
        public function load( $class ) {
            $items = explode('\\', $class);
            if ( key_exists($items[0], $this->roots) ) { //check if the class namespace root has been added (non-sosidee's plugins exit here)
                $items[0] = $this->roots[ $items[0] ];
                $class_path = implode(DIRECTORY_SEPARATOR, $items);
                $file_path = plugin_dir_path( __DIR__ ) . strtolower($class_path) . '.php';
                $file_path = realpath( $file_path );
                if ( $file_path !== false ) {
                    require_once $file_path;
                }
            }
        }

        public function add( $namespace, $dir ) {
            $this->roots[$namespace] = basename( $dir );
            $func_file = $dir . '/sos/wp/functions';
            self::requireFile($func_file);
        }

        //just the usual way to get a singleton
        private static $instance = null;
        final public static function instance() {
            if (self::$instance == null) {
                self::$instance = new \SOSIDEE_CLASS_LOADER();

                spl_autoload_register( array(self::$instance, 'load') );

            }

            return self::$instance;
        }

        private $roots;
        private function __construct() {
            $this->roots = array();
        }

        private static function requireFile( $filepath ) {
            $file = implode( DIRECTORY_SEPARATOR, explode( '/', $filepath ) );
            if ( substr( $file, -strlen('.php') ) !== '.php' ) {
                $file .= '.php';
            }
            $file = realpath( $file );
            if ( $file !== false ) {
                require_once $file;
            }
        }

    }
}
