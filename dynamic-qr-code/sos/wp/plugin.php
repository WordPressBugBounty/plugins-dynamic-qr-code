<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
use Elementor as NativeElementor;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Base class for the plugins
 *
 * @property $key : used in the database and for the slugs
 *
 */
class Plugin
{
    use TMessage, TAsset, TTranslation; //, TAddonPLM;

    protected static $helpUrl = 'https://support.sosplugin.com/';

    private $localizedScriptHandles;
    private $inlineScriptHandles;
    private $deActivationEnabled;

    private $updater;

    protected $menu;
    protected $pages;
    protected $clusters;
    protected $scripts;
    protected $styles;
    protected $shortcodes;
    protected $metaboxes;
    protected $elementorWidgets; //array that contains the class names of the custom widgets for Elementor
    protected $endpoints;

    protected $dashLinks;
    protected $customUpdate;

    protected $infoData;

    protected $database;

    protected $filepath;
    protected $folder;
    public $basename;


    public $key;
    public $name;
    public $version;

    public $gutenbergEnabled;

    public $qsArgs; // array of (HTTP GET) custom query variables to be enabled

    public static $path = '';
    public static $url = '';

    public $isProOld;
    //public $plmVersionMin;

    protected function __construct( $_file = '' ) {
        $this->isProOld = false;
        $this->infoData = array();

        $this->setKey('plugin');
        $this->name = 'SOS Plugin';
        $this->version = '';

        $this->localizedScriptHandles = array();
        $this->inlineScriptHandles = array();
        $this->deActivationEnabled = 0;
        $this->updater = null;
        //$this->proAddon = null;
        $this->database = null;

        $this->menu = null;
        $this->pages = array();
        $this->clusters = array();
        $this->scripts = array();
        $this->styles = array();
        $this->shortcodes = array();
        $this->metaboxes = array();
        $this->elementorWidgets = array();
        $this->endpoints = array();
        $this->gutenbergEnabled = false;

        $this->qsArgs = array();

        $this->dashLinks = array();
        $this->customUpdate = false;

        //$this->resetAddons(); da TAddonPLM    // eliminare
        //$this->plmVersionMin = '1.0.0';       // eliminare

        if ( empty($_file) ) {
            self::$path = sosidee_check_path_separator( sosidee_dirname( plugin_dir_path( __FILE__ ) , 2) ); // without the last separator
            self::$url = sosidee_dirname( plugin_dir_url( __FILE__ ) , 2);
            $this->basename = null;
            $this->folder = basename(self::$path);
            $this->filepath = null;
        } else {
            self::$path = sosidee_check_path_separator(untrailingslashit(plugin_dir_path($_file)));
            self::$url = untrailingslashit(plugin_dir_url($_file));
            $this->basename = plugin_basename($_file);
            $this->folder = basename(self::$path);
            $this->filepath = $_file;
        }
    }

    private static $_instances = array();
    final public static function instance() {
        $calledClass = get_called_class();
        if ( !isset( self::$_instances[$calledClass] ) ) {
            self::$_instances[$calledClass] = new $calledClass();
        }
        return self::$_instances[$calledClass];
    }

    protected function checkKey( $value ) {
        $value = str_replace( '_', '-', sosidee_check_id($value) );
        if ( !sosidee_str_starts_with($value, 'sos') ) {
            $value = 'sos-' . ltrim( $value, '-');
        }
        return $value;
    }
    protected function setKey( $value ) {
        $this->key = $this->checkKey($value);
    }

    public function isPro() { return false; }

    public function checkAddon() {
        return false;
    }

    public function hasDatabase() {
        return !is_null($this->database);
    }
    public function getDatabase() {
        return $this->database;
    }

    /***
     * Enables plugin events: activation a/o deactivation event(s)
     * onActivation() a/o onDeactivation() plugin function(s) will be called
     * @param $value string 'activation' | 'deactivation' | 'both'
     * @return void
     */
    protected function enableEvent( $value ) {
        $value = strtolower( trim( $value ) );
        if ( strlen($value) > 0 ) {
            $value = substr( $value, 0, 1 );
            if ( $value == 'a' ) {
                $this->deActivationEnabled = 2;
            } elseif ( $value == 'd' ) {
                $this->deActivationEnabled = 3;
            } elseif ( $value == 'b' ) {
                $this->deActivationEnabled = 6;
            }
        }
    }

    /**
     * Creates and adds a backend page located in the 'admin' folder
     * 
     * @param string $file : just the filename, without path
     * 
     * @return  BE\Page object
     */
    protected function addPage( $file, $name = '' ) {
        $path = self::$path . DIRECTORY_SEPARATOR . 'admin';
        if ( !sosidee_str_starts_with($file, DIRECTORY_SEPARATOR) ) {
            $path .= DIRECTORY_SEPARATOR;
        }
        $path .= $file;
        $page = new BE\Page($path, $name);
        $page->key = $this->key . '-' . count($this->pages);
        $this->pages[] = $page;
        return $page;
    }

    public function getPages() {
        return $this->pages;
    }

    /**
     * Creates and adds a metabox with:
     *      - screen = post and page
     *      - context = normal
     * 
     * @param $key : (unique) ID of the metabox
     * @param $title : title of the metabox
     * 
     * @return  MetaBox object
     */
    protected function addMetaBox( $key, $title, $screen = ['post', 'page'], $context = 'normal', $priority = 'high', $compatible = true ) {
        $key = $this->key . '-mb_' . sosidee_check_id($key);
        $ret = new MetaBox( $key, $title, $screen, $context, $priority, $compatible );
        $this->metaboxes[] = $ret;
        return $ret;
    }

    private $fixCanonicalUrlRedirect = true;
    private function addCluster( $key, $title, $class ) {
        $key = $this->key . '_' . $key;
        $cluster = new $class( $key, $title );
        $this->clusters[] = $cluster;
        if ( $this->fixCanonicalUrlRedirect ) {
            add_action( 'admin_init', [ $this, 'fixCanonicalUrlRedirect' ] );
            $this->fixCanonicalUrlRedirect = false;
        }
        return $cluster;
    }
    public function fixCanonicalUrlRedirect() {
        if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
            remove_action( 'admin_head', 'wp_admin_canonical_url' );
        }
    }

    protected function addSection( $key, $title = '' ) {
        return $this->addCluster( $key, $title, DATA\Section::class );
    }
    protected function addGroup( $key, $title ) {
        return $this->addCluster( $key, $title, DATA\Group::class );
    }

    /*
    protected function getCluster( $key ) {
        $ret = null;
        foreach ( $this->clusters as $cluster ) {
            if ( $cluster->key == $key ) {
                $ret = $cluster;
                break;
            }
        }
        return $ret;
    }
    private function getClusterIndex( $key ) {
        $ret = -1;
        foreach ( $this->clusters as $index => $cluster ) {
            if ( $cluster->key == $key ) {
                $ret = $index;
                break;
            }
        }
        return $ret;
    }
    */

    protected function addStyle( $file ) {
        $key = $this->key . '-' . count($this->styles);
        $style = new Style($key, $file);
        $this->styles[] = $style;
        return $style;
    }

    protected function addStyleInline( $code ) {
        $key = $this->key . '-' . time();
        wp_register_style( $key, false, [], $this->version );
        wp_enqueue_style( $key );
        wp_add_inline_style( $key, $code );
    }

    public function getStyle( $file ) {
        foreach ( $this->styles as $style ) {
            if ( $style->getFile() == $file ) {
                return $style;
            }
        }
        return null;
    }


    protected function addScript( $file, $dependency = true, $in_body = false ) {
        $key = $this->key . '-' . count($this->scripts);

        if ( is_bool( $dependency ) ) {
            $dependencies = $dependency ? array('jquery') : array();
        } elseif ( is_string( $dependency ) ) {
            $dependencies = array($dependency);
        } else {
            $dependencies = $dependency;
        }

        $script = new Script($key, $file, $dependencies, $in_body);
        $this->scripts[] = $script;
        return $script;
    }

    protected function addInlineScript( $code, $handle = '-inline', $before = false ) {
        $single = true;
        $position = 'after';
        foreach ( $this->scripts as $script ) {
            if ( $script->key === $handle ) {
                $single = false;
                if ( $before ) {
                    $position = 'before';
                }
                break;
            }
        }
        if ( $single ) {
            $handle = $this->key . $handle;
        }
        if ( !in_array($handle, $this->inlineScriptHandles) ) {
            wp_register_script( $handle, '', ['jquery'], $this->version, true );
            wp_enqueue_script( $handle );
            $this->inlineScriptHandles[] = $handle;
        }
        wp_add_inline_script( $handle, $code, $position );
    }

    protected function addFooterScript( $content, ...$pages ) {
        $hook = is_admin() ? 'admin_footer' : 'wp_footer';
        $printer = function() use ($content, $pages) {
            $add = empty($pages);
            if ( !$add ) {
                foreach ( $pages as $page ) {
                    if ( $page instanceof BE\Page &&  $page->isCurrent() ) {
                        $add = true;
                        break;
                    }
                }
            }
            if ( !$add ) {
                return;
            }
            $code = is_callable($content) ? (string) call_user_func($content) : (string) $content;
            $code = trim($code);
            if ( $code === '' ) { return; }
            wp_print_inline_script_tag($code);
        };
        if ( doing_action($hook) || did_action($hook) ) {
            $printer();
        } else {
            add_action($hook, $printer);
        }
    }

    /*
     * $before works only if $handle is the key property of a Script object:
     * in that case, it determines the position relative to the script
     */
    public function registerInlineScript( $code, $pages = [], $handle = '-reg-inline', $before = false ) {
        $action = !is_admin() ? 'wp_enqueue_scripts' : 'admin_enqueue_scripts';
        $me = $this;
        add_action( $action, function() use ( $code, $pages, $handle, $before, $me ) {
            if ( !is_array($pages) ) {
                $pages = [$pages];
            }
            $add = count($pages) == 0;
            if ( !$add ) {
                foreach ( $pages as $page ) {
                    if ( $page->isCurrent() ) {
                        $add = true;
                        break;
                    }
                }
            }
            if ( $add ) {
                $me->addInlineScript( $code, $handle, $before );
            }
        } );
    }

    protected function addLocalizedScript( $name, $data, $handle = '' ) {
        $handle = $this->key . $handle;
        if ( !in_array($handle, $this->localizedScriptHandles) ) {
            wp_register_script( $handle, '', [], $this->version, true );
            wp_enqueue_script( $handle );
            $this->localizedScriptHandles[] = $handle;
        }
        wp_localize_script($handle, $name, $data);
    }

    protected function registerLocalizedScript( $name, $callback, $pages = [], $handle = '' ) {
        $action = !is_admin() ? 'wp_enqueue_scripts' : 'admin_enqueue_scripts';
        $me = $this;
        add_action( $action, function() use ($name, $callback, $pages, $handle, $me) {
            if ( !is_array($pages) ) {
                $pages = [ $pages ];
            }
            $add = count($pages) == 0;
            if ( !$add ) {
                foreach ( $pages as $page ) {
                    if ( $page->isCurrent() ) {
                        $add = true;
                        break;
                    }
                }
            }
            if ( $add ) {
                $data = $callback();
                $me->addLocalizedScript( $name, $data, $handle );
            }
        } );
    }

    public function getScript( $file ) {
        foreach ( $this->scripts as $script ) {
            if ( $script->getFile() == $file ) {
                return $script;
            }
        }
        return null;
    }


    protected function addShortCode( $key, $callback ) {
        $shortcode = new ShortCode($key, $callback);
        $this->shortcodes[] = $shortcode;
        return $shortcode;
    }


    protected function addWidget( $class ) {
        $class_root = explode('\\', __NAMESPACE__)[0];
        $handler = new Elementor\Handler( $class_root . '\\' . $class );
        $this->elementorWidgets[] = $handler;
    }

    private function _addApiEndPoint( $method, $route, $callback, $version ) {
        if ( empty($version) ) {
            $version = 0;
        }
        $ret = new API\EndPoint( $method, $route, $callback, $version );
        $this->endpoints[] = $ret;
        //$this->addApiAjax(); // va aggiunta solo se serve
        return $ret;
    }
    protected function addApiGet( $route, $callback = null, $version = '' ) {
        return $this->_addApiEndPoint('GET', $route, $callback, $version);
    }

    protected function addApiPost( $route, $callback = null, $version = '' ) {
        return $this->_addApiEndPoint('POST', $route, $callback, $version);
    }

    protected function addApiHead( $route, $callback = null, $version = '' ) {
        return $this->_addApiEndPoint('HEAD', $route, $callback, $version);
    }

    protected function addApiAny( $route, $callback = null, $version = '' ) {
        return $this->_addApiEndPoint( ['GET','POST'], $route, $callback, $version);
    }

    protected function addDashLink( $url, $text, $title = '', $target = '_blank' ) {
        $this->dashLinks[] = [
             'url' => $url
            ,'text' => $text
            ,'title' => $title
            ,'target' => $target
        ];
    }

    protected function registerActivation( $callback ) {
        $reflector = new \ReflectionClass( get_class($this) );
        $file = $reflector->getFileName();

        register_activation_hook( $file, [ $this, $callback ] );
    }

    protected function setFilepath() {
        $reflector = new \ReflectionClass( get_class($this) );
        $this->filepath = $reflector->getFileName();
    }
    protected function getInfo() {
        if ( is_null($this->filepath) ) {
            $this->setFilepath();
        }
        $this->infoData = get_file_data($this->filepath, [
            'Version' => 'Version',
            'SosAddonVersion' => 'Sos Addon Version',
            'SosMainVersion' => 'Sos Main Version',
        ]);
        $this->version = $this->infoData['Version'] ?? '';
    }

    public function tryToLoadInfo() {
        if ( empty($this->infoData) ) {
            $this->getInfo();
        }
    }


    public function run() {
        $this->preInitialize();
        $this->initialize();
        
        if ( !is_admin() ) {
            $this->initializeApi();
            if ( !sosidee_is_rest() ) {
                $this->initializeFrontend();
            }
        } else {
            //$this->menu = new BE\Menu( $this->name );
            $this->initializeBackend();
        }
        
        $this->finalize();

        return $this;
    }

    /**
     * EVENTS
     */
    protected function initialize() {
        //to be overridden if needed
        //e.g.: data settings for both front- and back-ends
    }
    private function preInitialize() {

        if ( empty($this->infoData) ) {
            $this->getInfo();
            if ( empty($this->infoData) ) {
                $me = $this;
                add_action('plugins_loaded', function() use ($me) {
                    $me->tryToLoadInfo();
                });
            }
        }

        add_action('plugin_loaded', [$this, '_onLoad']);

        $this->checkAddon();

        /* in TAddonPLM
        if ( !has_action('plugins_loaded', [$this, 'initializeAddons']) ) {
            add_action('plugins_loaded', [$this, 'initializeAddons']);
        }
        */

        if ( is_admin() ) {
            $this->menu = new BE\Menu( $this->name );
        }

    }

    protected function initializeBackend() {
        //to be overridden if needed
        //e.g.: pages, menu, metaboxes... for the backend
        $me = $this;
        add_action( 'enqueue_block_editor_assets', function() use ( $me ) {
            $me->gutenbergEnabled = true;
        } );
    }

    protected function initializeFrontend() {
        //to be overridden if needed
        //e.g. to define shortcodes
    }

    protected function initializeApi() {
        //to be overridden if needed
    }

    public function registerData() {
        foreach ( $this->clusters as $cluster ) {
            $cluster->register();
        }
    }

    public function registerMetaBox( $post_type ) {
        if ( in_array( $post_type, array( 'post', 'page' ) ) ) {
            foreach ( $this->metaboxes as $metabox ) {
                $metabox->register($this);
            }
            add_action('admin_notices', [ __NAMESPACE__ . '\TMessage', 'msgHtml']);
        }
    }

    public function registerApi() {
        if ( sosidee_is_rest() ) {
            foreach ( $this->endpoints as $endpoint ) {
                $endpoint->register();
            }
        }
    }

    public function initializePage() {
        $items = array();
        foreach ( $this->pages as $page ) {
            $page->translate();
            if ( $page->menuType == BE\MenuType::TOOLS ) {
                $page->url = add_query_arg('page', $page->key, admin_url('tools.php'));
            } else if ( $page->menuType == BE\MenuType::SETTINGS ) {
                $page->url = add_query_arg('page', $page->key, admin_url('options-general.php'));
            } else {
                $page->url = add_query_arg('page', $page->key, admin_url('admin.php'));
            }
            if ( $page->menuHidden === true ) {
                $last_slash_pos = strrpos($page->url, '/');
                if ( $last_slash_pos !== false ) {
                    $href = substr($page->url, $last_slash_pos + 1);
                    $items[] = $href;
                }
                //$items[] = $page->key;
            }
            unset($page);
        }
        /*
        if ( count($items) > 0 ) {
            $js = "jQuery(document).ready(function($) { ";
            foreach ( $items as $item ) {
                $js .= "$('a[href=\"$item\"]').closest('li').remove(); ";
            }
            $js .= " });";
            $this->registerInlineScript($js);
        }
        */
        /*
        if ( count($items) > 0 ) {
            $lines = array(
                'jQuery(function($){',
                '  var slugs = ' . json_encode($items) . ';',
                '  slugs.forEach(function(slug){',
                '    $("a[href*=\'page=\' + encodeURIComponent(slug)]").closest("li").remove();',
                '  });',
                '});'
            );
            $js = implode("\n", $lines);
            $this->registerInlineScript($js);
        }
        */
    }
    
    public function initializeMenu() {
        $this->menu = $this->applyFilter('_pre_initialize_menu', $this->menu);
        $this->menu->initialize();
    }
    public function finalizeMenu() {
        $this->menu->finalize();
    }

    public function checkElementor() {
        // Check if Elementor installed and activated
        if ( did_action( 'elementor/loaded' ) ) {
            // Check for required Elementor version
            if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '2.0.0', '>=') ) {
                add_action('elementor/widgets/widgets_registered', [$this, 'initializeElementor']);
            } else {
                // self::msgWarn('The minimum version of Elementor is 2.0.0.', true);
            }
        } else {
            // self::msgWarn('Elementor is not loaded.', true);
        }
    }

    public function initializeElementor() {
        foreach ( $this->elementorWidgets as $widget ) {
            $class = $widget->class;
            $native = new $class();
            $native->initialize();
            // https://developers.elementor.com/v3-5-planned-deprecations/
            //NativeElementor\Plugin::instance()->widgets_manager->register_widget_type( $native );
            NativeElementor\Plugin::instance()->widgets_manager->register( $native );
            $widget->native = $native;
        }
    }

    public function registerDashLink( $plugin_meta, $plugin_file ) {
        $file = str_replace( DIRECTORY_SEPARATOR, '/', $this->filepath);
        if ( sosidee_str_ends_with($file, $plugin_file) ) {
            if ( count($this->dashLinks) > 0) {
                $row_metas = [];
                foreach ( $this->dashLinks as $link ) {
                    $url = $link['url'];
                    $text = $link['text'];
                    $title = $link['title'];
                    $target = $link['target'];
                    if ( self::$internationalized ) {
                        $text = $this::t_($text);
                    }
                    $row_metas[] = '<a href="' . esc_html($url) . '" aria-label="' . esc_attr($text) . '" title="' . esc_attr($title) . '" target="' . esc_attr($target) . '">' . esc_html($text) . '</a>';
                }
                $plugin_meta = array_merge( $plugin_meta, $row_metas );
            }
        }
        return $plugin_meta;
    }

    protected function finalize() {

        add_filter( 'safe_style_css', function($styles) {
            $additional_styles = [
                'display'
            ];
            return array_unique( array_merge( $styles, $additional_styles ) );
         } );

        if ( count($this->qsArgs) > 0) {
            $qsArgs = $this->qsArgs;
            add_filter( 'query_vars', function($vars) use ($qsArgs) {
                if ( is_array($vars) ) {
                    foreach ($qsArgs as $qs) {
                        $vars[] = $qs;
                    }
                    return $vars;
                } else {
                    return $qsArgs;
                }
            } );
        }

        if ( count($this->endpoints) > 0 ) {
            add_action( 'rest_api_init', [$this, 'registerApi'] );
            foreach ( $this->endpoints as $endpoint ) {
                $endpoint->enqueueScript();
            }
        }

        if ( is_admin() ) {
            if ( count($this->clusters) > 0 ) {
                add_action( 'admin_init', array($this, 'registerData') );
            }
            if ( count($this->metaboxes) > 0 ) {
                add_action( 'add_meta_boxes', [ $this, 'registerMetaBox' ] );
                $after_title = true;
                foreach ( $this->metaboxes as $metabox ) {
                    $metabox->registerCallback();
                    if ( $after_title && $metabox->context == 'after_title' ) {
                        $me = $this;
                        add_action('edit_form_after_title',  function() use($me) {
                            if ( $me->gutenbergEnabled ) { return; }
                            global $post, $wp_meta_boxes;
                            do_meta_boxes( get_current_screen(), 'after_title', $post ); // Output the "after_title" meta boxes
                            unset( $wp_meta_boxes[get_post_type($post)]['after_title'] ); // Remove the initial "after_title" meta boxes
                        } );
                        //break;
                        $after_title = false;
                    }
                }
            }

            if ( count($this->dashLinks) > 0 ) {
                add_filter( 'plugin_row_meta', [ $this, 'registerDashLink' ], 10, 2 );
            }

            if ( $this->customUpdate === false ) {

                if ( !empty($this->basename) ) {
                    $file = $this->basename; // TODO: lasciare solo questa linea quando la libreria sarà aggiornata
                } else {
                    $file = "{$this->folder}/{$this->folder}.php";
                }

                if ( !is_multisite() ) {
                    add_action( "in_plugin_update_message-$file", [$this, 'displayUpdateNotice'], 10, 2 );
                } else {
                    add_action( "after_plugin_row_wp-{$file}", [$this, 'displayUpdateNoticeMS'], 10, 2 );
                }
            } else {
                $data = Addon\Repository::getData( $this->key );
                if ( $data && !empty($data->url) ) {
                    $problem = false;
                    if ( $data->basename != $this->basename ) {
                        $problem = true;
                        self::plugErr("Cannot check for remote update (invalid basename).");
                        sosidee_log("Plugin::finalize() - invalid repository basename: {$data->basename}.");
                    }
                    if ( $data->folder != $this->folder ) {
                        $problem = true;
                        self::plugErr("Cannot check for remote update (invalid folder).");
                        sosidee_log("Plugin::finalize() - invalid repository folder: {$data->folder}.");
                    }

                    if ( $problem === false ) {
                        $data->version = $this->version;
                        $ns ??= __NAMESPACE__;
                        $pos = \strpos($ns, '\\');
                        $ns = $pos === false ? $ns : \substr($ns, 0, $pos);
                        $class = $ns . '\SRC\Updater'; // classe dell'updater
                        if ( class_exists( $class ) ) {
                            $this->updater = new $class( $data );
                        } else {
                            self::plugErr("Cannot check for remote update (invalid class).");
                            sosidee_log("Plugin::finalize() - Updater class '{$class}' not found.");
                        }
                    }
                } else {
                    self::plugErr("Cannot check for remote update (invalid url).");
                    sosidee_log("Plugin::finalize() - repository does not exist or the url is empty.");
                }
            }

            if ( count($this->pages) > 0 ) {
                add_action( 'admin_menu', array($this, 'initializePage') );
                $has_form = false;
                foreach ( $this->pages as $page ) {
                    if ( $page->hasForm() ) {
                        $has_form = true;
                        break;
                    }
                }
                if ( $has_form ) {
                    if ( !empty($_POST) ) {
                        if ( !empty($_POST[DATA\Form::FORM_NAME]) ) {
                            add_action( 'admin_init', array($this, 'handlePostedForm') );
                        }
                    } else {
                        add_action( 'current_screen', array($this, 'handleFormLoad') );
                    }
                }

            }
            if ( count($this->menu->pages) > 0 ) {
                add_action( 'admin_menu', array($this, 'initializeMenu') );
                add_action('admin_menu', array($this, 'finalizeMenu'), 9999);
            }

        } else {
            if ( !sosidee_is_rest() ) {  // frontend, not api
                if ( count($this->shortcodes ) > 0) {
                    add_action( 'the_posts', array($this, 'lookForShortcodes' ) );
                }
            }
        }

        if ( count($this->scripts) > 0 ) {
            foreach ( $this->scripts as $script ) {
                if ( !$script->manual ) {
                    $script->html();
                } else {
                    $script->register();
                }
            }
        }
        if ( count($this->styles) > 0 ) {
            foreach ( $this->styles as $style ) {
                $style->html();
            }
        }

        if ( count($this->elementorWidgets) > 0 ) {
            add_action( 'plugins_loaded', array( $this, 'checkElementor' ) );
        }

        if ( $this->deActivationEnabled > 0 ) {
            if ( is_null($this->filepath) ) {
                $this->setFilepath();
            }
            if ( $this->deActivationEnabled % 2 == 0 ) {
                register_activation_hook($this->filepath, array($this, 'onActivate'));
            }
            if ( $this->deActivationEnabled % 3 == 0 ) {
                register_deactivation_hook($this->filepath, array($this, 'onDeactivate'));
            }
        }

    }

    public function onDatabaseUpdate($old_version, $new_version) {
        //to be overridden, if needed
    }

    public function onActivate() {
        //to be overridden, if needed
    }

    public function onDeactivate() {
        //to be overridden, if needed
    }

    public function onLoad() {
        //to be overridden, if needed
    }

    public function _onLoad($file) {
        /*
        if ( plugin_basename($file) == $this->basename ) {
            $this->onLoad();
        }
        */
        // TODO: quando la libreria verrà aggiornata in tutti i plugin, confrontare plugin_basename($file) con $this->basename
        //       ed eliminare il resto del codice
        $folder = sosidee_check_path_separator( sosidee_dirname($file) );
        if ( $folder == $this::$path ) {
            $this->onLoad();
        }
    }

    /**
     * Checks if a shortcode is present in the posts and calls the function hasShortcode()
     * The function hasShortcode() is called once per tag found in the posts
     */
    public function lookForShortcodes( $posts ) {
        if ( empty($posts) ) { return $posts; }

        $tags = array();
        foreach ( $posts as $post ) {
            for ( $n=0; $n<count($this->shortcodes); $n++ ) {
                $tag = $this->shortcodes[$n]->tag;
                $content = $post->post_content;
                if ( stripos($content, "[{$tag} ") !== false || stripos($content, "[{$tag}]") !== false ) {
                    if ( !in_array($tag, $tags) ) {
                        $tags[] = $tag;
                        $this->shortcodes[$n]->isTrueCall = true;

                        $attributes = [];
                        if ( stripos($content, "[{$tag} ") !== false ) {
                            $p1 = stripos($content, "[{$tag} ");
                            $p2 = stripos($content, "]", $p1 + 1);
                            if ( $p2 !== false && $p1 < $p2 ) {
                                $p1++;
                                $p2--;
                                $text = substr($content, $p1, $p2 - $p1 + 1);
                                $attributes = shortcode_parse_atts( $text );
                                if ( count($attributes) > 1 ) {
                                    array_shift($attributes);
                                }
                            }
                        }

                        $this->hasShortcode( $tag, $attributes );
                    }
                }
            }
        }
        return $posts;
    }

    public function handlePostedForm() {
        $name = sanitize_text_field( wp_unslash( $_POST[DATA\Form::FORM_NAME] ) );
        if ( !empty($name) ) {
            foreach ( $this->pages as $page ) {
                if ( $page->hasForm() ) {
                    $form = $page->getFormByName($name);
                    if ( !is_null($form) ) {
                        $form->handleSubmission();
                        return;
                    }
                }
            }
        }
    }

    public function handleFormLoad() {
        foreach ( $this->pages as $page ) {
            if ( $page->isCurrent() && $page->hasForm()  ) {
                $page->handleFormLoad();
            }
        }
    }

    protected function getSpecificHook( $hook, $code = '?' ) {
        $key = $code === '?' ? $this->key : $code;
        return $key . '_' . ltrim($hook, '_');
    }

    public function applyFilter( $hook, $value, ...$args ) {
        $plug_hook = $this->getSpecificHook( $hook );
        $all_args = array_merge([$value], $args);
        return apply_filters( $plug_hook, ...$all_args );
    }

    public function doAction( $hook, ...$args ) {
        $plug_hook = $this->getSpecificHook( $hook );
        do_action( $plug_hook, ...$args );
    }
    public function didAction( $hook ) {
        $plug_hook = $this->getSpecificHook( $hook );
        return did_action( $plug_hook );
    }

    protected function isEncryptionPossible() {
        return extension_loaded( 'openssl' )
            && defined('SECURE_AUTH_KEY') && SECURE_AUTH_KEY != ''
            && defined('SECURE_AUTH_SALT') && SECURE_AUTH_SALT != '';
    }

    public function getTempFolder( $path = '' , $mode = null ) {
        $ret = false;
        $uploads = sosidee_upload_dir();
        if ( $uploads !== false ) {
            $url = $uploads['url'] . $this->key . '/';
            $root = $uploads['path'] . $this->key . DIRECTORY_SEPARATOR;
            $folder = $root;
            if ( !empty($path) ) {
                $path = str_replace( DIRECTORY_SEPARATOR, '/', $path );
                $path = trim( $path, '/' );
                $url .= $path . '/';
                $folder .= sosidee_check_path_separator( $path );
            }
            $ok = FileSystem::isFolder($folder);
            if ( !$ok ) {
                $ok = FileSystem::createFolder( $folder, true );
            }
            if ( $ok ) {
                $content = "<!DOCTYPE html><html lang=\"en\"><head><title>no way</title></head><body>you weren't supposed to be here</body></html>";
                $file = 'index.html';
                $filepath = $root . $file;
                if ( !FileSystem::exists($filepath) ) {
                    FileSystem::write( $filepath, $content );
                }
                if ( !empty($path) ) {
                    $path = str_replace( '/', DIRECTORY_SEPARATOR, $path );
                    $currentPath = $root;
                    $parts = explode(DIRECTORY_SEPARATOR, $path);
                    foreach ( $parts as $part ) {
                        $currentPath .= $part . DIRECTORY_SEPARATOR;
                        $filepath = $currentPath . $file;
                        if ( !FileSystem::exists( $filepath) ) {
                            FileSystem::write( $filepath, $content );
                        }
                    }
                }
                $folder = rtrim( $folder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                if ( is_null($mode) ) {
                    $ret = [ 'path' => $folder, 'url' => $url ];
                } else {
                    switch ( $mode ) {
                        case 'path':
                            $ret = $folder;
                            break;
                        case 'url':
                            $ret = $url;
                            break;
                    }
                }
            }
        }
        return $ret;
    }

    /**
    * It's called when a shortcode is present in a post or page
    * 
    * @param string $tag : tag of the shortcode found in the post/page
     * @param array $attributes : associative array [key => value] of the attributes found in the shortcode
    *
    **/
    protected function hasShortcode( $tag, $attributes ) {
        //to be overridden if needed (e.g.: to add scripts and stylesheets to the HTML header)
    }

    /**
     * @param $args array (associative) with lowercase keys
     * @param $content, $tag string
        public function handleShortcode( $args, $content, $tag ) {
            $attributes = shortcode_atts(
                array(
                    'foo' => 'default_value',
                    'bar' => 'default_value',
                ),
                $args
            );
            return 'string';
        }
     */


        /**
     * Template for the data validation function
     * @param string $cluster_key : key of the cluster
     * @param array $inputs : values sent by the user ( associative array [field key => input value] )
     * 
     * @return array : values to be saved ( associative array [field key => output value] )
     * 
    public function validateData( $cluster_key, $inputs )
    {
        $outputs = array();
        foreach ($inputs as $field_key => $field_value)
        {
            $value = sanitize_in_some_way( $field_value );
            if ($value is OK)
            {
                $outputs[$field_key] = $value;
            }
            else
            {
                $cluster = $this->getCluster($cluster_key);
                $field = $cluster->getField($field_key);
                $outputs[$field_key] = $field->value; //old value
                self::msgErr("error message"); //message to admin console
            }
        }
        return $outputs;
    }
     */

    private function getUpgradeNotice( $version ) {
        $ret = '';
        $url = "https://plugins.svn.wordpress.org/{$this->folder}/tags/{$version}/upgradenotice.txt";
        $response = wp_remote_get( $url );
        if ( !is_wp_error( $response ) ) {
            $http_status = wp_remote_retrieve_response_code($response);
            if ( $http_status == 200 ) {
                $ret = wp_remote_retrieve_body($response);
            }
        }
        return $ret;
    }

    public function displayUpdateNotice( $plugin_data, $new_data ) {
        if ( isset($plugin_data['new_version']) ) {
            $note = $this->getUpgradeNotice( $plugin_data['new_version'] );
            if ( !empty($note) ) {
                echo '<span style="display:block;margin-left:2em;">' . wp_kses( $note, sosidee_allowed_html() ) . '</span>';
            }
        }
    }

    // MS = multi-site
    public function displayUpdateNoticeMS( $file, $plugin ) {
        if ( version_compare( $plugin['Version'], $plugin['new_version'], '<') ) {
            $note = $this->getUpgradeNotice( $plugin['new_version'] );
            if ( !empty($note) ) {
                $wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
                printf(
                    '<tr class="plugin-update-tr"><td colspan="%s" class="plugin-update update-message notice inline notice-warning notice-alt"><div class="update-message"><h4 style="margin: 0; font-size: 14px;">%s</h4>%s</div></td></tr>',
                    esc_attr( $wp_list_table->get_column_count() ),
                    esc_html( $plugin['Name'] ),
                    wp_kses( $note, sosidee_allowed_html() )
                );
            }
        }
    }

    protected function setHelp( $key ) {
        self::$helpUrl .= $key;
    }

    public function help( $path = '', $style = 'margin: 0.75em; float: right;' ) {
        $url = self::$helpUrl . $path;
        $ret = '<a href="' . esc_url($url) . '" onclick="this.blur();" target="_blank" title="help"><i class="dashicons dashicons-editor-help"';
        $style = HtmlTag::getStyle($style, 'color: #ffcc00; font-size: 1.5em; text-decoration: none;');
        if ( !is_null($style) ) {
            $ret .= ' style="' . esc_attr($style) . '"';
        }
        $ret .= '></i></a>';
        return $ret;
    }

}