<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

class Locale extends Base
{
    private static $languages = null;
    public static function getLanguages( $caption = '' ) {
        if ( is_null(self::$languages) ) {
            $items = Assets\Handler::load('language-codes.json');
            if ( is_array($items) ) {
                for ( $n=0; $n<count($items); $n++ ) {
                    self::$languages[$items[$n]->alpha2] = $items[$n]->English;
                }
            } else {
                if ( self::hasLog() ) {
                    self::$log->error('Language (json) file could not be successfully loaded.');
                }
                return ['' => '- sorry, cannot load language list -'];
            }
        }
        if ( is_null(self::$languages) ) {
            self::$languages = [];
        }
        if ( $caption != '' ) {
            return ['' => $caption] + self::$languages;
        } else {
            return self::$languages;
        }
    }

    public static function getLanguageDescription( $code ) {
        $ret = 'unknown';
        $items = self::getLanguages();
        if ( isset($items[$code]) ) {
            $ret = $items[$code];
        }
        return $ret;
    }

    private static $countries = null;
    public static function getCountries( $caption = '' ) {
        if ( is_null(self::$countries) ) {
            $items = Assets\Handler::load('country-codes.json');
            if ( is_array($items) ) {
                for ( $n=0; $n<count($items); $n++ ) {
                    self::$countries[$items[$n]->Code] = $items[$n]->Name;
                }
            } else {
                if ( self::hasLog() ) {
                    self::$log->error('Country (json) file could not be successfully loaded.');
                }
                return ['' => '- sorry, cannot load Country list -'];
            }
        }
        if ( is_null(self::$countries) ) {
            self::$countries = [];
        }
        if ( $caption != '' ) {
            return ['' => $caption] + self::$countries;
        } else {
            return self::$countries;
        }
    }

    public static function getCountryDescription( $code ) {
        $ret = 'unknown';
        $items = self::getCountries();
        if ( isset($items[$code]) ) {
            $ret = $items[$code];
        }
        return $ret;
    }

}