<?php
/*
Plugin Name: Dynamic QR Code
Version: 1.0.1
Description: Allows you to create DYNAMIC QR CODES: you can modify what happens when scanning your QR code without actually modifying (and reprinting) the QR code.
Requires at least: 5.9
Tested up to: 6.7
Requires PHP: 8.0
Author: SOSidee.com srl
Author URI: https://sosidee.com
Text Domain: dynamic-qr-code
Domain Path: /languages
Plugin URI: https://sosplugin.com/dynamic-qr-code/
Contributors: sosidee
*/
namespace SOSIDEE_DYNAMIC_QRCODE;
( defined( 'ABSPATH' ) and defined( 'WPINC' ) ) or die( 'you were not supposed to be here' );
defined('SOSIDEE_DYNAMIC_QRCODE') || define( 'SOSIDEE_DYNAMIC_QRCODE', true );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

require_once "wp-loader.php";

\SOSIDEE_CLASS_LOADER::instance()->add( __NAMESPACE__, __DIR__ );

/**
 * Class of This Plugin *
 *
**/
class SosPlugin extends SOS\WP\Plugin
{

    use SRC\TAddon;

    //pages
    private $pageQrCodes;
    private $pageLogs;

    public $pageQrCode;
    public $pageConfigs;
    private $pageStats;

    //database
    public $database;
    public $config;

    //forms
    public $formSearchQrCode;
    public $formEditQrCode;
    public $formSearchLog;
    public $formStatLog;

    private $apiRedirect; //API

    private $mbHC; //metabox for hiding post/page content

    protected function __construct() {
        parent::__construct();

        //PLUGIN KEY & NAME 
        $this->key = 'sos-dynamic-qr-code';
        $this->name = 'Dynamic QR Code';

        SRC\FORM\Base::$FLD_HID = SRC\Shortcode::TAG . '_' . SRC\Shortcode::AUTH;

        //if necessary, enable localization
        //$this->internationalize( 'dynamic-qr-code' ); //Text Domain

        self::$helpUrl = 'https://support.sosidee.com/{KEY}/';
        $this->setHelp('dynamic-qr-code');

        $this->checkAddon = true;
        $this->addonClass = 'DQC';

    }

    protected function initialize() {
        parent::initialize();

        // settings
        $section = $this->addSection('config', 'Settings');
        $this->config = new SRC\FORM\Configs( $section );

        // database: custom tables for the plugin
        $this->database = new SRC\Database();

        $this->apiRedirect = $this->addApiAny('dynamic-qr-code', [ $this, 'apiRedirectByCode' ], 0 );
        $this->apiRedirect->nonceDisabled = true;

        $mb = $this->addMetaBox( 'post-content', $this->name );
        $this->mbHC = new SRC\Metabox($mb);

        //if ( $this->hasAnyAddon() ) {
        //    $this->name .= ' PRO';
        //}
    }

    protected function initializeBackend() {

        $this->pageQrCodes = $this->addPage('qrcodes' );
        $this->pageQrCode = $this->addPage('qrcode' );
        $this->pageQrCode->menuHidden = true;
        $this->pageLogs = $this->addPage('logs' );
        $this->pageConfigs = $this->addPage('configs' );
        $this->pageStats = $this->addPage('stats' );

        //assign data cluster to page
        $this->config->setPage( $this->pageConfigs );

        //menu
        $this->menu->icon = '-screenoptions';

        $this->menu->add( $this->pageQrCodes, 'QR-Codes' );
        $this->menu->add( $this->pageQrCode );
        $this->menu->add( $this->pageLogs, 'Scan logs' );
        $this->menu->add( $this->pageStats, 'Scan stats' );
        $this->menu->add( $this->pageConfigs, 'Settings' );

        $this->formSearchQrCode = new SRC\FORM\QrCodeSearch();
        $this->formSearchQrCode->addToPage( $this->pageQrCodes );

        $this->formEditQrCode = new SRC\FORM\QrCodeEdit();
        $this->formEditQrCode->addToPage( $this->pageQrCode );

        $this->formSearchLog = new SRC\FORM\logSearch();
        $this->formSearchLog->addToPage( $this->pageLogs );

        $this->formStatLog = new SRC\FORM\logStat();
        $this->formStatLog->addToPage( $this->pageStats );

        $this->qsArgs[] = SRC\FORM\QrCodeEdit::QS_ID;

        $this->addScript('admin')->addToPage( $this->pageQrCodes, $this->pageQrCode );
        $this->addScript('qrcode')->addToPage( $this->pageQrCode );
        $this->addScript('config')->addToPage( $this->pageConfigs );
        $this->addStyle('admin')->addToPage( $this->pageQrCodes, $this->pageQrCode, $this->pageLogs, $this->pageStats );
        $this->addGoogleIcons();
        $this->addGoogleIconsToEditor();

        $this->addDashLink( self::$helpUrl , 'Help' );

        add_action('current_screen', [$this, 'checkConfig']);

        //TEST
        //$dw = new SOS\WP\DashboardWidget('custom_help_widget', 'Pippo');
        //$dw->callback = [$this, 'xxx'];
    }

    /*
    public function loadQrCodeList( $caption = false, $include_cancelled = false ) {
        $ret = [];
        if ( $caption !== false ) {
            $ret[0] = $caption;
        }

        $results = $this->database->loadQrCodeList( $include_cancelled );

        if ( is_array($results) ) {
            if ( count($results) > 0 ) {
                for ( $n=0; $n<count($results); $n++ ) {
                    $ret[ $results[$n]->qrcode_id ] = $results[$n]->description;
                }
            }
        } else {
            self::msgErr( 'A problem occurred while reading the qr code list from the database.' );
        }
        return $ret;
    }
    */

    public function addDeleteCookieScript( $js ) {
        $this->addInlineScript( $js, 'cookie-delete' );
    }

    public function getJsRedirect( $url ) {
        $ret = '<p style="font-style: italic;">';
        $ret .= DATA\FormTag::get( 'img', [
             'alt' => 'waiting...'
            ,'src' => $this->getLoaderSrc(24)
            ,'width' => '12px'
        ]);
        $ret .= ' redirecting...</p>';

        $url = $this->getRedirectUrl( esc_url( $url ) );
        $js = <<<EOD
            self.window.location.replace('{$url}');
EOD;
        $ret .= DATA\FormTag::get( 'script', [
             'type' => 'application/javascript'
            ,'content' => $js
        ]);

        return $ret;
    }

    protected function initializeFrontend() {
        //add_filter( 'the_content', [ $this, 'checkMetaboxPost' ] );
        add_filter( 'the_content', [ $this->mbHC, 'checkPost' ] );
        $this->addShortCode( SRC\Shortcode::TAG, array($this, 'dynqrcode_handle_shortcode') );
    }

    public function checkConfig() {
        if ( !function_exists('ImageCreate') ) {
            $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> GD library (PHP) not found: please contact your server administrator.";
            self::msgErr( $msg );
        }

        if ( $this->pageQrCodes->isCurrent() || $this->pageQrCode->isCurrent() ) {
            if ( !$this->config->check() ) {
                $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> Configuration is not valid: please check <a href=\"{$this->pageConfigs->url}\">{$this->pageConfigs->title}</a>";
                $this::msgErr($msg);
            }
        } else if ( $this->pageLogs->isCurrent() ) {
            if ( !$this->formSearchLog->_posted ) {
                $this->config->logDisabled->load();
                if ( $this->config->logDisabled->value == true ) {
                    $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> Logs are currently disabled: please check <a href=\"{$this->pageConfigs->url}\">{$this->pageConfigs->title}</a>";
                    self::msgWarn( $msg );
                }
            }
        }
    }

    protected function hasShortcode( $tag, $attributes ) {
    }

    private function htmlQrCodeImage( $qrcode, $sc ) {
        $ret = '';
        $msg = '';

        if ( $sc->imageType == 'enhanced' ) {
            $cypher = true;
            if ( $sc->timeout > 0 ) {
                $code = SRC\QrCode::getNewCypher();
                $data = [ 'cypher' => $code ];
                if ( $this->database->saveQrCode( $data, $qrcode->qrcode_id ) ) {
                    $qrcode->cypher = $code;
                } else {
                    $msg = "can't save the new code in the database.";
                    if ( $qrcode->cypher != '') {
                        $msg .= " Old image used.";
                    }
                    sosidee_log("A problem occurred while saving the new code in the database (record.id={$qrcode->qrcode_id}).");
                }
            } else {
                if ( $qrcode->cypher == '') {
                    $msg .= "enhanced image not found (not created because timeout is not greater than zero).";
                }
            }
            $code = base64_encode( $qrcode->cypher );
        } else {
            $cypher = false;
            $code = $qrcode->code;
        }

        if ( $code != '') {
            $fore_color = $sc->colorFore;
            if ( $fore_color == '') {
                $fore_color = $qrcode->img_forecolor;
            }
            $back_color = $sc->colorBack;
            if ( $back_color == '') {
                $back_color = $qrcode->img_backcolor;
            }
            $text = $this->getApiUrl( $code, $cypher );
            $fore_color = SRC\QrCode::getColor( $fore_color );
            $back_color = SRC\QrCode::getColor( $back_color );
            $data = SRC\QrCode::getString( $text, $sc->imageSize, $sc->imagePad, $fore_color, $back_color );

            $ret = DATA\FormTag::get( 'img', [
                 'src' => "data:image/png;base64,{$data}"
                ,'alt' => 'scan this qr code with your mobile device'
                ,'class' => $sc->cssClass
            ]);

            if ( $sc->timeout > 0 ) {
                $ms = $sc->timeout * 60 * 1000;
                $js = <<<EOD
function jsSosDqcAddEvent(fn) {
    if (window.addEventListener) {
        window.addEventListener('load', fn);
    } else if (window.attachEvent) {
            window['eload' + fn] = fn;
            window['load' + fn] = function (event) {
            window['eload' + fn](event);
        }
        window.attachEvent('onload', window['load' + fn]);
    } else {
        var _win_onload_ = window.onload;
        window.onload = function(event) {
            if ( _win_onload_ ) {
                _win_onload_(event);
                _win_onload_ = null;
            }
            fn(event);
        };
    }
}
jsSosDqcAddEvent( function(e) {
    setTimeout( function() { location.reload(); }, $ms);
});
EOD;

                $ret .= DATA\FormTag::get( 'script', [
                     'type' => 'application/javascript'
                    ,'content' => $js
                ]);

            }

        }

        if ( $msg != '' ) {
            $ret .= DATA\FormTag::get( 'pre', [
                'content' => "{$this->name}: " . $msg
            ]);
        }

        return $ret;
    }

    public function dynqrcode_handle_shortcode( $args, $content = '' ) {
        $tag = SRC\Shortcode::TAG;
        $msg = "<!-- {$this->name} -->";
        $msg .= "<pre><em>we've had a problem here: ";
        $invalid = false;

        $show = false;
        $delete_cookie = true;

        $sc = new SRC\Shortcode( $args );

        if ( $sc->id > 0 ) {
            $qrcode = $this->database->loadQrCode( $sc->id );
            if ( $qrcode !== false ) {

                if ( $sc->mode == SRC\ShortcodeMode::DISPLAY_IMAGE ) {
                    if ( $sc->timeout == 0 || $sc->imageType == 'enhanced' ) {
                        $show = true;
                        $content = $this->htmlQrCodeImage( $qrcode, $sc );
                    } else {
                        $msg .= "timeout can't be used with 'standard' image type.";
                        $invalid = true;
                        sosidee_log("{$tag} shortcode invalid parameters: timeout is not zero with a 'standard' image type.");
                    }
                    $delete_cookie = false;
                } else {

                    $this->config->load(); // load current configuration
                    $isMobileBrowser = SOS\Mobile::is() && SOS\Mobile::isBrowser();
                    $isDeviceEnabled = $isMobileBrowser || $this->config->anyDeviceEnabled->value; //it's a mobile browser OR any device

                    if ( $isDeviceEnabled ) {

                        $key = SRC\OTKey::getCookie();
                        if ( $key != '' ) {
                            $otkey = $this->database->loadOTKey( $key, $qrcode->qrcode_id );
                            if ( $otkey !== false && $otkey->otk_id > 0 ) {
                                $tally = intval( $otkey->tally );
                                $tally++;
                                if ( $this->database->updateOTKey( $otkey->otk_id, $tally ) == false ) {
                                    sosidee_log("database.updateOTKey({$tally}) failed for key.id={$otkey->otk_id}");
                                }
                                if ( $tally == 1 ) {
                                    $show = true;
                                    if ( $sc->hasForm ) {
                                        $delete_cookie = false;
                                    }
                                } else {
                                    if ( $sc->hasForm && SRC\FORM\Base::checkPosted( $sc->id ) ) {
                                        $show = true;
                                        $delete_cookie = false;
                                    } else {
                                        sosidee_log("Hiding content shortcode: cookie already used.");
                                    }
                                }
                            } else {
                                sosidee_log("database.loadOTKey({$qrcode->qrcode_id}) failed for key={$key}");
                            }
                        }
                    } else {
                        sosidee_log("{$tag} shortcode: device not authorized.");
                    }
                }
            } else {
                $msg .= "invalid parameter(s)";
                $invalid = true;
                sosidee_log("{$tag} shortcode invalid parameter: " . SRC\Shortcode::AUTH . "={$sc->id}.");
            }
        } else {
            $msg .= "invalid shortcode";
            $invalid = true;
            sosidee_log("{$tag} shortcode invalid parameter: " . SRC\Shortcode::AUTH . "={$sc->id}.");
        }

        if ( !$invalid ) {
            if ( $show ) {
                $ret = do_shortcode( $content );
            } else {
                $ret = "<!-- {$this->name}: hidden content -->";
            }
        } else {
            $msg .= "</em><br>[$tag";
            foreach ( $args as $key => $value ) {
                $msg .= " {$key}=\"{$value}\"";
            }
            $msg .= "]</pre>";
            $ret = $msg;
        }

        if ( $delete_cookie ) {
            SRC\OTKey::setJsCookieEraser();
        }

        return apply_filters( 'dynqrcode_handle_shortcode', $ret );
    }

    public function getApiUrl( $code = '', $cypher = false ) {
        if ( !empty($code) ) {
            $key = !$cypher ? 'qr' : 'cr';
            $value = urlencode( $code );
            return $this->apiRedirect->getUrl() . "&{$key}={$value}";
        } else {
            return $this->apiRedirect->getUrl(); //return 'https://this.is.just.a.demo/?rest_route=/rapi/dynamic-qr-code';
        }
    }

    public function getApiUrlLength( $code_length = 0 ) {
        if ( $code_length > 0 ) {
            $code_length = SRC\QrCode::getB64Len($code_length);
        }
        return strlen( $this->getApiUrl() ) + 4 + $code_length;
    }

    public function apiRedirectByCode( \WP_REST_Request $request ) {

        $log = [
             'code' => '?'
            ,'status' => SRC\LogStatus::ERROR
            ,'qrcode_id' => 0
        ];

        $url = '';
        $qr_code = '';
        $qr_event_id = '';

        $isMFApp = SRC\App::isMyFastApp();
        if ( $isMFApp ) {
            $user = SRC\App::getUserId();
            if ( $user !== false ) {
                $log['user_key'] = $user;
            }
        }

        $this->config->load(); // load current configuration
        $anyDevice = $this->config->anyDeviceEnabled->value;

        $isMobile = SOS\Mobile::is();
        $deviceEnabled = $isMobile || $anyDevice; //it's mobile OR any device

        $isMobileBrowser = SOS\Mobile::isBrowser();
        if ( $isMFApp ) {
            $insertLog = !$this->config->logDisabled->value;
        } else {
            $insertLog = !$this->config->logDisabled->value && (!$isMobile || $isMobileBrowser);
        }

        if ( $insertLog ) {
            $log['dev_type'] = $isMobile ? SRC\DeviceType::MOBILE : SRC\DeviceType::NOT_MOBILE;
            if ( $this->config->geoEnabled->value && $this->hasGeo() ) {
                $key = $this->config->geoKey->value;
                $geo = $this->addon::getGeo($key);
                if ( is_array($geo) ) {
                    $log['country'] = $geo['country'];
                    $log['region'] = $geo['region'];
                    $log['city'] = $geo['city'];
                }
            }
            $langs = SRC\HTTP::getLanguages();
            if ( is_array($langs) && count($langs) > 0 ) {
                $log['lang'] = $langs[0];
            }
            if ( !$this->isPro ) {
                $log['op_sys'] = SRC\OS::UNKNOWN;
            } else {
                $log['op_sys'] = $this->addon::getOS();
            }
        }

        $isCypher = false;
        $otkey = false;

        $method = $request->get_method();
        if ( $method == 'GET' ) { // && $deviceEnabled
            $qs = $request->get_query_params();
            if ( array_key_exists('qr', $qs) ) {
                $value = $qs['qr'];
                $qr_code = trim( html_entity_decode( urldecode( $value ) ) );
                $qr_code = sanitize_text_field( $qr_code );
            } else if ( array_key_exists('cr', $qs) ) {
                $value = $qs['cr'];
                $qr_code = trim( html_entity_decode( urldecode( base64_decode( $value ) ) ) );
                $qr_code = sanitize_text_field( $qr_code );
                $isCypher = true;
            }
        } else if ( $method  == 'POST' && $isMFApp ) {
            $body = $request->get_body();
            $json = json_decode($body);
            if ( isset($json->id) ) {
                $qr_event_id = sanitize_text_field( $json->id );
            }
            if ( isset($json->qr) ) {
                $qr = trim( html_entity_decode( urldecode( $json->qr ) ) );
                $root = $this->getApiUrl('');
                if ( sosidee_str_starts_with($qr, $root) ) {
                    $qr = substr( $qr, strlen($root) );
                    if ( sosidee_str_starts_with($qr, 'qr=') ) {
                        $qr_code = substr( $qr, strlen('qr=') );
                    } else if ( sosidee_str_starts_with($qr, 'cr=') ) {
                        $qr_code = base64_decode( substr( $qr, strlen('cr=') ) );
                        $isCypher = true;
                    }
                    $qr_code = sanitize_text_field( $qr_code );
                }
            }
        }

        if ( $isCypher ) {
            $item = $this->database->loadQrCodeByCypher($qr_code);
            if ( $item !== false ) {
                $qr_code = $item->code;
            } else {
                $qr_code = '';
                sosidee_log("database.loadQrCodeByCypher($qr_code) returned false.");
            }
        }

        if ( $qr_code != '' ) {
            $log['code'] = $qr_code;
            $log['event_id'] = $qr_event_id;

            $items = $this->database->loadQrCodeByKey($qr_code);
            if ( is_array( $items ) && count( $items ) > 0 ) {
                $priority = false;
                $qrcodes = [];
                for ( $n=0; $n<count($items); $n++ ) {
                    $item = &$items[$n];
                    $item->browser = $isMobileBrowser || !$isMobile; //dynamically added
                    if ( $item->only_mfa && !$isMFApp ) {
                        $status = SRC\QrCodeStatus::DISABLED;
                    } else {
                        $status = SRC\QrCode::getStatus( $item );
                    }
                    $item->status = $status; //dynamically added
                    if ( $status == SRC\QrCodeStatus::ACTIVE ) {
                        if ( $item->priority ) {
                            $priority = true;
                        }
                        $qrcodes[] = $items[$n];
                    }
                    unset($item);
                }

                // se nessun QR-Code è attivo, allora cerca tra gli abilitati (per avere un url...)
                if ( count($qrcodes) == 0 ) {
                    for ( $n=0; $n<count($items); $n++ ) {
                        if ( $items[$n]->status != SRC\QrCodeStatus::DISABLED ) {
                            $qrcodes[] = $items[$n];
                            if ( $items[$n]->priority ) {
                                $priority = true;
                            }
                        }
                    }
                }

                // se ancora nessun QR-Code va bene, allora vale tutto! (5tika22i)
                if ( count($qrcodes) == 0 ) {
                    $qrcodes = $items;
                }

                if ( $priority ) {
                    // there should be only one!
                    $items = $qrcodes;
                    $qrcodes = [];
                    for ( $n=0; $n<count($items); $n++ ) {
                        if ( $items[$n]->priority ) {
                            $qrcodes[] = $items[$n];
                        }
                    }
                }

                $index = 0;
                if ( count($qrcodes) > 1 ) {
                    $index = SRC\QrCode::roll( count($qrcodes) );
                }
                $qrcode = $qrcodes[$index];
                $log['qrcode_id'] = $qrcode->qrcode_id;

                if ( !$deviceEnabled ) {
                    $qrcode->status = SRC\QrCodeStatus::DISABLED;
                }

                $log['status'] = $qrcode->status;

                if ( $deviceEnabled && ( $isCypher || $this->config->anyQrHideEnabled->value ) ) {
                    $otkey = SRC\OTKey::getNew();
                    $otdata = [
                         'qrcode_id' => intval( $qrcode->qrcode_id )
                        ,'code' =>$otkey
                    ];

                    if ( $this->database->insertOTKey( $otdata ) ) {
                        SRC\OTKey::setCookie( $otkey );
                    } else {
                        sosidee_log("database.saveOTKey() returned false.");
                    }
                }

                $url = SRC\QrCode::getRedirectUrl( $qrcode, $this->config );

            } else {
                $url = $this->config->urlError->value;
                $log['status'] = SRC\LogStatus::ERROR;
                sosidee_log("database.loadQrCodeByKey($qr_code) returned false.");
            }
        } else {
            $url = $this->config->urlError->value;
            $log['status'] = SRC\LogStatus::ERROR;
        }

        sosidee_log( [
                 'HTTP-Method' => $method
                ,'QR-Code-Key' => $qr_code
                ,'QR-Code-Id' => $log['qrcode_id']
                ,'Redirect-URL' => $url
                ,'Mobile-Device' => $isMobile ? 'true' : 'false'
                ,'Mobile-Browser' => $isMobileBrowser ? 'true' : 'false'
                ,'MyFastAPP-Request' => $isMFApp ? 'true' : 'false'
                ,'Any-Device-Enabled' => $anyDevice ? 'true' : 'false'
                ,'Database-Log' => $insertLog ? 'true' : 'false'
                ,'Event-Id' => $qr_event_id
                ,'User-Key' => $log['user_key'] ?? ''
                ,'Cookie' => $otkey !== false ? $otkey : 'false'
            ], "API Redirect Parameters: " ); // note: it works if WP_DEBUG_LOG constant is true

        $url = $this->getRedirectUrl($url);
        if ( $url != '' ) {

            if ( $insertLog ) {
                if ( $this->database->saveLog( $log ) == false ) {
                    sosidee_log($log, "A problem occurred saving log=");
                }
            }

            wp_redirect( $url, 302, 'Dynamic QR Code plugin' );

        } else {
            sosidee_log('Plugin.apiRedirectByCode(): redirect URL is empty.');
            return new \WP_REST_Response( "Server response: a problem occurred. Please check the Dynamic QR Code plugin configuration.", 500);
        }
        exit();
    }

    private function getRedirectUrl( $path ) {
        $ret = '';
        /** @noinspection HttpUrlsUsage */
        if ( $path != '' && !sosidee_str_starts_with($path, ['https://', 'http://', '//']) ) {

            if ( $this->hasSocial() ) {
                if ( $this->hasFacebook() && $this->addon->facebook::is( $path ) ) {
                    $url = $this->addon->facebook::getUrl($path);
                    if ($url !== false) {
                        $ret = $url;
                    } else {
                        sosidee_log("Addon\Facebook.getUrl() returned false for path: $path");
                    }
                } else if ( $this->hasInstagram() && $this->addon->instagram::is( $path ) ) {
                    $url = $this->addon->instagram::getUrl($path);
                    if ($url !== false) {
                        $ret = $url;
                    } else {
                        sosidee_log("Addon\Instagram.getUrl() returned false for path: $path");
                    }
                } else if ( $this->hasLinkedIn() && $this->addon->linkedin::is( $path ) ) {
                    $url = $this->addon->linkedin::getUrl($path);
                    if ($url !== false) {
                        $ret = $url;
                    } else {
                        sosidee_log("Addon\LinkedIn.getUrl() returned false for path: $path");
                    }
                } else if ( $this->hasWhatsApp() && $this->addon->whatsapp::is( $path ) ) {
                    $url = $this->addon->whatsapp::getUrl($path);
                    if ($url !== false) {
                        $ret = $url;
                    } else {
                        sosidee_log("Addon\WhatsApp.getUrl() returned false for path: $path");
                    }
                } else if ( $this->hasYouTube() && $this->addon->youtube::is( $path ) ) {
                    $url = $this->addon->youtube::getUrl($path);
                    if ($url !== false) {
                        $ret = $url;
                    } else {
                        sosidee_log("Addon\YouTube.getUrl() returned false for path: $path");
                    }
                } else {
                    $ret = $path;
                }
            } else {
                if ( !sosidee_str_starts_with($path, '/') ) {
                    $path = '/' . $path;
                }
                $ret = get_site_url() . $path;
            }
        } else {
            $ret = $path;
        }

        if ( sosidee_str_starts_with($ret, ['https://', 'http://', '//']) ) {
            $this->config->randQsEnabled->load();
            if ( $this->config->randQsEnabled->value ) {
                $key = 'sos' . strval( random_int(6, 666) );
                $value = base64_encode( bin2hex( random_bytes(12) ) );
                $ret = add_query_arg( $key, $value, $ret );
            }
        }
        return $ret;
    }

    private function deleteFiles( $folder ) {
        try {
            foreach ( glob($folder) as $file ) {
                if ( is_file($file) ) {
                    unlink($file);
                }
            }
        } catch ( \Exception $ex ) {
            sosidee_log( $ex->getMessage() );
        }
    }

    public function onDeactivate() {
        $tmp = $this->getTempFolder();
        if ( $tmp !== false ) {
            $this->deleteFiles( $tmp['path'] . '*.png' );
            $this->deleteFiles( $tmp['path'] . '*.csv' );
        }
    }

    public function pro( $style = null, $title = 'PRO version' ) {
        $url = self::$helpUrl . 'pro-version';
        $ret = '<a href="' . esc_url($url) . '" onclick="this.blur();" target="_blank" title="' . esc_attr($title) . '"><i class="dashicons-before dashicons-awards"';
        $style = SOS\WP\HtmlTag::getStyle($style, 'color: #ff0000;');
        if ( !is_null($style) ) {
            $ret .= ' style="' . esc_attr($style) . '"';
        }
        $ret .= '></i></a>';
        return $ret;
    }

    public function htmlAdminPageTitle( $title ) {
        echo '<h1>' . esc_html( $title );
        if ( $this->hasAnyAddon() ) {
            echo ' <sup><i class="dashicons-before dashicons-awards" style="font-size: 100%;" title="pro version"></i></sup>';
        }
        echo '</h1>';
    }

}


/**
 * DO NOT CHANGE BELOW UNLESS YOU KNOW WHAT YOU DO *
**/
$plugin = SosPlugin::instance(); //the class must be the one defined in this file
$plugin->run();
