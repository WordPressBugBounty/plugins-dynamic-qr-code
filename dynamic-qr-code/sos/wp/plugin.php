<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
use \Elementor as NativeElementor;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Base class for the plugins
 *
 * @property $key : used in the database and for the slugs
 *
 */
class Plugin
{
    use Property
	{
        Property::__get as __getProp;
        Property::__set as __setProp;
	}
    use Message, Asset, Translation;

    private $localizedScriptHandles;
    private $inlineScriptHandles;

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

    protected $file;
    protected $folder;

    public $name;
    public $version;

    public $gutenbergEnabled;

    public $qsArgs; // array of (HTTP GET) custom query variables to be enabled

    public static $path = '';
    public static $url = '';
    
    protected function __construct() {

        $this->_addProperty('key', 'sos-plugin');

        $this->name = 'SOS Plugin';
        $this->version = '';

        $this->localizedScriptHandles = array();
        $this->inlineScriptHandles = array();

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

        self::$path = sosidee_dirname( plugin_dir_path( __FILE__ ) , 2);
        $this->folder = basename(self::$path);
        self::$url = sosidee_dirname( plugin_dir_url( __FILE__ ) , 2);
        Script::$PLUGIN_URL = self::$url;
        Style::$PLUGIN_URL = self::$url;
    }

    private static $_instances = array();
    final public static function instance() {
        $calledClass = get_called_class();
        if ( !isset( self::$_instances[$calledClass] ) ) {
            self::$_instances[$calledClass] = new $calledClass();
        }

        return self::$_instances[$calledClass];
    }

    public function __get( $name ) {
        $ret = null;
        switch($name) {
            default:
                $ret = $this->__getProp($name);
        }
        return $ret;
    }

    public function __set( $name, $value ) {
        $ret = null;
        switch($name) {
            case 'key':
                $value = str_replace( '_', '-', self::checkId($value) );
                if ( !sosidee_str_starts_with($value, 'sos') ) {
                    $value = 'sos-' . $value;
                }
                $ret = $this->__setProp($name, $value);
                break;
            default:
                $ret = $this->__setProp($name, $value);
        }
        return $ret;
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
        $key = $this->key . '-mb_' . self::checkId($key);
        $ret = new MetaBox($key, $title, $screen, $context, $priority, $compatible);
        $this->metaboxes[] = $ret;
        return $ret;
    }

    private function addCluster( $key, $title, $type ) {
        $key = $this->key . '_' . $key;
        $cluster = false;
        if ($type == 'section') {
            $cluster = new Data\Section($key, $title);
        } else if ($type == 'group') {
            $cluster = new Data\Group($key, $title);
        }
        if ($cluster !== false) {
            $this->clusters[] = $cluster;
        }
        return $cluster;
    }
    protected function addSection( $key, $title ) {
        return $this->addCluster( $key, $title, 'section' );
    }
    protected function addGroup( $key, $title ) {
        return $this->addCluster( $key, $title, 'group' );
    }

    protected function getCluster( $key ) {
        $ret = null;
        for ($n=0; $n<count($this->clusters); $n++) {
            $cluster = $this->clusters[$n];
            if ($cluster->key == $key) {
                $ret = $cluster;
                break;
            }
        }
        return $ret;
    }

    private function getClusterIndex( $key ) {
        $ret = false;
        for ($n=0; $n<count($this->clusters); $n++) {
            $cluster = $this->clusters[$n];
            if ($cluster->key == $key) {
                $ret = $n;
                break;
            }
        }
        return $ret;
    }

    protected function addStyle( $file ) {
        $key = $this->key . '-' . count($this->styles);
        $style = new Style($key, $file);
        $this->styles[] = $style;
        return $style;
    }

    protected function addScript( $file, $jquery_dependency = true, $in_body = false ) {
        $key = $this->key . '-' . count($this->scripts);

        $dependency = $jquery_dependency ? array('jquery') : array();

        $script = new Script($key, $file, $dependency, $in_body);
        $this->scripts[] = $script;
        return $script;
    }

    protected function addInlineScript( $code, $handle = '-inline' ) {
        $handle = $this->key . $handle;
        if ( !in_array($handle, $this->inlineScriptHandles) ) {
            wp_register_script( $handle, '', ['jquery'], '', true );
            wp_enqueue_script( $handle );
            $this->inlineScriptHandles[] = $handle;
        }
        wp_add_inline_script( $handle, $code );
    }

    protected function registerInlineScript( $code, $pages = [], $handle = '-reg-inline' ) {
        $action = !is_admin() ? 'wp_enqueue_scripts' : 'admin_enqueue_scripts';
        add_action( $action, function() use ( $code, $pages, $handle ) {
            if (!is_array($pages)) {
                $pages = [$pages];
            }
            $add = count($pages) == 0;
            if ( !$add ) {
                for ( $n=0; $n<count($pages); $n++ ) {
                    if ( $pages[$n]->isCurrent() ) {
                        $add = true;
                        break;
                    }
                }
            }
            if ( $add ) {
                $this->addInlineScript( $code, $handle );
            } else {
                return false;
            }
        } );
    }

    protected function addLocalizedScript( $name, $data, $handle = '' ) {
        $handle = $this->key . $handle;
        if ( !in_array($handle, $this->localizedScriptHandles) ) {
            wp_register_script( $handle, '', [], '', true );
            wp_enqueue_script( $handle );
            $this->localizedScriptHandles[] = $handle;
        }
        wp_localize_script($handle, $name, $data);
    }

    protected function registerLocalizedScript( $name, $callback, $pages = [], $handle = '' ) {
        $action = !is_admin() ? 'wp_enqueue_scripts' : 'admin_enqueue_scripts';
        add_action( $action, function() use ($name, $callback, $pages, $handle) {
            if ( !is_array($pages) ) {
                $pages = [ $pages ];
            }
            $add = count($pages) == 0;
            if ( !$add ) {
                for ( $n=0; $n<count($pages); $n++ ) {
                    if ( $pages[$n]->isCurrent() ) {
                        $add = true;
                        break;
                    }
                }
            }
            if ( $add ) {
                $data = $callback();
                $this->addLocalizedScript( $name, $data, $handle );
            } else {
                return false;
            }
        } );
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
        $ret = new API\EndPoint($method, $route, $callback, $version );
        $this->endpoints[] = $ret;
        $this->addApiAjax();
        return $ret;
    }
    protected function addApiGet( $route, $callback = null, $version = 1 ) {
        return $this->_addApiEndPoint('GET', $route, $callback, $version);
    }

    protected function addApiPost( $route, $callback = null, $version = 1 ) {
        return $this->_addApiEndPoint('POST', $route, $callback, $version);
    }

    protected function addApiHead( $route, $callback = null, $version = 1 ) {
        return $this->_addApiEndPoint('HEAD', $route, $callback, $version);
    }

    protected function addApiAny( $route, $callback = null, $version = 1 ) {
        return $this->_addApiEndPoint( ['GET','POST'], $route, $callback, $version);
        //return $this->_addApiEndPoint( \WP_REST_Server::ALLMETHODS, $route, $callback, $version);
    }

    protected function addDashLink( $url, $text, $title = '', $target = '_blank' ) {
        $this->dashLinks[] = [
             'url' => $url
            ,'text' => $text
            ,'title' => $title
            ,'target' => $target
        ];
    }

    protected function startSession() {
        add_action('init',
            function () {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
            }
        );
    }

    protected function registerActivation( $callback ) {
        $reflector = new \ReflectionClass( get_class($this) );
        $file = $reflector->getFileName();

        register_activation_hook( $file, [ $this, $callback ] );
    }

    protected function getInfo() {
        $reflector = new \ReflectionClass( get_class($this) );
        $this->file = $reflector->getFileName();
        $plugin_data = get_file_data($this->file, [
            'Version' => 'Version',
        ], 'plugin');
        $this->version = $plugin_data['Version'];
    }

    public function run() {
        $this->initialize();
        
        if ( !is_admin() ) {
            $this->initializeApi();
            if ( !sosidee_is_rest() ) {
                $this->initializeFrontend();
            }
        } else {
            $this->menu = new BE\Menu( $this->name );
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

        if ( $this->version == '' ) {
            $this->getInfo();
            if ($this->version == '') {
                add_action('plugins_loaded', function() {
                    if ( $this->version == '' ) {
                        $this->getInfo();
                    }
                });
            }
        }
    }

    protected function initializeBackend() {
        //to be overridden if needed
        //e.g.: pages, menu, metaboxes... for the backend
        add_action( 'enqueue_block_editor_assets', function() {
            $this->gutenbergEnabled = true;
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
        for ($n=0; $n<count($this->clusters); $n++) {
            $cluster = $this->clusters[$n];
            $cluster->register();
        }
    }

    public function registerMetaBox( $post_type ) {
        if ( in_array( $post_type, array( 'post', 'page' ) ) ) {
            for ( $n=0; $n<count($this->metaboxes); $n++ ) {
                $metabox = $this->metaboxes[$n];
                $metabox->register($this);
            }
        }
    }

    public function registerApi() {
        if ( sosidee_is_rest() ) {
            for ( $n=0; $n<count($this->endpoints); $n++ ) {
                $this->endpoints[$n]->register();
            }
        }
    }

    public function initializePage() {
        for ( $n=0; $n<count($this->pages); $n++ ) {
            $this->pages[$n]->translate();
            $this->pages[$n]->url = admin_url('admin.php?page=' . $this->pages[$n]->key);
        }
    }
    
    public function initializeMenu() {
        $this->menu->initialize();
    }

    public function checkElementor() {
        // Check if Elementor installed and activated
        if ( did_action( 'elementor/loaded' ) ) {
            // Check for required Elementor version
            if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '2.0.0', '>=') ) {
                add_action('elementor/widgets/widgets_registered', [$this, 'initializeElementor']);
            } else {
                //self::msgWarn('The minimum version of Elementor is 2.0.0.', true);
            }
        } else {
            //self::msgWarn('Elementor is not loaded.', true);
        }
    }

    public function initializeElementor() {
        for ( $n=0; $n<count($this->elementorWidgets); $n++ ) {
            $class = $this->elementorWidgets[$n]->class;
            $native = new $class();
            $native->initialize();
            // https://developers.elementor.com/v3-5-planned-deprecations/
            //NativeElementor\Plugin::instance()->widgets_manager->register_widget_type( $native );
            NativeElementor\Plugin::instance()->widgets_manager->register( $native );
            $this->elementorWidgets[$n]->native = $native;
        }
    }

    public function registerDashLink( $plugin_meta, $plugin_file ) {
        $file = str_replace( DIRECTORY_SEPARATOR, '/', $this->file);
        if ( sosidee_str_ends_with($file, $plugin_file) ) {
            if ( count($this->dashLinks) > 0) {
                $row_metas = [];
                for ( $n=0; $n<count($this->dashLinks); $n++) {
                    $link = $this->dashLinks[$n];
                    $url = $link['url'];
                    $text = $link['text'];
                    $title = $link['title'];
                    $target = $link['target'];
                    if (self::$internationalized) {
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

        if ( count($this->qsArgs) > 0) {
            add_filter( 'query_vars', function($vars) {
                if ( is_array($vars) ) {
                    foreach ($this->qsArgs as $qs) {
                        $vars[] = $qs;
                    }
                    return $vars;
                } else {
                    return $this->qsArgs;
                }
            } );
        }

        if ( count($this->endpoints) > 0 ) {
            add_action( 'rest_api_init', [$this, 'registerApi'] );
            for ($n=0; $n<count($this->endpoints); $n++) {
                $this->endpoints[$n]->enqueueScript();
            }
        }

        if ( is_admin() ) {
            if ( count($this->clusters) > 0 ) {
                add_action( 'admin_init', array($this, 'registerData') );
            }
            if ( count($this->metaboxes) > 0 ) {
                add_action( 'add_meta_boxes', [ $this, 'registerMetaBox' ] );
                $after_title = true;
                for ( $n=0; $n<count($this->metaboxes); $n++ ) {
                    $metabox = $this->metaboxes[$n];
                    $metabox->registerCallback();
                    if ( $after_title && $metabox->context == 'after_title' ) {
                        add_action('edit_form_after_title',  function() {
                            if ($this->gutenbergEnabled) { return false;}
                            global $post, $wp_meta_boxes;
                            do_meta_boxes( get_current_screen(), 'after_title', $post ); // Output the "after_title" meta boxes
                            unset( $wp_meta_boxes[get_post_type($post)]['after_title'] ); // Remove the initial "after_title" meta boxes
                        } );
                        //break;
                        $after_title = false;
                    }
                }
            }
            if ( count($this->pages) > 0 ) {
                add_action( 'admin_menu', array($this, 'initializePage') );
            }
            if ( count($this->menu->pages) > 0 ) {
                add_action( 'admin_menu', array($this, 'initializeMenu') );
            }
            if ( count($this->scripts) > 0 ) {
                for ($n=0; $n<count($this->scripts); $n++) {
                    $this->scripts[$n]->html();
                }
            }
            if ( count($this->styles) > 0 ) {
                for ( $n=0; $n<count($this->styles); $n++ ) {
                    $this->styles[$n]->html();
                }
            }

            if ( count($this->dashLinks) > 0 ) {
                add_filter( 'plugin_row_meta', [ $this, 'registerDashLink' ], 10, 2 );
            }

            $file = "{$this->folder}/{$this->folder}.php";
            if ( !is_multisite() ) {
                add_action( "in_plugin_update_message-$file", [$this, 'displayUpdateNotice'], 10, 2 );
            } else {
                  add_action( "after_plugin_row_wp-{$file}", [$this, 'displayUpdateNoticeMS'], 10, 2 );
            }

        } else {
            if ( !sosidee_is_rest() ) {  // frontend, not api
                if (count($this->shortcodes) > 0) {
                    add_action( 'the_posts', array($this, 'lookForShortcodes' ) );
                }
            }
        }

        if ( count($this->elementorWidgets) > 0 ) {
            add_action( 'plugins_loaded', array( $this, 'checkElementor' ) );
        }

        register_deactivation_hook($this->file, array($this, 'onDeactivate'));
    }

    public function onDeactivate() {
        //to be overridden, if needed
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

    /**
    *   Selects a data group by record id, 
    *   fills its fields with the values loaded from the database
    *   and returns the cluster array index of the group
    * 
    * @param integer $id : record id of the data group
    *
    * @return integer : index of the group in the $clusters array
     *
     * @TODO: move to DATA\Db or DATA\Group
    **/
    protected function getGroupIndexById( $id ) {
        global $wpdb;

        $ret = false;
        $sql = "SELECT option_name, option_value FROM $wpdb->options WHERE option_id=%d";
        $query = $wpdb->prepare( $sql, $id);
        $results = $wpdb->get_row($query, ARRAY_A);
        if ($results) {
            $key = sanitize_key( $results["option_name"] );
            $ret = $this->getClusterIndex($key);
            if ($ret !== false) {
                $cluster = $this->clusters[$ret];
                if ( $cluster instanceof Data\Group ) {
                    $data = maybe_unserialize( $results["option_value"] );
                    if ( !$cluster->loadFields( $data ) ) {
                        $ret = false;
                    }
                } else {
                    $ret = false;
                }
            }
        }
        return $ret;
    }

    protected function isEncryptionPossible() {
    	return extension_loaded( 'openssl' )
		    && defined('SECURE_AUTH_KEY') && SECURE_AUTH_KEY != ''
	           && defined('SECURE_AUTH_SALT') && SECURE_AUTH_SALT != '';
    }

    public function getTempFolder() {
        $ret = false;
        $root = wp_upload_dir();
        if ( $root['error'] === false ) {
            $url = $root['baseurl'] . '/' .  $this->key;
            $folder = $root['basedir'] . '/' .  $this->key;
            $ok = is_dir($folder);
            if ( !$ok ) {
                $ok = mkdir( $folder );
            }
            if ( $ok ) {
                $file = $folder . DIRECTORY_SEPARATOR .  'index.html';
                if ( !is_file($file) ) {
                    $content = "<!DOCTYPE html><html><head><title>no way</title></head><body>you weren't supposed to be here</body></html>";
                    file_put_contents( $file, $content );
                }

                $ret = array();
                $ret['basedir'] = $folder;
                $ret['baseurl'] = $url;
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
                echo '<span style="display:block;margin-left:2em;">' . sosidee_kses($note) . '</span>';
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
                    $wp_list_table->get_column_count(),
                    $plugin['Name'],
                    sosidee_kses( $note )
                );
            }
        }
    }

}