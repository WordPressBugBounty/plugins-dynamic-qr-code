<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Json;
class Api
{
    use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TBase;

    private static $LICENSE_MANAGER_URL = 'https://sosplugin.com/';

    /**
     * Native function: activate() in 'include/class.sl.api.php'
     */
    public static function activate( $key, $pid, $domain = '' ) {
        return self::getResponse( 'activate', $key, $pid, $domain );
    }

    /**
     * Native function: deactivate() in 'include/class.sl.api.php'
     */
    public static function deactivate( $key, $pid, $domain = '' ) {
        return self::getResponse( 'deactivate', $key, $pid, $domain );
    }

    /**
     * Native functions:
     *      status_check() in 'include/class.sl.api.php'
     *      get_licence_statuses() in 'include/class.sl.functions.php'
     *
     *  licence_status: active | inactive | expired | not-activated | cancelled
     */
    public static function check( $key, $pid, $domain = '' ) {
        return self::getResponse( 'status-check', $key, $pid, $domain );
    }

    private static function getResponse( $action, $key, $pid, $domain ) {
        $response = new Response();

        $result = self::request( $action, $key, $pid, $domain );

        if ( ! empty( $result->error ) ) {
            $response->error   = true;
            $response->message = $result->message ?? 'Communication error with the license server.';
            $response->status  = Status::UNKNOWN;
            return $response;
        }

        $response->error = false;
        $response->message = $result->message ?? 'Action completed.';

        if ( ! empty( $result->licence_status ) ) {
            $response->status = Status::getByTag( $result->licence_status );
        } elseif ( ! empty( $result->status_code ) ) {
            $response->status = Status::getByCode( $result->status_code );
        } else {
            $response->status = Status::UNKNOWN;
            $response->message = 'The server response does not contain useful information about the license status.';
        }

        return $response;
    }

    private static function request( $action, $key, $pid, $domain ) {
        if ( empty( $domain ) ) {
            $domain = sosidee_get_host();
        }

        $qs_args = array(
             'woo_sl_action' => $action
            ,'licence_key' => $key
            ,'product_unique_id' => $pid
            ,'domain' => $domain
        );

        $url = add_query_arg( $qs_args, self::getUrl() );

        $connection_args = array(
            'timeout' => 12, // seconds
        );

        $response = wp_remote_get( $url, $connection_args );

        if ( is_wp_error( $response ) ) {
            $status = 'WP Error';
            $message = $response->get_error_message();
            sosidee_log( "License\Api::$action($key, $pid, $domain) - wp_remote_get() returned: $message" );
            if ( $response->get_error_code() === 'http_request_failed' ) {
                $data = $response->get_error_data();
                $is_timeout = false;
                if ( isset( $data['curl_error_code'] ) ) {
                    if ( $data['curl_error_code'] === 28 ) { //Operation timed out
                        $is_timeout = true;
                    }
                } elseif ( str_contains( $message, 'cURL error 28' ) ||
                            str_contains( strtolower( $message ), 'timed out' ) ||
                            str_contains( strtolower( $message ), 'timeout' ) ) {
                    $is_timeout = true;
                }
                if ( $is_timeout ) {
                    $status = 'Connection Timeout';
                    $message = 'Unable to connect to the license server due to a timeout. Please try again shortly.';
                }
            }
            return self::createResult($status, $message);
        }

        $http_code = wp_remote_retrieve_response_code( $response );

        if ( $http_code !== 200 ) {
            $message = "Invalid HTTP response code: $http_code";
            sosidee_log( "License\Api::$action($key, $pid, $domain) - $message. Response: " . print_r( $response, true ) );
            return self::createResult( 'HTTP Error', $message );
        }

        $body_json = wp_remote_retrieve_body( $response );
        if ( empty( $body_json ) ) {
            $message = 'Empty response body from server.';
            sosidee_log( "License\Api::$action($key, $pid, $domain) - $message" );
            return self::createResult( 'API Error', $message );
        }

        $decoded_body = Json::decode( $body_json );
        $api_data = is_array( $decoded_body ) ? ( $decoded_body[0] ?? null ) : $decoded_body;

        if ( ! is_object( $api_data ) ) {
            $message = 'API response is not a valid object after decoding.';
            sosidee_log( "License\Api::$action($key, $pid, $domain) - Invalid data structure. Decoded body: " . print_r( $decoded_body, true ) );
            return self::createResult( 'API Data Error', $message );
        }

        if ( ! isset( $api_data->status ) ) {
            $message = "Missing 'status' property in the API response.";
            sosidee_log( "License\Api::$action($key, $pid, $domain) - $message. Body: " . print_r( $api_data, true ) );
            return self::createResult( 'API Format Error', $message );
        }

        if ( sosidee_is_dev() ) {
            sosidee_log( "sosidee_is_dev() is true, so \$api_data is logged just below:" );
            sosidee_log( print_r( $api_data, true ) );
        }
        return $api_data;
    }

    /*
    public static function getFormattedDomain() {
        $url = home_url();
        $host = wp_parse_url( $url, PHP_URL_HOST );

        if ( empty( $host ) ) {
            return '';
        }

        if ( strpos( $host, 'www.' ) === 0 ) {
            $host = substr( $host, 4 );
        }

        return $host;
    }
    */

    private static function createResult( $status, $message ) {
        $ret = new \stdClass();
        $ret->error = true;
        $ret->status = $status;
        $ret->message = $message;
        $ret->status_code = '';

        return $ret;
    }

    private static function getUrl() {
        return self::$LICENSE_MANAGER_URL;
    }
}