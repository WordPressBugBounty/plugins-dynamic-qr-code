<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Class Script
 * @package SOSIDEE_DYNAMIC_QRCODE\SOS\WP
 *
 * @property $key
 * @property $url
 * @property $body
 */
class Script
{
    use TBase;

    private $dependency;
    private $pages;

    private $inlineCodes;

    private $url;

    private $body;

    private $file;

    public $key;


    public $manual;

    public function __construct( $key, $file, $dependency, $body ) {
        $this->key = $key;
        $this->file = $file;

        if ( !str_starts_with($file, 'http') && !str_starts_with($file, '//') ) {
            if ( !str_ends_with($file, '.js') ) {
                $file .= '.js';
            }
            $this->url = self::plugin()::$url . "/assets/js/{$file}";
        } else {
            $this->url = $file;
        }


        $this->body = $body;
        $this->dependency = $dependency;

        $this->pages = array();
        $this->inlineCodes = array();

        $this->manual = false;
    }

    public function getFile() {
        return $this->file;
    }

    public function addToPage() {
        $pages = func_get_args();
        if ( count($pages) > 0 && is_array($pages[0]) ) {
            $pages = $pages[0];
        }
        $keys = array_map( function($page) {
                return $page->key;
            }, $this->pages );
        foreach ( $pages as $page ) {
            if ( !in_array($page->key, $keys) ) {
                $this->pages[] = $page;
            }
        }
        return $this;
    }

    public function addInLineCode( $param, $before = false ) {
        $code = new JsCode();
        if ( is_callable($param) ) {
            $code->callback = $param;
        } elseif ( is_string($param) ) {
            $code->content = $param;
        } else {
            sosidee_log("Script.addInLineCode(): param must be a string or a callable.");
        }
        $code->before = $before;
        $this->inlineCodes[] = $code;
    }

    public function html() {
        $this->register();
        $this->enqueue();
        return $this;
    }

    public function _register() {
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
            if ( !wp_script_is($this->key, 'registered') ) {
                wp_register_script( $this->key, $this->url, $this->dependency, self::plugin()->version, $this->body );
            }
        }
    }

    public function register() {
        $action = !is_admin() ? 'wp_enqueue_scripts' : 'admin_enqueue_scripts';
        if ( !did_action($action) ) {
            $me = $this;
            add_action( $action, function() use($me) {
                $me->_register();
            } );
        } else {
            $this->_register();
        }
        return $this;
    }

    public function _enqueue() {
        if ( !wp_script_is($this->key, 'enqueued') ) {
            foreach ( $this->inlineCodes as $code ) {
                $content = '// empty line';
                $position = $code->before ? 'before' : 'after';
                if ( !is_null($code->callback) && is_callable($code->callback) ) {
                    $content = call_user_func($code->callback);
                } else if ( !is_null($code->content) ) {
                    $content = $code->content;
                }
                wp_add_inline_script( $this->key, $content, $position );
            }
            wp_enqueue_script( $this->key );
        }
    }
    public function enqueue() {
        $action = !is_admin() ? 'wp_enqueue_scripts' : 'admin_enqueue_scripts';
        if ( !did_action($action) ) {
            $me = $this;
            add_action( $action, function() use($me) {
                $me->_enqueue();
            } );
        } else {
            $this->_enqueue();
        }
        return $this;
    }

}