<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Class Style
 * @package SOSIDEE_DYNAMIC_QRCODE\SOS\WP
 *
 * @property $key
 * @property $url
 */
class Style
{
    use TBase;

    private $pages;

    private $key;

    private $url;

    private $file;

    public function __construct( $key, $file ) {
        $this->key = $key;
        $this->file = $file;

        if ( !str_starts_with($file, 'http') && !str_starts_with($file, '//') ) {
            if ( !str_ends_with($file, '.css') ) {
                $file .= '.css';
            }
            $this->url = self::plugin()::$url . "/assets/css/{$file}";
        } else {
            $this->url = $file;
        }

        $this->pages = array();
    }

    public function getFile() {
        return $this->file;
    }

    public function addToPage() {
        $pages = func_get_args();
        if ( func_num_args() == 1 && is_array($pages[0]) ) {
            $pages = $pages[0];
        }
        $keys = array_map(function($page) {
            return $page->key;
        }, $this->pages);
        foreach ( $pages as $page ) {
            if ( !in_array($page->key, $keys) ) {
                $this->pages[] = $page;
            }
        }
        return $this;
    }

    public function html() {
        $action = !is_admin() ? 'wp_enqueue_scripts' : 'admin_enqueue_scripts';
        add_action( $action, function() {
            $add = !is_admin() || empty($this->pages);
            if ( !$add ) {
                foreach ($this->pages as $page) {
                    if ( $page->isCurrent() ) {
                        $add = true;
                        break;
                    }
                }
            }
            if ( $add ) {
                wp_enqueue_style( $this->key, $this->url, array(), self::plugin()->version, 'all' );
            }
        } );
        return $this;
    }
    
}