<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

class Base extends \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA\Form
{
    public static $FLD_HID;

    private static $root = null;
    private static $options = null;

    //protected $_database;

    public function __construct($name, $callback = null) {
        parent::__construct( $name, $callback );
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

    public static function getUrlList() {
        if ( is_null(self::$options) ) {
            self::$options = [ '' => 'custom URL' ];
            if ( self::plugin()->hasFacebook() ) {
                self::$options['Facebook&nbsp;'] = self::plugin()->addon->facebook::getOptions();
            }
            if ( self::plugin()->hasInstagram() ) {
                self::$options['Instagram&nbsp;'] = self::plugin()->addon->instagram::getOptions();
            }
            if ( self::plugin()->hasLinkedIn() ) {
                self::$options['LinkedIn&nbsp;'] = self::plugin()->addon->linkedin::getOptions();
            }
            if ( self::plugin()->hasWhatsApp() ) {
                self::$options['WhatsApp&nbsp;'] = self::plugin()->addon->whatsapp::getOptions();
            }
            if ( self::plugin()->hasYouTube() ) {
                self::$options['YouTube&nbsp;'] = self::plugin()->addon->youtube::getOptions();
            }
            self::$options['Pages&nbsp;'] = self::getPageList();
            self::$options['Posts&nbsp;'] = self::getPostList();
        }
        return self::$options;
    }

    public static function getDescription( $text, $paragraph = false ) {
        $ret = SOS_WP\HtmlTag::get( 'span', [ 'html' => $text, 'style' => 'font-style:italic;' ] );
        if ( $paragraph ) {
            $ret = SOS_WP\HtmlTag::get( 'p', [ 'html' => $ret ] );
        }
        return $ret;
    }

    private static function isPosted() {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST';
    }

    public static function checkPosted( $value ) {
        $ret = false;
        if ( self::isPosted() ) {
            $config = self::plugin()->config;
            //$config->formCheckMode->load();
            switch ( $config->formCheckMode->getValue() ) {
                case CheckMode::METHOD:
                    $ret = true;
                    break;
                case CheckMode::REFERER:
                    $ref = wp_get_raw_referer();
                    $pid = url_to_postid($ref);
                    if ( $pid == get_the_ID() ) {
                        $ret = true;
                    } else {
                        sosidee_log("Hiding content: data posted from an invalid URL. Referer=$ref");
                    }
                    break;
                case CheckMode::FIELD:
                    if ( isset($_POST[self::$FLD_HID]) ) {
                        $hid = trim( $_POST[self::$FLD_HID] );
                        if ( strcasecmp( $hid, $value ) == 0 ) {
                            $ret = true;
                        } else {
                            sosidee_log("Hiding content: hidden field value is {$hid} while {$value} was expected.");
                        }
                    } else {
                        sosidee_log("Hiding content: hidden field not found.");
                    }
                    break;
            }
        } else {
            sosidee_log("Hiding content: invalid REQUEST_METHOD value.");
        }
        return $ret;
    }

    protected function getProMsg( $msg ) {
        return $msg . ' ' . self::plugin()->pro();
    }

    public static function getHiddenFieldTemplate( $id ) {
        return SOS_WP\DATA\FormTag::get( 'input', [
             'type' => 'hidden'
            ,'name' => self::$FLD_HID
            ,'value' => $id
        ]);
    }


}