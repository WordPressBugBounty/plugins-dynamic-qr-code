<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TBase
{

    public static function plugin() {
        return \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
    }

    public static function database() {
        if ( isset(self::plugin()->database) ) {
            return self::plugin()->database;
        } else {
            sosidee_log("custom database object is null.");
            return null;
        }
    }

}