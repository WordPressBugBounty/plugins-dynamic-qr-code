<?php
/**
 * Plugin Name:       Dynamic QR Code
 * Plugin URI:        https://sosplugin.com/dynamic-qr-code/
 * Description:       Allows you to create dynamic QR codes whose destination can be changed without reprinting the QR code.
 * Version:           2.0.0
 * Requires at least: 5.9
 * Tested up to:      7.1
 * Requires PHP:      8.2
 * Author:            sosidee
 * Author URI:        https://sosidee.com/
 * Text Domain:       dynamic-qr-code
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Sos Addon Version: 1.0
 */

namespace SOSIDEE_DYNAMIC_QRCODE;
( defined( 'ABSPATH' ) and defined( 'WPINC' ) ) or die( 'you were not supposed to be here' );
defined('SOSIDEE_DYNAMIC_QRCODE') || define( 'SOSIDEE_DYNAMIC_QRCODE', true );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

require_once "wp-loader.php";

\SOSIDEE_CLASS_LOADER::instance()->add( __NAMESPACE__, __DIR__ );


/**
 * Class of this Plugin *
**/
class SosPlugin extends SOS\WP\Plugin
{

    use SOS\WP\TMain;
    use SRC\TAddon;

    //pages
    private $pageQrCodes;
    private $pageLogs;

    public $pageQrCode;
    public $pageConfigs;

    private $pagePro;

    //database
    //public $database;
    public $config;

    //forms
    public $formSearchQrCode;
    public $formEditQrCode;
    public $formSearchLog;
    public $formPro;
    //public $formLic;

    private $apiRedirect; //API

    private $mbHC; //metabox for hiding post/page content

    private $scriptReload;
    private $deleteCookie;

    protected function __construct( $_file = '' ) {
        parent::__construct(__FILE__);

        //PLUGIN KEY & NAME 
        $this->setKey('dynamic-qr-code');
        $this->name = 'Dynamic QR Code';

        SRC\FORM\Base::$FLD_HID = SRC\Shortcode::TAG . '_' . SRC\Shortcode::AUTH;

        //if necessary, enable localization
        //$this->internationalize( 'dynamic-qr-code' ); //Text Domain = $this->folder

        $this->setHelp('dynamic-qr-code/');

        $this->enableEvent('deactivation');

        $this->deleteCookie = false;

    }
    protected function initialize() {

        // settings
        $section = $this->addSection('config');
        $this->config = new SRC\FORM\Configs( $section ); // deve essere la prima form a essere creata

        // database: custom tables for the plugin
        $this->database = new SRC\DB\Database();

        $this->apiRedirect = $this->addApiAny('dynamic-qr-code', [ $this, 'handleApiMethod' ] );
        $this->apiRedirect->nonceDisabled = true;

        $mb = $this->addMetaBox( 'post-content', $this->name );
        $this->mbHC = new SRC\Metabox($mb);

        $this->checkAddon();
    }

    protected function initializeBackend() {

        $this->pageQrCodes = $this->addPage('qrcodes' );
        //$this->pageQrCodes->menuHidden = true;
        $this->pageQrCode = $this->addPage('qrcode' );
        $this->pageQrCode->menuHidden = true;
        $this->pageLogs = $this->addPage('logs' );
        //$this->pageLogs->menuHidden = true;
        $this->pageConfigs = $this->addPage('configs' );
        //$this->pageConfigs->menuHidden = true;
        $this->pagePro = $this->addPage('pro' );
        //$this->pagePro->menuHidden = true;

        // assign data cluster to page
        $this->config->setPage( $this->pageConfigs );

        // menu
        $this->menu->icon = '-screenoptions';

        $this->menu->add( $this->pageQrCodes, 'QR-Codes' );
        $this->menu->add( $this->pageQrCode );
        $this->menu->add( $this->pageLogs, 'Scan logs' );
        $this->menu->add( $this->pageConfigs, 'Settings' );
        $this->menu->add( $this->pagePro, 'Go PRO' );

        $this->formSearchQrCode = new SRC\FORM\QrCodeSearch();
        $this->pageQrCodes->addForm( $this->formSearchQrCode );

        $this->formEditQrCode = new SRC\FORM\QrCodeEdit();
        $this->pageQrCode->addForm( $this->formEditQrCode );

        $this->formSearchLog = new SRC\FORM\logSearch();
        $this->pageLogs->addForm( $this->formSearchLog );


        $this->formPro = new SRC\FORM\Pro();
        $this->pagePro->addForm( $this->formPro );

        $this->qsArgs[] = SRC\FORM\QrCodeEdit::QS_ID;

        $this->addScript('admin')->addToPage( $this->pageQrCodes, $this->pageQrCode );
        $this->addScript('qrcode')->addToPage( $this->pageQrCode );
        $this->addScript('log')->addToPage( $this->pageLogs );
        $this->addScript('qrcodes')->addToPage( $this->pageQrCodes );
        $confScript = $this->addScript('config')->addToPage( $this->pageConfigs );
        $this->addStyle('admin')->addToPage( $this->pageQrCodes, $this->pageQrCode, $this->pageLogs, $this->pagePro ); //, $this->pageStats
        $this->addGoogleIcons();
        $this->addGoogleIconsToEditor();

        $this->addDashLink( self::$helpUrl , 'Help' );

        add_action('current_screen', [$this, 'checkConfig']);

        $confScript->addInLineCode( function() {
            $now = sosidee_current_datetime();
            $month = $now->format('m') - 1;
            return 'document.addEventListener("DOMContentLoaded", function() {' . "\n" .
                '    jsDynSetServerDateTime(new Date(' . $now->format('Y') . ', ' . $month . ', ' . $now->format('d') . ', ' . $now->format('H') . ', ' . $now->format('i') . ', ' . $now->format('s') . '));' . "\n" .
                '});';
        });

        $this->addFooterScript( function() {
            $key = wp_json_encode($this->config->cypherLength->getTagKey());
            $base = $this->getApiUrlLength();
            $min = SRC\QrCode::CYPHER_LENGTH_MIN;
            return "jsDynInitializeCypher({$key}, {$base}, {$min});";
        }, $this->pageConfigs );

        $this->scriptReload = null;
    }


    public function addDeleteCookieScript( $js ) {
        $this->addInlineScript( $js, 'cookie-delete' );
    }

    protected function initializeFrontend() {
        add_action('template_redirect', [ $this->mbHC, 'checkPost' ]);
        $this->addShortCode( SRC\Shortcode::TAG, array($this, 'dynqrcode_handle_shortcode') );
        $this->scriptReload = $this->addScript('reload', false);
        $this->scriptReload->manual = true;
    }

    public function checkConfig() {
        /*
        if ( !function_exists('ImageCreate') ) {
            $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> GD library (PHP) not found: please contact your server administrator.";
            self::msgErr( $msg );
        }
        */
        if ( !extension_loaded('gd') || !function_exists('imagecreatetruecolor') || !function_exists('imagepng') ) {
            $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> PHP GD extension not found or incomplete: please contact your server administrator.";
            self::msgErr( $msg );
        }

        if ( $this->pageQrCodes->isCurrent() || $this->pageQrCode->isCurrent() ) {
            if ( !$this->config->check() ) {
                $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> Configuration is not valid: please check <a href=\"{$this->pageConfigs->url}\">{$this->pageConfigs->title}</a>";
                $this::msgErr($msg);
            }
        } else if ( $this->pageLogs->isCurrent() ) {
            if ( !$this->formSearchLog->isPosted() ) {
                $this->config->logDisabled->load();
                if ( $this->config->logDisabled->value == true ) {
                    $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> Logs are currently disabled: please check <a href=\"{$this->pageConfigs->url}\">{$this->pageConfigs->title}</a>";
                    self::msgWarn( $msg );
                }
            }
        }
    }

    protected function hasShortcode( $tag, $attributes ) {
        if (!has_action('template_redirect', [$this, 'handleDeleteCookie'])) {
            add_action('template_redirect', [$this, 'handleDeleteCookie']);
        }
    }

    private function htmlQrCodeImage( $qrcode, $sc ) {
        $ret = '';
        $msg = '';

        if ( $sc->imageType == 'enhanced' ) {
            $cypher = true;
            if ( $sc->timeout > 0 ) {
                $code = SRC\QrCode::getNewCypher();
                $data = [ 'cypher' => $code ];
                if ( $this->database->qrcodes->save( $data, $qrcode->qrcode_id ) ) {
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
            $text = $this->getApiUrl( $code, $cypher );
            $fore_color = SRC\QrCode::getColor( $sc->colorFore );
            $back_color = SRC\QrCode::getColor( $sc->colorBack );

            $parameters = [
                'text' => $text,
                'id' => $qrcode->qrcode_id,
                'size' => $sc->imageSize,
                'pad' => $sc->imagePad,
                'foreColor' => $fore_color,
                'backColor' => $back_color,
                'level' => $sc->imageQuality
            ];
            $data = SRC\QrCode::getString( $parameters );

            $ret = DATA\FormTag::get( 'img', [
                 'src' => "data:image/png;base64,{$data}"
                ,'alt' => 'scan this qr code with your mobile device'
                ,'class' => $sc->cssClass
                ,'title' => 'scan this qr code with your mobile device'
                ,'timeout' => $sc->timeout
            ]);

            if ( $sc->timeout > 0 ) {
                $ms = $sc->timeout * 60 * 1000;
                //$ms = 5000;

                $this->scriptReload->addInLineCode( function() use ($ms) {
                    $js = "jsSosDqcAddEvent( function(e) {";
                    $js .= "setTimeout(function() { const url = new URL(window.location.href); url.searchParams.set('_dqc_ts', Date.now()); window.location.href = url.toString(); }, $ms);";
                    $js .= "});";
                    return $js;
                });

                $this->scriptReload->enqueue();

            }

        }

        if ( $msg != '' ) {
            $ret .= DATA\FormTag::get( 'pre', [
                'content' => "{$this->name}: " . $msg
            ]);
        }

        return $ret;
    }

    public function handleDeleteCookie() {
        if ( $this->deleteCookie ) {
            SRC\OTKey::deleteCookie();
        }
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
            $qrcode = $this->database->qrcodes->load( $sc->id );
            if ( $qrcode !== false ) {

                if ( $sc->mode == SRC\ShortcodeMode::DISPLAY_IMAGE ) {
                    $sc->fillUp( $qrcode );
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
                    //$isMobileBrowser = SOS\Mobile::is() && SOS\Mobile::isBrowser();
                    $isMobileBrowser = SOS\WP\Mobile::is() && SOS\WP\Mobile::browser();
                    $isDeviceEnabled = $isMobileBrowser || $this->config->anyDeviceEnabled->value; //it's a mobile browser OR any device

                    if ( $isDeviceEnabled ) {

                        $key = SRC\OTKey::getCookie();
                        if ( $key != '' ) {
                            $otkey = $this->database->otkeys->load( $key, $qrcode->qrcode_id );
                            if ( $otkey !== false && $otkey->otk_id > 0 ) {
                                $tally = intval( $otkey->tally );
                                $tally++;
                                if ( $this->database->otkeys->save( $otkey->otk_id, $tally ) === false ) {
                                    sosidee_log("DB\OTKey::save({$tally}) - failed for key.id={$otkey->otk_id}");
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
            $this->deleteCookie = true;
        }

        return apply_filters( 'dynqrcode_handle_shortcode', $ret );
    }

    public function getApiUrl( $code = '', $cypher = false ) {
        $base = $this->apiRedirect->getUrl(); //return 'https://this.is.just.a.demo/?rest_route=/rapi/dynamic-qr-code';
        if ( $code === '' || $code === null ) {
            return $base;
        }
        $param = $cypher ? 'cr' : 'qr';
        return sosidee_add_query_arg($param, $code, $base);
    }

    public function getApiUrlLength( $code_length = 0 ) {
        if ( $code_length > 0 ) {
            $code_length = SRC\QrCode::getB64Len($code_length);
        }
        return strlen( $this->getApiUrl() ) + 4 + $code_length;
    }


    private function apiRedirectByData( $data ) {
        $log = &$data['log'];
        $cypher = $data['cypher'] ?? false;
        if ( $cypher ) {
            $code = $data['code'] ?? '';
            $item = $this->database->qrcodes->loadByCypher( $log['code'] );
            if ( $item !== false ) {
                $log['code'] = $item->code;
            } else {
                $log['code'] = '';
                sosidee_log("database::loadQrCodeByCypher('$code') returned false.");
                $this->finalizeApi($data);
            }
        }

        $code = $log['code'];
        $items = $this->database->qrcodes->loadByKey( $code );
        if ( !is_array( $items ) || count( $items ) == 0 ) {
            sosidee_log("Plugin::apiRedirectByData() - database.qrcodes.loadByKey($code) returned false.");
            $this->finalizeApi($data);
        }

        $status = [];
        $qrcs = [];
        $hasPriority = false;
        $info = [
            'preview' => $data['preview'],
            'shared' => $this->config->sharedCodeEnabled->getValue(),
        ];
        foreach ( $items as $item ) {
            $item_status = SRC\QrCode::getStatus( $item, $info );
            $status[$item->qrcode_id] = $item_status;
            $item->count_states = json_decode($item->count_states, true) ?? [];
            if ( $item_status == SRC\QrCodeStatus::ACTIVE ) {
                if ( $item->priority ) {
                    $hasPriority = true;
                }
                $qrcs[] = $item;
            }
        }

        if ( count($qrcs) == 0 ) {
            // prende qualsiasi item purché non disabilitato
            foreach ( $items as $item ) {
                if ( !$item->disabled ) {
                    $qrcs[] = $item;
                    if ( $item->priority ) {
                        $hasPriority = true;
                    }
                }
            }
        }

        if ( count($qrcs) == 0 ) {
            // sono tutti disabilitati
            sosidee_log("Plugin::apiRedirectByData() - QR-Codes with key=$code are all disabled.");
            $this->finalizeApi($data);
            /*
            foreach ( $items as $item ) {
                $qrcs[] = $item;
                if ( $item->priority ) {
                    $hasPriority = true;
                }
            }
            */
        }

        if ( $hasPriority && count($qrcs) > 1 ) { // filter by priority
            $tmps = $qrcs;
            $qrcs = []; //reset
            foreach ( $tmps as $tmp ) {
                if ( $tmp->priority ) {
                    $qrcs[] = $tmp;
                }
            }
        }

        $index = 0;
        if ( count($qrcs) > 1 ) {
            $index = SRC\QrCode::roll( count($qrcs) ); // professor Bohr chooses which one wins
        }

        $qrcode = $qrcs[$index];
        $log['qrcode_id'] = $qrcode->qrcode_id;
        if ( !$qrcode->disabled ) {
            $log['status'] = $status[$qrcode->qrcode_id] ?? SRC\LogStatus::ERROR;
            if ( $log['status'] == SRC\LogStatus::ERROR ) {
                sosidee_log("Plugin::apiRedirectByData() - no qrcode id (" . ($qrcode->qrcode_id ?? '?') . ") found in status array.");
            }
        } else {
            $log['status'] = SRC\QrCodeStatus::DISABLED;
        }

        if ( count($qrcode->count_states) > 0 ) {
            $log['count_me'] = in_array($log['status'], $qrcode->count_states) ? 1 : 0;
        } elseif ( $log['status'] == SRC\QrCodeStatus::DISABLED ) {
            $log['count_me'] = 0;
        }

        if ( !$data['preview'] ) {

            if ( $cypher || $this->config->anyQrHideEnabled->getValue() ) {
                $ot_key = SRC\OTKey::getNew();
                $ot_data = [
                    'qrcode_id' => intval( $qrcode->qrcode_id )
                    ,'code' => $ot_key
                ];

                if ( $this->database->otkeys->save( $ot_data ) ) {
                    SRC\OTKey::setCookie( $ot_key );
                } else {
                    sosidee_log("Plugin::apiRedirectByData() - database.otkeys.save() returned false.");
                }
            }

        } else {
            sosidee_log("Request considered as preview.");
        }

        $data['url'] = SRC\QrCode::getRedirectUrl( $qrcode, $log['status'], $this->config );

        unset($log);
        $this->finalizeApi($data);
    }

    private function finalizeApi( $data ) { //apiRedirectByData

        $log = $data['log'];
        if ( $data['logging'] ) {
            if ( $this->database->logs->save( $log ) === false ) {
                sosidee_log($log, "A problem occurred saving log=");
            }
        }

        $url = $this->getRedirectUrl( $data['url'] );

        if ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
            sosidee_log( [
                'QR-Code-Key' => array_key_exists('code', $data) ? ($data['code'] ? 'true' : 'false') : '?',
                'QR-Code-Id' => $log['qrcode_id'] ?? '?',
                'Redirect-URL' => $url ?? '?',
                'Is-Mobile' => array_key_exists('mobile', $data) ? ($data['mobile'] ? 'true' : 'false') : '?',
                'Is-Preview' => array_key_exists('preview', $data) ? ($data['preview'] ? 'true' : 'false') : '?',
                'Is-Cypher' => array_key_exists('cypher', $data) ? ($data['cypher'] ? 'true' : 'false') : '?',
                'Log-Enabled' => array_key_exists('logging', $data) ? ($data['logging'] ? 'true' : 'false') : '?',
                'Note' => $data['note'] ?? 'none',
            ] );
        }

        wp_redirect( $url, 302, 'Dynamic QR Code plugin' );
        exit();
    }

    private function sendMiddlePage( $html ) {
        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $html;
        exit;
    }

    public function handleApiMethod( \WP_REST_Request $request ) {
        // nota: a questo punto plugins_loaded, init e rest_api_init sono già stati chiamati
        // mentre wp_enqueue_scripts e admin_init non sono ancora stati eseguiti

        $config = $this->config;

        $data = [
            'url' => $config->urlError->getValue(),
            'logging' => false,
            'log' => [
                'code' => '?',
                'status' => SRC\LogStatus::ERROR,
                'qrcode_id' => 0,
                'count_me' => 1,
            ],
            'cypher' => false,
            //'browser' => false,
            'mobile' => false,
            'preview' => false,
        ];

        $method = $request->get_method();
        if ( $method != 'GET' ) {
            sosidee_log("Plugin::handleApiMethod() - Invalid request method: $method");
            $data['note'] = "invalid method: $method";
            $this->finalizeApi($data);
        }

        //pro default: cookie handshake + score
        //opzione extra: JS gate solo quando destinazione finale è https/http (non intent), con beacon e fallback link manuale

        $code = '';
        $qs = $request->get_query_params();
        if ( array_key_exists('qr', $qs) || array_key_exists('cr', $qs) ) {
            if ( array_key_exists('qr', $qs) ) {
                $code = trim( html_entity_decode( urldecode( $qs['qr'] ) ) );
            }  else {
                $decoded = base64_decode( $qs['cr'], true );
                if ( $decoded !== false ) {
                    $code = trim( html_entity_decode( urldecode( $decoded ) ) );
                } else {
                    $data['note'] = "invalid encoded code.";
                    sosidee_log("Plugin::handleApiMethod() - Invalid request parameter: base64_decode() returned false for key 'cr'.");
                }
                $data['cypher'] = true;
            }
            if ( !empty( $code ) ) {
                $code = sanitize_text_field( $code );
                $data['log']['code'] = $code;
            } else {
                sosidee_log("Plugin::handleApiMethod() - Invalid request parameter: missing a query value for key 'qr' or 'cr'.");
                $this->finalizeApi($data);
            }
        } else {
            sosidee_log("Plugin::handleApiMethod() - Invalid request parameter: missing a query key ('qr' or 'cr').");
            $data['note'] = "request parameter: missing code query key.";
            $this->finalizeApi($data);
        }

        $isMobile = SOS\WP\Mobile::is();
        $data['mobile'] = $isMobile;
        $deviceEnabled = $config->anyDeviceEnabled->getValue() || $isMobile;
        if ( !$deviceEnabled ) {
            sosidee_log("Plugin::handleApiMethod() - Scanning device is not enabled.");
            $data['note'] = "unabled device.";
            $this->finalizeApi($data);
        }

        if ( $isMobile ) {
            $threshold = $config->scanValidationLevel->getValue();
            sosidee_log("threshold for scan validation level: $threshold");
            if ( $threshold != SOS\WP\RealBrowserThreshold::NONE ) {
                $realBrowser = SOS\WP\Mobile::realBrowser($threshold);
            } else {
                $realBrowser = true;
            }

            $info = [
                'api-url' => $this->getApiUrl($code, $data['cypher']),
                'real-browser' => $realBrowser,
                'code' => $code,
            ];
            $decisions = $this->applyFilter('_api_preview', false, $config, $info );
            if ( is_array($decisions) ) {
                $mp = $decisions['middle-page'] ?? false;
                if ( $mp ) {
                    $html = $decisions['html'] ?? false;
                    if ( $html ) {
                        $this->sendMiddlePage($html);
                    } else {
                        sosidee_log("Plugin::handleApiMethod() - no html for the middle page.");
                    }
                }
                $isPreview = $decisions['preview'] ?? false;
            } else {
                $isPreview = !$realBrowser;
            }
        } else {
            $isPreview = false;
        }

        //$data['browser'] = $realBrowser;
        $data['preview'] = $isPreview;

        $enabledLog = !$config->logDisabled->getValue();

        $data['logging'] = $enabledLog && !$isPreview;
        if ( $data['logging'] ) {
            $log = &$data['log'];

            $log['dev_type'] = $isMobile ? SRC\DeviceType::MOBILE : SRC\DeviceType::NOT_MOBILE;
            $log['lang'] = SOS\WP\Request::getBrowserMainLanguage();
            $log = $this->applyFilter('_request_get_geolocation', $log, $config );
            $log['op_sys'] = $this->applyFilter('_request_get_os', SRC\OS::UNKNOWN );

            unset($log);
            //sosidee_log("it will be logged.");
        } else {
            //sosidee_log("it won't be logged.");
        }

        $this->apiRedirectByData($data);
    }


    public function getRedirectUrl( $path ) {
        $ret = '';
        /** @noinspection HttpUrlsUsage */
        if ( $path != '' && !sosidee_str_starts_with($path, ['https://', 'http://', '//']) ) {

            $url = $this->applyFilter('_social_get_redirect_url', false, $path);
            if ( $url !== false ) {
                $ret = $url;
            } else {
                if ( !sosidee_str_starts_with($path, '/') ) {
                    $path = '/' . $path;
                }
                $ret = get_site_url() . $path;
            }
        } else {
            $ret = $path;
        }

        if ( empty($ret) ) {
            $ret =  $this->config->urlError->getValue();
        }

        if ( sosidee_str_starts_with($ret, ['https://', 'http://', '//']) ) {
            if ( $this->config->randQsEnabled->getValue() ) {
                try {
                    $key   = 'sos' . random_int(6, 666);
                    $value = base64_encode(bin2hex(random_bytes(12)));
                } catch ( \Exception $_ ) {
                    // fallback deterministico se l'entropia di sistema non è disponibile
                    $key   = 'sos' . mt_rand(6, 666);
                    $value = base64_encode(bin2hex(pack('H*', uniqid('', true))));
                }
                $ret = add_query_arg( $key, $value, $ret );
            }

            if ( sosidee_is_dev() ) {
                $ret = SOS\WP\Http::replaceLocalHostWithIp( $ret );
            }
        }


        return $ret;
    }

    /*
    private function deleteFiles( $folder ) {
        try {
            foreach ( SOS\WP\FileSystem::getFiles( $folder ) as $file  ) {
                SOS\WP\FileSystem::deleteFile( $file );
            }
        } catch ( \Exception $ex ) {
            sosidee_log( $ex->getMessage() );
        }
    }
    */

    public function onDeactivate() {
        SRC\IO::delete();
    }

    public function pro( $style = null, $title = 'available in the PRO version' ) {
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
        $title = apply_filters($this->key . '_admin_page_title', $title);
        echo '<h1>' . wp_kses( $title, sosidee_allowed_html() ) . '</h1>';
    }

}


/**
 * DO NOT CHANGE BELOW UNLESS YOU KNOW WHAT YOU DO *
**/
$plugin = SosPlugin::instance(); //the class must be the one defined in this file
$plugin->run();
