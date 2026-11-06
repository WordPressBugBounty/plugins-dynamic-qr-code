<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\BE;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Class for the admin console menu
 * icon: https://developer.wordpress.org/resource/dashicons/#wordpress
 */
class Menu
{
    use SOS_WP\TTranslation;
    use SOS_WP\TBase;

    public $name;
    public $slug;
    public $icon;
    public $color;

    public $pages;

    public function __construct($name) {

        $this->name = $name;
        $this->slug = self::plugin()->key;
        $this->color = false;
        $this->icon = '';

        $this->pages = array();
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
    public function add( $page, $title = '', $role = null ) {
        $page->init( $title, $role );
        $this->pages[] = $page;

        return $this;
    }

    /***
     * Inserts an item in $index position, starting from zero
     */
    public function insert( $index, $page, $title = '', $role = null) {
        $page->init( $title, $role );
        array_splice($this->pages, $index, 0, [$page]);
        return $this;
    }

    /**
     * Adds an item to the 'Tools' menu
     */
    public function addTool( $page, $title = '', $role = null ) {
        $page->menuType = MenuType::TOOLS;
        return $this->add($page, $title, $role);
    }

    /**
     * Adds an item to the 'Settings' menu
     */
    public function addSetting( $page, $title = '', $role = null ) {
        $page->menuType = MenuType::SETTINGS;
        return $this->add($page, $title, $role);
    }

    private function getPageIndex($page) {
        if ( count($this->pages) === 0 ) {
            return -1;
        }
        if ( is_int($page) ) {
            return $page;
        } else {
            $index = -1;
            foreach ( $this->pages as $i => $p ) {
                if ( $p->key == $page->key ) {
                    $index = $i;
                    break;
                }
            }
            return $index;
        }
    }

    public function show( $page ) {
        $index = $this->getPageIndex($page);
        if ( $index === -1 ) {
            return;
        }

        if ( isset($this->pages[$index]) ) {
            $this->pages[$index]->menuHidden = false;
        }

    }

    public function hide( $page ) {
        $index = $this->getPageIndex($page);
        if ( $index === -1 ) {
            return;
        }

        if ( isset($this->pages[$index]) ) {
            $this->pages[$index]->menuHidden = true;
        }

    }

    public function delete( $page ) {
        $index = $this->getPageIndex($page);
        if ( $index === -1 ) {
            return;
        }
        if ( isset($this->pages[$index]) ) {
            unset($this->pages[$index]);
            $this->pages = array_values($this->pages);
        }
    }

    public function initialize() {

        $firstVisibleUrl = false;
        foreach ( $this->pages as $page ) {
            if ( !$page->menuHidden ) {
                $firstVisibleUrl = $page->url;
                break;
            }
        }

        foreach ( $this->pages as $n => $page ) {

            $title = $page->title;
            if ( $title == '' ) {
                $title = $page->key;
            }
            if ( $page->menuColor !== false ) {
                $title = "<span style='color:{$page->menuColor};'>" . $title . '</span>';
            }

            if ( $page->menuHidden ) {
                $title = '';
            } else {
                $title = self::t_($title);
            }

            $callback = function() use ($page) {
                require_once $page->path;
            };

            if ( $page->menuType == MenuType::TOOLS ) {
                $page->hook = add_management_page( $this->name, $title, $page->role, $page->key, $callback );
            } else if ( $page->menuType == MenuType::SETTINGS ) {
                $page->hook = add_options_page( $this->name, $title, $page->role, $page->key, $callback );
            } else {
                $parent_slug = $this->slug;
                if ( $page->menuHidden ) {
                    $parent_slug .= '-hid';
                }
                if ( $n === 0 ) {
                    $item = $this->name;
                    if ($this->color !== false) {
                        $item = "<span style=\"color:{$this->color};\">" . $item . "</span>";
                    }
                    $icon = $this->icon;
                    if ( !empty($icon) && sosidee_str_starts_with($icon, '-') === true ) {
                        $icon = 'dashicons' . $icon;
                    }

                    $top_callback = function () use ($firstVisibleUrl) {
                        if (!$firstVisibleUrl) {
                            echo '<p>This plugin has no page.</p>';
                        }
                    };

                    $top_hook = add_menu_page(
                        $this->name,
                        $item,
                        $page->role,
                        $this->slug,
                        $top_callback,
                        $icon
                    );

                    if ( $firstVisibleUrl ) {
                        add_action("load-$top_hook", function () use ($firstVisibleUrl) {
                            wp_safe_redirect($firstVisibleUrl, 302);
                            exit;
                        });
                    }
                }
                $page->hook = add_submenu_page( $parent_slug, $this->name, $title, $page->role, $page->key, $callback );
            }
        }
    }

    public function finalize() {
        if ( count($this->pages) > 0 ) {
            remove_submenu_page($this->slug, $this->slug);
        }
    }

}