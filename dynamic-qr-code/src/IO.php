<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\FileSystem;

class IO
{
    use SOS_WP\TBase;

    private static $image = [];
    private static $log = [];


    private static function getTempFolder( $path = '' ) {
        return self::plugin()->getTempFolder( $path );
    }

    private static function initImg() {
        self::$image = self::getTempFolder('img');
    }

    public static function getImgPath() {
        if ( empty( self::$image ) ) {
            self::initImg();
        }
        return self::$image['path'] ?? '';
    }
    public static function getImgUrl() {
        if ( empty( self::$image ) ) {
            self::initImg();
        }
        return self::$image['url'] ?? '';
    }

    private static function initLog() {
        self::$log = self::getTempFolder('log');
    }

    public static function getLogPath() {
        if ( empty( self::$log ) ) {
            self::initLog();
        }
        return self::$log['path'] ?? '';
    }
    public static function getLogUrl() {
        if ( empty( self::$log ) ) {
            self::initLog();
        }
        return self::$log['url'] ?? '';
    }

    private static function deleteFiles( $path, $filters ) {
        try {
            foreach ( FileSystem::getFiles( $path, $filters ) as $file  ) {
                FileSystem::deleteFile( $file );
            }
        } catch ( \Exception $ex ) {
            sosidee_log( $ex->getMessage() );
        }
    }

    public static function delete() {
        $path = self::getLogPath();
        if ( !empty($path) ) {
            self::deleteFiles( $path, '*.csv' );
        }
        $path = self::getImgPath();
        if ( !empty($path) ) {
            self::deleteFiles( $path, '*.png' );
        }
    }


}