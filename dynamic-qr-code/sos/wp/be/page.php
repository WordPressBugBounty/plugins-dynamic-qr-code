<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\BE;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Class for the admin console pages
 * 
 * @property string $name : the text displayed in the browser title and in the nav-tab
 * @property string $role : the capability required to be displayed to the user when added to a menu item
 * @property string $title : the text displayed in the title tags of the page when the menu item is selected
 * @property string $key
 * @property string $hook : the hook_suffix returned by add_menu_page(), add_submenu_page(), add_options_page(), etc.
 * @property string $menuType
 * @property string $menuColor
 */
class Page
{
    use SOS_WP\Property {
        SOS_WP\Property::__set as __setProp;
    }
    use SOS_WP\Translation;

    private static $screen = null;

    public $key;
    public $url;
    public $name;
    public $role;
    public $title;
    public $menuType;
    public $menuColor;
    public $hook;

    public function __construct($path, $name) {
        $this->_addProperty('path', '');
        $this->path = $path;

        $this->key = '';
        $this->url = '';
        $this->name = '';
        $this->role = 'manage_options';
        $this->title = '';
        $this->menuType = MenuType::CUSTOM;
        $this->menuColor = false;
        $this->hook = false;

        if ($name == '') {
            $name = sosidee_str_remove('.php', basename( $path) );
        }
        $this->name = $name;

    }

    public function __set($name, $value) {
        switch ($name) {
            case 'path':
                if ( !sosidee_str_ends_with($value, '.php') ) {
                    $value .= '.php';
                }
                break;
        }
        return $this->__setProp($name, $value);
    }
    
    public function translate() {
        $this->name = self::t_( $this->name );
        $this->title = self::t_( $this->title );
    }
    
    public function isCurrent() {
        if ( is_null(self::$screen) ) {
            self::$screen = get_current_screen();
        }
        return sosidee_str_ends_with(self::$screen->id, $this->key );
    }

    /***
     * @param ...$args : null or query string array [ key_1 => $value_1, key_2 => $value_2, ecc.]
     * @return string : page url with querystring ?key_1=$value_1&key_2=$value_2 ecc.
     */
    public function getUrl(...$args) {
        return add_query_arg($args, $this->url);
    }

}