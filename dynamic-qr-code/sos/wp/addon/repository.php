<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Repository
{
    private static $url = 'https://update.sosplugin.com/';

    private function __construct() {}

    public static function getData( $code ) {
        $ret = new Repo\Data();

        $data = [];
        if ( empty($code) ) {
            sosidee_log('SOS\WP\Addon\Repository::getData() - key is empty.');
        } else {
            $filepath = __DIR__ . DIRECTORY_SEPARATOR . 'repo' . DIRECTORY_SEPARATOR . $code . '.php';
            $realpath = realpath( $filepath );
            if ( $realpath !== false ) {
                $data = include $realpath;
            } else {
                sosidee_log("SOS\WP\Addon\Repository::getData('{$code}') - file not found: {$filepath}.");
            }
        }
        $url_path = $data['path'] ?? '';
        $ret->folder = $code;
        $ret->basename = self::getBasename( $code );
        $ret->url = self::$url . trim($url_path, '/') . '/';
        $zip = $data['zip'] ?? $code;
        $ret->zip = rtrim($zip, '.zip') . '.zip';
        $ret->name = $data['name'] ?? $code;
        $ret->description = $data['description'] ?? '';

        return $ret;
    }


    public static function getBasename( $name ) {
        $name = rtrim( $name, '.php' );
        if ( strpos( $name, '/' ) === false ) {
            $name = "$name/$name";
        }
        $name .= '.php';
        return $name;
    }

    public static function getProLicManPlugin() {
        return self::getData('sos-prolicman');
    }


    public static function check( $url = '' ) {
        if ( !empty( $url ) && sosidee_str_starts_with( $url, self::$url ) === false ) {
            return [
                'success'  => true,
                'scope'  => 'context',
                'code' => '',
                'messages' => '',
            ];
        }

        $test = self::$url . 'test.html';

        $response = wp_remote_head( $test, [
            'timeout'  => 5,
            'sslverify'=> true,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success'  => false,
                'scope'  => 'transport',      // problema di raggiungibilità
                'code'   => $response->get_error_code(),
                'messages' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code !== 200 ) {
            $reason  = wp_remote_retrieve_response_message( $response );
            return [
                'success'  => false,
                'scope'  => 'http',           // problema di risposta remota
                'code'   => $code,
                'messages' => $reason,
            ];
        }

        return [
            'success'  => true,
            'scope'  => 'ok',
            'code' => '',
            'messages' => '',
        ];


    }


}