<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Http
{

    public static function getServerIp() {
        $cache_key = 'sosidee_server_ip';

        $cached_ip = get_transient($cache_key);
        if ( false !== $cached_ip ) {
            return $cached_ip;
        }

        $url = 'https://api.ipify.org?format=json';

        $response = wp_remote_get($url, [
            'timeout' => 10, // imposta un timeout di 10 secondi
        ]);

        if ( is_wp_error($response) ) {
            sosidee_log('Http::getServerIp() - API IPify error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = Json::decode($body);
        if ( is_null($data) ) {
            sosidee_log('Http::getServerIp() - invalid response body: ' . (string)$body);
            return false;
        }
        if ( empty($data->ip) ) {
            sosidee_log('Http::getServerIp() - IP string is empty.');
            return false;
        }

        if ( filter_var($data->ip, FILTER_VALIDATE_IP) ) {
            set_transient($cache_key, $data->ip, 12 * HOUR_IN_SECONDS);
            return $data->ip;
        } else {
            sosidee_log('Http::getServerIp() - invalid IP string: ' . (string)$data->ip);
            return false;
        }

    }

    public static function replaceLocalHostWithIp( $url ) {
        if ( !sosidee_is_dev() ) {
            return $url;
        }

        $parts = wp_parse_url($url);
        if ( empty($parts['host']) ) {
            return $url;
        }

        $host = strtolower($parts['host']);

        $isLoopback =
            $host === 'localhost' ||
            $host === '127.0.0.1' ||
            $host === '::1';

        $isLocDomain = str_ends_with($host, '.loc');

        $isPrivateIp = self::isPrivateIpv4( $host );

        if ( !$isLoopback && !$isLocDomain && ! $isPrivateIp ) {
            return $url;
        }

        if ( $isPrivateIp ) {
            $ip = $host;
        } else {
            $ip = Request::getPreferredServerPrivateIp();
            if ( empty( $ip ) ) {
                return $url;
            }
        }

        // ricostruisce sostituendo solo l'host, lasciando schema, path, query ecc.
        $scheme = $parts['scheme'] ?? 'http';
        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;
        $port = $parts['port'] ?? null;
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        if ( $isLocDomain || $isPrivateIp ) {
            $port = 8081;
        }

        $auth = '';
        if ( $user !== null ) {
            $auth = $user;
            if ( $pass !== null ) {
                $auth .= ':' . $pass;
            }
            $auth .= '@';
        }

        $portPart = $port ? ':' . $port : '';

        return "{$scheme}://{$auth}{$ip}{$portPart}{$path}{$query}{$fragment}";
    }

    public static function isHostPrivateIp(string $url) {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null || $host === false) {
            return false;
        }

        // Gestisce gli IPv6 racchiusi tra parentesi, per esempio http://[::1]
        $host = trim($host, '[]');

        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip === false) {
            return false;
        }

        return self::isPrivateIpv4($ip);
    }

    public static function isPrivateIpv4($ip) {
        if (
            !is_string($ip) ||
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ) {
            return false;
        }

        $value = ip2long($ip);

        if ($value === false) {
            return false;
        }

        return
            ($value >= ip2long('10.0.0.0') &&
                $value <= ip2long('10.255.255.255')) ||

            ($value >= ip2long('172.16.0.0') &&
                $value <= ip2long('172.31.255.255')) ||

            ($value >= ip2long('192.168.0.0') &&
                $value <= ip2long('192.168.255.255'));
    }

    public static function isSSL() {
        if ( is_ssl() ) {
            return true;
        }

        if ( !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ) {
            return true;
        }

        if ( !empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https' ) {
            return true;
        }

        if ( !empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443 ) {
            return true;
        }

        return false;
    }


}