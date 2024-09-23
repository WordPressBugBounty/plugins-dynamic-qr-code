<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\BE;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Class for the admin console menu
 */
class Menu
{
    use SOS_WP\Property {
        SOS_WP\Property::__set as __setProp;
    }
    use SOS_WP\Translation;

    public $name;
    public $slug;
    public $color;

    public $pages;

    public function __construct($name) {

        $this->_addProperty('icon', '');

        $this->name = '';
        $this->slug = '';
        $this->color = false;

        $this->pages = array();
        $this->name = $name;
    }

    public function __set($name, $value) {
        switch ($name) {
            case 'icon':
                if ( sosidee_str_starts_with($value, '-') == true ) {
                    $value = 'dashicons' . $value;
                }
                break;
        }
        return $this->__setProp($name, $value);
    }

    /**
     * Adds a menu item
     * 
     * @param Page $page : the admin page associated with the item
     * @param string $title : the text displayed in the title tags of the page when the menu item is selected 
     * @param string $role : the capability required for this item to be displayed to the user (overrides the page's default role)
     * 
     * @return Menu object
     */
    public function add($page, $title = '', $role = null) {
        if (count($this->pages) == 0) {
            $this->slug = $page->key; //set the menu base slug
        }
        if ($page->title == '') {
            $page->title = $title;
        }
        if ( !is_null($role) ) {
            $page->role = $role;
        }
        $this->pages[] = $page;

        return $this;
    }

    /**
     * Adds a menu item without displaying it
     */
    public function addHidden($page, $title = '', $role = null) {
        $page->menuType = MenuType::HIDDEN;
        return $this->add($page, $title, $role);
    }

    /**
     * Adds an item to the 'Tools' menu
     */
    public function addTool($page, $title = '', $role = null) {
        $page->menuType = MenuType::TOOLS;
        return $this->add($page, $title, $role);
    }

    /**
     * Adds an item to the 'Settings' menu
     */
    public function addSetting($page, $title = '', $role = null) {
        $page->menuType = MenuType::SETTINGS;
        return $this->add($page, $title, $role);
    }

    public function initialize() {

        for ($n=0; $n<count($this->pages); $n++) {
            $page = $this->pages[$n];
            $file = $page->path;

            $callback = function() use ($file) {
                require_once $file;
            };

            $title = $page->title;
            if ( $title == '' ) {
                $title = $page->key;
            }
            if ( $page->menuColor !== false) {
                $title = "<span style='color:{$page->menuColor};'>" . $title . '</span>';
            }

            if ($page->menuType == MenuType::TOOLS) {
                $page->hook = add_management_page( $this->name, $title, $page->role, $page->key, $callback );
            }
            else if ($page->menuType == MenuType::SETTINGS) {
                $page->hook = add_options_page( $this->name, $title, $page->role, $page->key, $callback );
            } else {
                $root = ($page->menuType != MenuType::HIDDEN) ? $this->slug : null;
                if ($n == 0) {
                    $item = $this->name;
                    if ($this->color !== false) {
                        $item = "<span style=\"color:{$this->color};\">" . $item . "</span>";
                    }
                    add_menu_page( $this->name, $item, $page->role, $this->slug, $callback , $this->icon);
                    $page->hook = add_submenu_page( $root, $this->name, $title, $page->role, $this->slug, $callback );
                } else {
                    $page->hook = add_submenu_page( $root, $this->name, $title, $page->role, $page->key, $callback );
                }
            }
        }
    }
    
}