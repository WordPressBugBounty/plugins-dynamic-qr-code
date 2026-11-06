<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Cookie
{

    // $expiry: in seconds
    // $sameSite: 'Strict' | 'Lax' | 'None'
    public static function set( $name, $value, $expiry = 0, $httpOnly = true, $sameSite = 'Lax' ) {
        if ( empty($value) ) {
            sosidee_log("Cookie::set() - value should not be empty (cookie '$name' deleted).");
            return self::del( $name, $httpOnly, $sameSite );
        }
        if ( $expiry > 0) {
            $expiry += time();
        }
        $options = [
            'expires' => $expiry,
            'path' => '/',
            'domain' => self::getDomain(),
            'secure' => HTTP::isSSL(),
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ];
        return setcookie( $name, base64_encode( json_encode( $value ) ), $options );
    }

    public static function get( $name, $value_default = null, $as_array = false ) {
        $ret = $value_default;
        if ( isset($_COOKIE[$name]) ) {
            $value = wp_unslash( $_COOKIE[$name] );
            if ( sosidee_is_base64( $value ) ) { // checks if the cookie value is base64 encoded
                $ret = sosidee_json_decode( base64_decode( $value ), $value_default, $as_array);
            }
        }
        return $ret;
    }

    public static function del( $name, $httpOnly = true, $sameSite = 'Lax' ) {
        $options = [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => self::getDomain(),
            'secure' => HTTP::isSSL(),
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ];

        return setcookie( $name, '', $options );
    }

    private static $domain = '';
    private static $DOMAINS = [];

    /** @noinspection HttpUrlsUsage */
    public static function getDomain() {
        if ( empty(self::$domain) ) {
            $url = isset($_SERVER['HTTP_HOST']) ? strtolower( esc_url_raw( wp_unslash($_SERVER['HTTP_HOST']) ) ) : '';
            if ( sosidee_str_starts_with($url, 'https://') ) {
                $url = substr($url, strlen('https://'));
            } else if ( sosidee_str_starts_with($url, 'http://') ) {
                $url = substr($url, strlen('http://'));
            }
            $domain = strrev( $url );
            if ( count(self::$DOMAINS) == 0 ) {
                self::loadDomains();
            }
            for ( $n=0; $n<count(self::$DOMAINS); $n++ ) {
                if ( sosidee_str_starts_with( $domain, self::$DOMAINS[$n] ) ) {
                    $offset = strlen( self::$DOMAINS[$n] ) + 1;
                    $pos = strpos($domain, '.', $offset);
                    if ( $pos !== false ) {
                        $domain = substr( $domain, 0, $pos );
                    }
                    break;
                }
            }
            self::$domain = strrev( $domain );
        }
        return self::$domain;
    }

    private static function loadDomains() {
        self::$DOMAINS = [];
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'public_suffix_list.dat';
        if ( realpath( $file ) !== false ) {
            $lines = FileSystem::lines( $file, true );
            if ( $lines !== false ) {
                foreach ( $lines as $line ) {
                    $line = trim($line);
                    if ( !sosidee_str_starts_with($line, '//') ) {
                        self::$DOMAINS[] = strrev($line) . '.';
                    }
                }
                rsort(self::$DOMAINS);
            } else {
                sosidee_log("SOS\WP\Cookie.loadDomains() could not read file: $file");
            }
        } else {
            sosidee_log("SOS\WP\Cookie.loadDomains() file not found: $file");
        }
        rsort(self::$DOMAINS);
    }

}