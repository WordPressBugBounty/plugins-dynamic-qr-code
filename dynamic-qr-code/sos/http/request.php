<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\Http;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

use SOSIDEE_DYNAMIC_QRCODE\SOS\Text;


class Request
{

    public static function getLanguages() {
        $ret = '*';
        if ( !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
            $http = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            if ( strlen($http) > 1 ) {
                $languages = array();
                $items = explode(',', $http);
                foreach ( $items as $_ ) {
                    $_ = strtolower($_);
                    if ( !Text::startsWith($_, 'und') ) {
                        $item = preg_replace( '/[^a-z]/', '', substr( $_, 0, 2) );
                        if ( strlen($item) > 1 && !in_array($item, $languages) ) {
                            $languages[] = $item;
                        }
                    }
                }
                if ( count($languages) > 0 ) {
                    $ret = $languages;
                }
            }
        }
        return $ret;
    }

}