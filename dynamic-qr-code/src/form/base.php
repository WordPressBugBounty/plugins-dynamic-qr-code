<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

class Base extends \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA\Form
{
    private static $root = null;
    private static $options = null;

    protected $_database;

    public function __construct($name, $callback = null) {
        parent::__construct( $name, $callback );

        $this->_database = $this->_plugin->database;

    }

    private static function getRoot() {
        if ( is_null(self::$root) ) {
            self::$root = get_site_url();
        }
        return self::$root;
    }

    private static function getUrlPath( $value ) {
        $ret = $value;
        if ( strpos($value, self::getRoot() ) !== false ) {
            $index = strlen( self::getRoot() );
            $ret = substr($value, $index );
        }
        return $ret;
    }


    private static function getPageList() {
        $ret = array();
        $pages = get_pages();
        foreach ( $pages as $page ) {
            $url = get_page_link( $page->ID );
            $url = self::getUrlPath( $url );
            $ret[ $url ] = $page->post_title;
        }
        return $ret;
    }

    private static function getPostList() {
        $ret = array();
        $posts = get_posts();
        foreach ( $posts as $post ) {
            $url = get_page_link( $post->ID );
            $url = self::getUrlPath( $url );
            $ret[ $url ] = $post->post_title;
        }
        return $ret;
    }

    public static function getOptions() {
        if ( is_null(self::$options) ) {
            self::$options = [
                '' => 'custom URL'
                ,'Pages' => self::getPageList()
                ,'Posts' => self::getPostList()
            ];
        }
        return self::$options;
    }

    public static function getDescription( $text, $paragraph = false ) {
        $ret = SOS_WP\HtmlTag::get( 'span', [ 'html' => $text, 'style' => 'font-style:italic;' ]);
        if ( $paragraph ) {
            $ret = SOS_WP\HtmlTag::get( 'p', [ 'html' => $ret ]);
        }
        return $ret;
    }

    private static $langs = null;
    public function getLanguageList( $caption = '' ) {
        if ( is_null(self::$langs) ) {
            $items = $this->_plugin->loadAsset('language-codes.json');
            if ( is_array($items) ) {
                for ( $n=0; $n<count($items); $n++ ) {
                    self::$langs[$items[$n]->alpha2] = $items[$n]->English;
                }
            } else {
                sosidee_log('Json languages file could not be successfully loaded.');
                return ['' => '- sorry, cannot load language list -'];
            }
        }
        if ( $caption != '' ) {
            return ['' => $caption] + self::$langs;
        } else {
            return self::$langs;
        }
    }

}