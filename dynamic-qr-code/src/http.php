<?php

namespace SOSIDEE_DYNAMIC_QRCODE\SRC;

//use SOSIDEE_DYNAMIC_QRCODE\SOS\Text;

class HTTP
{
    public static function getLanguages() {
        $ret = '*';
        if ( !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
            $http = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            if ( strlen($http) > 1 ) {
                $items = explode(',', $http);
                $languages = array_unique(array_filter(array_map(function($item) {
                    $item = strtolower($item);
                    if ( strpos($item, 'und') === false ) {
                        return preg_replace('/[^a-z]/', '', substr($item, 0, 2));
                    }
                    return null;
                }, $items)));

                $languages = array_filter($languages, function($item) {
                    return strlen($item) > 1;
                });

                if ( count($languages) > 0 ) {
                    $ret = $languages;
                }
            }
        }
        return $ret;
    }

}