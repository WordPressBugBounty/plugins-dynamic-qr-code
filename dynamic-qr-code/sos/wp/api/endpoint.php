<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\API;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class EndPoint
{
    use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TBase;

    const ROOT = 'rapi';
    const ACTION = 'wp_rest';

    private static $nonce = '';

    private $version;
    private $method;
    private $callback;
    private $route;

    private $configName;
    private $configData;

    public $localized;
    public $nonceDisabled;

    public function __construct( $method, $route, $callback, $version ) {
        $this->method = $method;
        $this->route = $route;
        $this->callback = $callback;
        $this->version = $version;

        $this->localized = false;
        $this->configName = '';
        $this->configData = array();

        $this->nonceDisabled = false;
    }

    public static function getNonce() {
        if (self::$nonce == '') {
            self::$nonce = wp_create_nonce( self::ACTION );
        }
        return self::$nonce;
    }

    private function getNamespace() {
        $ret = trim( self::ROOT, '/' );
        if ( !empty($this->version) ) {
            $ret .= "/v{$this->version}";
        }
        return $ret;
    }

    private function getRoute() {
        $route = $this->route;
        if ( !sosidee_str_starts_with($route, '/') ) {
            $route = '/' . $route;
        }
        return $this->getNamespace() . $route;
    }

    public function getPath() {
        return '?rest_route=/' . $this->getRoute();
    }

    public function getUrl() {
        $route  = $this->getRoute();
        $prefix = rest_get_url_prefix(); // normalmente "wp-json"

        // prova la via canonica
        $url = get_rest_url(null, $route);

        // fallback solo se l'URL è vuoto, fuori dominio o fuori prefisso
        $home = trailingslashit(home_url());
        if ( empty($url) || strpos($url, $home) !== 0 || strpos($url, "/$prefix/") === false ) {
            $url = add_query_arg('rest_route', '/' . ltrim($route, '/'), $home);
        }

        if ( sosidee_is_dev() ) {
            $url = \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Http::replaceLocalHostWithIp( $url );
        }

        return $url;
        //return get_site_url() . '/' . $this->getPath();
    }

    public function localize( $name = 'sosapi', $conf = [] ) {
        if ( !array_key_exists('method', $conf) ) {
            $conf['method'] = $this->method;
        }
        if ( !array_key_exists('url', $conf) ) {
            $conf['url'] = $this->getUrl(); //'?rest_route=' . $route;
        }

        if ( array_key_exists('data', $conf) || $this->method == 'POST' ) {
            if ( !array_key_exists('dataType', $conf) ) {
                $conf['dataType'] = 'json';
            }
            if ( !array_key_exists('contentType', $conf) ) {
                $conf['contentType'] = 'application/json; charset=utf-8';
            }
            if ( $conf['dataType'] == 'json' && !empty($conf['data']) ) {
                $conf['data'] = json_encode( $conf['data'] );
            }
        }

        $this->localized = true;
        $this->configName = $name;
        $this->configData = $conf;

    }

    public function enqueueScript() {
        if ( $this->localized !== false ) {
            $action = !is_admin() ? 'wp_enqueue_scripts' : 'admin_enqueue_scripts';
            $me = $this;
            add_action( $action, function() use($me) {
                if ( !array_key_exists('nonce', $me->configData) && $me->nonceDisabled !== true ) {
                    $me->configData['nonce'] = $me::getNonce();
                }
                wp_register_script( self::ACTION, '', [], $me->version, true );
                wp_enqueue_script( self::ACTION );
                wp_localize_script( self::ACTION, $me->configName, $me->configData );
            });
        }
    }

    public static function script( $code ) {
        $version = self::plugin()->version;
        wp_register_script( self::ACTION, '', ['jquery'], $version, true );
        wp_enqueue_script( self::ACTION );
        wp_add_inline_script(self::ACTION, $code );
    }

    /**
     *  @TODO: nella variabile $args è possibile inserire anche la chiave 'args' con valore un array contenente
     *  le chiavi della querystring (metodo GET), i cui valori sono recuperabili con il metodo $request->get_param( 'key' );
     *  https://developer.wordpress.org/reference/functions/register_rest_route/
     *  https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
     *  https://awhitepixel.com/blog/in-depth-guide-in-creating-and-fetching-custom-wp-rest-api-endpoints/
     */
    public function register() {
        $callback = $this->callback;
        if ( is_null($callback) ) {
            $callback = function() {
                return new \WP_REST_Response( 'no callback has been defined', 418);
            };
        }

        $args = [
             'methods' => $this->method
            ,'callback' => $callback
        ];
        if ( $this->nonceDisabled !== true ) {
            $args['permission_callback'] = function () {
                $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';
                return wp_verify_nonce( $nonce, self::ACTION);
            };
        } else {
            $args['permission_callback'] = function () {
                return true;
            };
        }
        $namespace = $this->getNamespace();
        register_rest_route( $namespace, $this->route, $args );
    }

}