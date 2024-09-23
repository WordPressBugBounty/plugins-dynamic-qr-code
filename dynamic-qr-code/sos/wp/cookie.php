<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Cookie
{

    // $expiry: in seconds
    public static function set( $name, $value, $expiry = 0 ) {
        if ( $expiry > 0) {
            $expiry += time();
        }
        if ( !empty($value) ) {
            setcookie( $name, base64_encode( json_encode( $value ) ), $expiry, '/', self::getDomain() );
        } else {
            setcookie( $name, $value, $expiry, '/', self::getDomain() );
        }
    }

    public static function get( $name, $value_default = null ) {
        $ret = $value_default;
        if ( isset($_COOKIE[$name]) ) {
            if ( sosidee_is_base64( $_COOKIE[$name] ) ) { // checks the cookie value
                $ret = sosidee_json_decode( base64_decode($_COOKIE[$name]), $value_default);
            }
        }
        return $ret;
    }

    public static function del( $name ) {
        setcookie( $name, '', -1, '/', self::getDomain() );
    }

    private static $domain = '';
    private static $DOMAINS = [];

    public static function getDomain() {
        if ( empty(self::$domain) ) {
            $url = strtolower( esc_url_raw($_SERVER['HTTP_HOST']) );
            if ( sosidee_str_starts_with($url, 'https://') ) {
                $url = substr($url, strlen('https://'));
            } else if ( sosidee_str_starts_with($url, 'http://') ) {
                $url = substr($url, strlen('http://'));
            }
            self::$domain = strrev( $url );
            if ( count(self::$DOMAINS) == 0 ) {
                self::loadDomains();
            }
            for ( $n=0; $n<count(self::$DOMAINS); $n++ ) {
                if ( sosidee_str_starts_with( self::$domain, self::$DOMAINS[$n] ) ) {
                    $offset = strlen( self::$DOMAINS[$n] ) + 1;
                    $pos = strpos(self::$domain, '.', $offset);
                    if ($pos !== false) {
                        self::$domain = substr( self::$domain, 0, $pos );
                    }
                    break;
                }
            }
            self::$domain = strrev( self::$domain );
        }
        return self::$domain;
    }

    private static function loadDomains() {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'public_suffix_list.dat';
        if ( realpath( $file ) !== false ) {
            $handle = fopen($file, 'r');
            if ($handle) {
                while ( ($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if ( !empty($line) && !sosidee_str_starts_with($line, '//') ) {
                        self::$DOMAINS[] = strrev($line) . '.';
                    }
                }
                fclose($handle);
            } else {
                sosidee_log("SOS\WP\Cookie.loadDomains() could not read file $file");
            }
        } else {
            sosidee_log("SOS\WP\Cookie.loadDomains() could not read file $file");
        }
        rsort(self::$DOMAINS);
    }

}