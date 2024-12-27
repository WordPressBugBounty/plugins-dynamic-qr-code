<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\Assets;
use SOSIDEE_DYNAMIC_QRCODE\SOS\IO;
use SOSIDEE_DYNAMIC_QRCODE\SOS\Json;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

class Handler
{
    private static function root() {
        return IO::checkDirSep( __DIR__);
    }
    public static function load( $filename ) {
        $ret = false;
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        $path = IO::checkSep(self::root() . "$ext/$filename");
        $file = realpath($path);
        if ( $file !== false ) {
            $ret = file_get_contents($file);
        } else {
            $path = IO::checkSep(self::root() . $filename);
            $file = realpath($path);
            if ( $file !== false ) {
                $ret = file_get_contents($file);
            }
        }
        if ( $ret !== false && $ext == 'json') {
            $res = Json::decode( $ret );
            if ( $res !== false ) {
                $ret = $res;
            }
        }
        return  $ret;
    }
}