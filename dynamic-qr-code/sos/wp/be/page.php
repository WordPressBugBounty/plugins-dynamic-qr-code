<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\BE;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

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
    use SOS_WP\TTranslation;

    private static $screen = null;

    protected $forms;

    public $key;
    public $url;
    public $path;
    public $name;
    public $role;
    public $title;
    public $menuType;
    public $menuColor;
    public $hook;
    public $menuHidden;

    public function __construct( $path, $name = '' ) {

        if ( !sosidee_str_ends_with($path, '.php') ) {
            $path .= '.php';
        }
        $this->path = $path;

        if ( $name == '' ) {
            $name = sosidee_str_remove('.php', basename( $path) );
        }
        $this->name = $name;

        $this->key = '';
        $this->url = '';
        $this->role = 'manage_options';
        $this->title = '';
        $this->menuType = MenuType::CUSTOM;
        $this->menuColor = false;
        $this->menuHidden = false;
        $this->hook = false;

        $this->forms = array();
    }

    public function translate() {
        $this->name = self::t_( $this->name );
        $this->title = self::t_( $this->title );
    }

    public function init( $title, $role ) {
        if ( $this->title == '' ) {
            $this->title = $title;
        }
        if ( !is_null($role) ) {
            $this->role = $role;
        }
    }
    
    public function isCurrent() {
        if ( is_null(self::$screen) ) {
            self::$screen = get_current_screen();
        }
        if ( is_null(self::$screen) ) {
            sosidee_log("Page::isCurrent() - get_current_screen() returned null.");
            return false;
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

    public function hasForm() {
        return count($this->forms) > 0;
    }

    public function addForm() {
        $forms = func_get_args();
        if ( func_num_args() == 1 && is_array($forms[0]) ) {
            $forms = $forms[0];
        }
        $keys = array_map(function($form) {
            return $form->getKey();
        }, $this->forms);
        foreach ( $forms as $form ) {
            if ( !in_array($form->getKey(), $keys) ) {
                $this->forms[] = $form;
            }
        }
        return $this;
    }

    public function getFormByName( $name ) {
        foreach ( $this->forms as $form ) {
            if ( $form->getName() == $name ) {
                return $form;
            }
        }
        return null;
    }

    public function handleFormLoad() {
        foreach ( $this->forms as $form ) {
            $form->handleLoad();
        }
    }


}