<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Trait Asset
 * specific plugin files are always supposed to be in the '/assets/ext' subfolder of the plugin root, where ext is the file extension
 * library files are always supposed to be in the '/sos/wp/assets' subfolder of the plugin root
 */
trait TAsset
{
    use TMessage;


    private static function getPath() {
        return realpath(sosidee_check_path_separator(self::plugin()::$path . "/assets/"));
    }

    private static function getLibPath() {
        return realpath(sosidee_check_path_separator(self::plugin()::$path . "/sos/wp/assets/"));
    }

    public static function loadLibAsset( $filename, $removeBOM = true ) {
        $root = self::getLibPath();
        if ( $root === false) {
            sosidee_log("Assets library directory does not exist.");
            return false;
        }
        $file = realpath("$root/$filename");
        if ( $file === false ) {
            return false;
        }
        if ( !str_starts_with($file, $root) ) {
            sosidee_log("Unauthorized attempt to access the file: {$filename}");
            return false;
        }
        return FileSystem::read( $file, $removeBOM );
    }

    /**
     * @param $filename: with extension but without path
     * @return false|mixed|string
     */
    public static function loadAsset( $filename, $removeBOM = true ) {
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        $root = self::getPath();
        if ( $root === false) {
            sosidee_log("Assets directory does not exist.");
            return false;
        }
        $file = realpath("$root/$ext/$filename");
        if ( $file === false ) {
            $file = realpath("$root/$filename");
            if ( $file === false ) {
                return false;
            }
        }
        if ( !str_starts_with($file, $root) ) {
            sosidee_log("Unauthorized attempt to access the file: {$filename}");
            return false;
        }

        $ret = FileSystem::read($file, $removeBOM);
        if ( $ret === false ) {
            return false;
        }
        if ( $ext === 'json') {
            $decoded = json_decode($ret);
            $ret = $decoded !== null ? $decoded : $ret;
        }
        return  $ret;
    }

    /**
     * Enqueues scripts and styles
     * If running in the backend it checks if the current page is owned by the plugin
     * @param $handle
     * @param $src
     * @param $pages : array of the admin pages of the plugin
     */
    private function _addAsset( $handle, $src, $type, $in_footer = false ) {
        if ( !is_admin() ) {
            add_action( 'wp_enqueue_scripts', function() use ($handle, $src, $type, $in_footer) {
                if ( $type == 'script' ) {
                    wp_enqueue_script( $handle, $src, [], self::plugin()->version, $in_footer );
                } else if ( $type == 'style' ) {
                    wp_enqueue_style( $handle, $src, [], self::plugin()->version );
                }
            });
        } else {
            $plugin = self::plugin();
            add_action( 'admin_enqueue_scripts', function($hook) use ($handle, $src, $type, $in_footer, $plugin)  {
                if ( count($plugin->getPages()) > 0 ) {
                    $do_it = false;
                    foreach ( $plugin->getPages() as $page ) {
                        if ( $hook == $page->hook ) {
                            $do_it = true;
                            break;
                        }
                    }
                    if ( $do_it ) {
                        if ( $type == 'script' ) {
                            wp_enqueue_script( $handle, $src, [], $plugin->version, $in_footer );
                        } else if ( $type == 'style' ) {
                            wp_enqueue_style( $handle, $src, [], $plugin->version );
                        }
                    }
                } else {
                    self::msgWarn("Wanted to add an asset ($src) to the dashboard but no admin page has been created yet.");
                }
            });
        }
    }

    private function _addAssetToEditor( $handle, $src, $type, $in_footer = false ) {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', function() use ($handle, $src, $type, $in_footer) {
                $screen = get_current_screen();
                if ( $screen && $screen->base === 'post' && in_array($screen->post_type, ['post', 'page']) ) {
                    if ( $type == 'script' ) {
                        wp_enqueue_script( $handle, $src, [], self::plugin()->version, $in_footer );
                    } elseif ( $type == 'style' ) {
                        wp_enqueue_style( $handle, $src, [], self::plugin()->version );
                    } else {
                        sosidee_log("TAsset::_addAssetToEditor() - invalid type ('{$type}') for file: {$src}");
                    }
                }
            });
        }
    }

    private function _getAssetSrc( $path ) {
        return sosidee_str_remove( get_home_url(), self::plugin()::$url ) . $path;
    }

    /***
     * URL: use.fontawesome.com/releases/v5.6.3/css/all.css
     */
    public function addFontawesome() {
        if ( sosidee_is_dev() ) {
            $folder = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fa' . DIRECTORY_SEPARATOR;
            if ( realpath( $folder ) === false ) {
                $msg = "TAsset::addFontawesome() - Font Awesome folder does not exist: $folder";
                if ( is_admin() ) {
                    self::msgErr($msg);
                } else {
                    sosidee_log($msg);
                }
            }
        }
        $this->_addAsset('font-awesome', $this->_getAssetSrc('/sos/wp/assets/fa/css/all.css'), 'style' );
    }

    /**
     * INFO: https://fonts.google.com/icons
     */
    public function addGoogleIcons() {
        $this->_addAsset( 'material-icons', $this->_getAssetSrc('/sos/wp/assets/material-icons.css'), 'style' );
    }
    public function addGoogleIconsToEditor() {
        $this->_addAssetToEditor( 'material-icons', $this->_getAssetSrc('/sos/wp/assets/material-icons.css'), 'style' );
    }

    /**
     * INFO: https://sweetalert2.github.io/
     * icon: success | error | warning | info | question
     */
    public function addSweetAlert() {
        $this->_addAsset('sweet-alert', $this->_getAssetSrc('/sos/wp/assets/sweetalert2.all.min.js'), 'script' );
    }

    public function addFormJs() {
        $this->_addAsset('sos-form', $this->_getAssetSrc('/sos/wp/assets/sosForm.js'), 'script' );
    }

    public function addApiAjax() {
        $this->_addAsset('sos-api-ajax', $this->_getAssetSrc('/sos/wp/assets/sosApi.js'), 'script' );
    }

    /**
     * @param int $size 24|32|64
     * @return string
     *
     * generated by https://icons8.com/
     */
    public function getLoaderSrc( $size = 32 ) {
        return self::plugin()::$url . "/sos/wp/assets/loader$size.gif";
    }

    public static function addMediaLibrary() {
        wp_enqueue_media();
    }

    private static $_languages = null;
    public static function getLanguages( $caption = '' ) {
        if ( is_null(self::$_languages) ) {
            $content = self::loadLibAsset('language-codes.json');
            if ( $content !== false ) {
                $items = sosidee_json_decode($content);
                if ( is_array($items) ) {
                    foreach ($items as $item) {
                        self::$_languages[$item->alpha2] = $item->English;
                    }
                } else {
                    sosidee_log("TAsset::getLanguages() - JSON decoding failed for language-codes.json. Error: " . json_last_error_msg());
                    self::$_languages = [];
                    if ( !empty($caption) ) {
                        $caption = '- sorry, cannot load the language list -';
                    }
                }
            }
        }
        if ( is_null(self::$_languages) ) {
            self::$_languages = [];
        }
        if ( $caption != '' ) {
            return ['' => $caption] + self::$_languages;
        } else {
            return self::$_languages;
        }
    }

    public static function getLanguageDescription( $code ) {
        $ret = 'unknown';
        $items = self::getLanguages();
        if ( isset($items[$code]) ) {
            $ret = $items[$code];
        }
        return $ret;
    }

    private static $_countries = null;
    public static function getCountries( $caption = '' ) {
        if ( is_null(self::$_countries) ) {
            $content = self::loadLibAsset('country-codes.json');
            if ( $content !== false ) {
                $items = sosidee_json_decode($content);
                if ( is_array($items) ) {
                    foreach ($items as $item) {
                        self::$_countries[$item->Code] = $item->Name;
                    }
                } else {
                    sosidee_log("TAsset::getCountries() - JSON decoding failed for country-codes.json. Error: " . json_last_error_msg());
                    self::$_countries = [];
                    if ( !empty($caption) ) {
                        $caption = '- sorry, cannot load the Country list -';
                    }
                }
            }
        }
        if ( is_null(self::$_countries) ) {
            self::$_countries = [];
        }
        if ( $caption != '' ) {
            return ['' => $caption] + self::$_countries;
        } else {
            return self::$_countries;
        }
    }

    public static function getCountryDescription( $code ) {
        $ret = 'unknown';
        $items = self::getCountries();
        if ( isset($items[$code]) ) {
            $ret = $items[$code];
        }
        return $ret;
    }


}