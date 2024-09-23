<?php
/*
Plugin Name: Dynamic QR Code
Version: 0.9.3
Description: Allows you to create DYNAMIC QR CODES: you can modify what happens when scanning your QR code without actually modifying (and reprinting) the QR code.
Author: SOSidee.com srl
Author URI: https://sosidee.com
Text Domain: dynamic-qr-code
Domain Path: /languages
*/
namespace SOSIDEE_DYNAMIC_QRCODE;
( defined( 'ABSPATH' ) and defined( 'WPINC' ) ) or die( 'you were not supposed to be here' );
defined('SOSIDEE_DYNAMIC_QRCODE') || define( 'SOSIDEE_DYNAMIC_QRCODE', true );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

require_once "loader.php";

\SOSIDEE_CLASS_LOADER::instance()->add( __NAMESPACE__, __DIR__ );

/**
 * Class of This Plugin *
 *
**/
class SosPlugin extends SOS\WP\Plugin
{

    private static $helpUrl = 'https://redirect.soslink.net/dynamic-qr-code/help/';

    //pages
    private $pageQrCodes;
    private $pageLogs;
    public $pageQrCode;
    public $pageConfig;

    //database
    public $database;
    public $config;

    //forms
    public $formSearchQrCode;
    public $formEditQrCode;
    public $formSearchLog;

    //API
    private $apiRedirect;

    private $mbHC; //metabox for hiding post/page content

    public static $FLD_HID;

    protected function __construct() {
        parent::__construct();

        //PLUGIN KEY & NAME 
        $this->key = 'sos-dynamic-qr-code';
        $this->name = 'Dynamic QR Code';

        self::$FLD_HID = SRC\Shortcode::TAG . '_' . SRC\Shortcode::AUTH;

        //if necessary, enable localization
        //$this->internationalize( 'dynamic-qr-code' ); //Text Domain
    }

    protected function initialize() {
        parent::initialize();

        // settings
        $section = $this->addSection('config', 'Settings');
        //$section->validate = array($this, 'validateConfig'); //moved to form/config //function to be called on configuration data saving
        $this->config = new SRC\FORM\Config( $section );

        // database: custom tables for the plugin
        $this->database = new SRC\Database();

        $this->apiRedirect = $this->addApiAny('dynamic-qr-code', [$this, 'apiRedirectByCode'], 0 );
        $this->apiRedirect->nonceDisabled = true;

        $this->mbHC = $this->addMetaBox( 'post-content', $this->name );
        $this->mbHC->addField( 'qid', 0 );
        $this->mbHC->addField( 'form', false, true );
        $this->mbHC->html = [$this, 'htmlMetabox'];
        $this->mbHC->callback = [$this, 'saveMetabox'];

    }

    protected function initializeBackend() {

        $this->pageQrCodes = $this->addPage('qrcodes' );
        $this->pageQrCode = $this->addPage('qrcode' );
        $this->pageLogs = $this->addPage('logs' );
        $this->pageConfig = $this->addPage('config' );

        //assign data cluster to page
        $this->config->setPage( $this->pageConfig );

        //menu
        $this->menu->icon = '-screenoptions';

        $this->menu->add( $this->pageQrCodes, 'QR-Codes' );
        $this->menu->addHidden( $this->pageQrCode );
        $this->menu->add( $this->pageLogs, 'Scan logs' );
        $this->menu->add( $this->pageConfig, 'Settings' );

        $this->formSearchQrCode = new SRC\FORM\QrCodeSearch();
        $this->formSearchQrCode->addToPage( $this->pageQrCodes );

        $this->formEditQrCode = new SRC\FORM\QrCodeEdit();
        $this->formEditQrCode->addToPage( $this->pageQrCode );

        $this->formSearchLog = new SRC\FORM\logSearch();
        $this->formSearchLog->addToPage( $this->pageLogs );

        $this->qsArgs[] = SRC\FORM\QrCodeEdit::QS_ID;

        $this->addScript('admin')->addToPage( $this->pageQrCodes, $this->pageQrCode );
        $this->addScript('qrcode')->addToPage( $this->pageQrCode );
        $this->addScript('config')->addToPage( $this->pageConfig );
        $this->addStyle('admin')->addToPage( $this->pageQrCodes, $this->pageQrCode, $this->pageLogs );
        $this->addGoogleIcons();
        $this->addGoogleIconsToEditor();

        $this->addDashLink( self::$helpUrl , 'Help' );

        add_action('current_screen', [$this, 'checkConfig']);

    }

    public function loadQrCodeList( $caption = '' ) {
        $ret = [];
        if ( $caption != '' ) {
            $ret[0] = $caption;
        }

        $results = $this->database->loadQrCodeList();

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

    public function htmlMetabox( $metabox, $post ) {

        echo '<p>Prevent users to view the post/page if not accessed by scanning the image of this QR-Code: ';
        echo $this->help('hide-2', 'float: right;');
        echo '</p>';
        $options = $this->loadQrCodeList('- select -');
        $qid = $metabox->getField('qid');
        echo '<p>';
        echo $qid->getSelect( [ 'options' => $options ] );
        echo '</p>';
        $form = $metabox->getField('form');
        echo '<p>';
        echo $form->getCheckbox("allow reloading this post/page by submitting the form(s) contained in it");
        echo '</p>';

    }

    public function saveMetabox( $metabox, $post, $update ) {
        $res = $metabox->save( $post );
        if ( $res === false ) {
            $metabox->err("{$this->name}: cannot save the data.");
        }
    }

    private function setJsCookieEraser() {
        $cookie = SRC\OTKey::COOKIENAME;
        $js = "document.cookie = '{$cookie} =; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';";
        $this->addInlineScript( $js, 'cookie-delete' );
    }

    private function getJsRedirect( $url ) {
        $ret = '<p style="font-style: italic;">';
        $ret .= SOS\WP\DATA\FormTag::get( 'img', [
            'alt' => 'waiting...'
            ,'src' => $this->getLoaderSrc(24)
            ,'width' => '12px'
        ]);
        $ret .= ' redirecting...</p>';

        $url = $this->getRedirectUrl( esc_url( $url ) );
        $js = <<<EOD
            self.window.location.replace('{$url}');
EOD;
        $ret .= SOS\WP\DATA\FormTag::get( 'script', [
            'type' => 'application/javascript'
            ,'content' => $js
        ]);

        return $ret;
    }

    private function isFormPosted() {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST';
    }

    private function checkPostedForm( $value ) {
        $ret = false;
        if ( $this->isFormPosted() ) {
            switch ( $this->config->formCheckMode->value ) {
                case SRC\FORM\CheckMode::METHOD:
                    $ret = true;
                    break;
                case SRC\FORM\CheckMode::REFERER:
                    $ref = wp_get_raw_referer();
                    $pid = url_to_postid($ref);
                    if ( $pid == get_the_ID() ) {
                        $ret = true;
                    } else {
                        sosidee_log("Hiding content: data posted from an invalid URL. Referer=$ref");
                    }
                    break;
                case SRC\FORM\CheckMode::FIELD:
                    if ( isset($_POST[self::$FLD_HID]) ) {
                        $hid = trim( $_POST[self::$FLD_HID] );
                        if ( strcasecmp( $hid, $value) == 0 ) {
                            $ret = true;
                        } else {
                            sosidee_log("Hiding content: hidden field value is {$hid} while {$value} was expected.");
                        }
                    } else {
                        sosidee_log("Hiding content: hidden field not found.");
                    }
                    break;
            }
        } else {
            sosidee_log("Hiding content: invalid REQUEST_METHOD value.");
        }
        return $ret;
    }

    public function checkMetaboxPost( $content ) {
        $this->mbHC->load();
        $qid = $this->mbHC->getField('qid');

        if ( $qid->value > 0 ) {
            $show = false;
            $id = $qid->value;
            $form = $this->mbHC->getField('form');
            $hasForm = boolval( $form->value );
            $delete_cookie = true;

            $isMobileBrowser = SRC\Mobile::is() && SRC\Mobile::isBrowser();
            $this->config->load(); // load current configuration
            $isDeviceEnabled = $isMobileBrowser || $this->config->anyDeviceEnabled->value; //it's a mobile browser OR any device

            if ( $isDeviceEnabled ) {
                //controllare il valore del cookie, e poi ...
                $key = SRC\OTKey::getCookie();
                if ( $key != '' ) {
                    $otkey = $this->database->loadOTKey( $key, $id );
                    if ( $otkey !== false && $otkey->otk_id > 0 ) {
                        $tally = intval( $otkey->tally );
                        $tally++;
                        if ( $this->database->updateOTKey( $otkey->otk_id, $tally ) == false ) {
                            sosidee_log("database.updateOTKey({$tally}) failed for key.id={$otkey->otk_id}");
                        }
                        if ( $tally == 1 ) {
                            if ( $hasForm ) {
                                $delete_cookie = false;
                            }
                            $show = true;
                        } else {
                            if ( $hasForm && $this->checkPostedForm( $id ) ) {
                                $show = true;
                                $delete_cookie = false;
                            } else {
                                sosidee_log("Hiding content: cookie already used.");
                            }
                        }
                    } else {
                        if ( $otkey !== false ) {
                            sosidee_log("Hiding content: cookie key not found in the database.");
                        } else {
                            sosidee_log("Hiding content: a problem occurred while reading the cookie key in the database.");
                        }
                    }
                } else {
                    sosidee_log("Hiding content: cookie not found.");
                }
            } else {
                sosidee_log("Hiding content: device not enabled.");
            }

            if ( $delete_cookie ) {
                $this->setJsCookieEraser();
            }

            if ( $show ) {
                $ret = $content;
            } else {
                $qrcode = $this->database->loadQrCode( $id );
                if ( $qrcode !== false ) {
                    $url = $qrcode->url_cypher;
                } else {
                    $url = '';
                    sosidee_log("database.loadQrCode({$qid->value}) returned false.");
                }
                if ( $url == '') {
                    $url = $this->config->urlError->value;
                }
                $ret = $this->getJsRedirect( $url );
            }

        } else {
            $ret = $content;
        }

        return $ret;
    }

    protected function initializeFrontend() {
        add_filter( 'the_content', [ $this, 'checkMetaboxPost' ] );
        $this->addShortCode( SRC\Shortcode::TAG, array($this, 'dynqrcode_handle_shortcode') );
    }

    public function checkConfig() {

        if ( $this->pageQrCodes->isCurrent() || $this->pageQrCode->isCurrent() ) {
            if ( !$this->config->check() ) {
                $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> Configuration is not valid: please check <a href=\"{$this->pageConfig->url}\">{$this->pageConfig->title}</a>";
                $this::msgErr($msg);
            }
        } else if ( $this->pageLogs->isCurrent() ) {
            if ( !$this->formSearchLog->_posted ) {
                $this->config->logDisabled->load();
                if ( $this->config->logDisabled->value == true ) {
                    $msg = "<span class=\"dashicons dashicons-admin-generic\"></span> Logs are currently disabled: please check <a href=\"{$this->pageConfig->url}\">{$this->pageConfig->title}</a>";
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

        if ( $msg != '') {
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
                    $isMobileBrowser = SRC\Mobile::is() && SRC\Mobile::isBrowser();
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
                                    if ( $sc->hasForm && $this->checkPostedForm( $sc->id ) ) {
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
            $this->setJsCookieEraser();
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

    private function getCopy2CBIcon( $text, $title ) {
        return '<a href="javascript:void(0);" onclick="jsSosCopy2Clipboard(\'' . esc_js($text) . '\')" title="' . esc_attr($title) . '" style="width: inherit;"><i class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit;">content_copy</i></a>';
    }

    private function getCopy2CBAlert( $text, $title ) {
        return '<a href="javascript:void(0);" onclick="alert(\'' . esc_js($text) . '\')" title="' . esc_attr($title) . '" style="width: inherit;"><i class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit;">content_copy</i></a>';
    }

    public function getShortcode1Template( $id ) {
        $ret = '[' . SRC\Shortcode::TAG . ' ' . SRC\Shortcode::AUTH . "={$id}]";
        $ret .= 'content displayed to QR code scanners';
        $ret .= '[/' . SRC\Shortcode::TAG . ']';
        return $ret;
    }

    public function getShortcode2Template( $id, $standard ) {
        $ret = '[' . SRC\Shortcode::TAG . ' ' . SRC\Shortcode::DISPLAY . "={$id}";
        if ( $standard ) {
            $ret .= ' ' . SRC\Shortcode::IMAGE . '="standard"';
        }
        $ret .= ']';
        return $ret;
    }

    public function getHiddenFieldTemplate( $id ) {
        return DATA\FormTag::get( 'input', [
             'type' => 'hidden'
            ,'name' => self::$FLD_HID
            ,'value' => $id
        ]);
    }

    public function getCopyApiUrl2CBIcon( $id, $code, $cypher = false ) {
        $title = "copy QR-Code URL to clipboard";
        if ( $id > 0 && $code != '' ) {
            return $this->getCopy2CBIcon(  $this->getApiUrl($code, $cypher), $title );
        } else {
            if ( $id <= 0 ) {
                return $this->getCopy2CBAlert( "Please save the QR-Code before copying the URL to clipboard.", $title );
            } else {
                if ( !$cypher ) {
                    return $this->getCopy2CBAlert( "Attention: key is empty.", $title );
                } else {
                    return $this->getCopy2CBAlert( "Please generate the enhanced QR-Code image before copying the URL to clipboard.", $title );
                }
            }
        }
    }

    public function getCopyShortcode2CBIcon( $id, $index = 1, $standard = false ) {
        $title = "copy shortcode to clipboard";
        if ( $id > 0 ) {
            if ( $index == 1 ) {
                $text = $this->getShortcode1Template( $id );
            } else if ( $index == 2 ) {
                $text = $this->getShortcode2Template( $id, $standard );
            } else {
                $text = 'a problem occurred';
            }
            return $this->getCopy2CBIcon( $text , $title );
        } else {
            return $this->getCopy2CBAlert( "Please save the QR-Code before copying the shortcode to clipboard.", $title );
        }
    }

    public function getCopyHiddenField2CBIcon( $id ) {
        $title = "copy hidden field to clipboard";

        if ( $id > 0 ) {
            $text = $this->getHiddenFieldTemplate( $id );
            return $this->getCopy2CBIcon(  $text , $title );
        } else {
            return $this->getCopy2CBAlert( "Please generate the enhanced QR-Code image before copying the hidden field to clipboard.", $title );
        }
    }

    public function getCopyApiRoot2CBIcon() {
        $title = "copy URL for MyFast App to clipboard";
        return $this->getCopy2CBIcon(  $this->getApiUrl(), $title );
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

        $isMobile = SRC\Mobile::is();
        $deviceEnabled = $isMobile || $anyDevice; //it's mobile OR any device

        $isMobileBrowser = SRC\Mobile::isBrowser();
        if ( $isMFApp ) {
            $insertLog = !$this->config->logDisabled->value;
        } else {
            $insertLog = !$this->config->logDisabled->value && (!$isMobile || $isMobileBrowser);
        }

        $isCypher = false;
        $otkey = false;

        $method = $request->get_method();
        if ( $method == 'GET' && $deviceEnabled ) {
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
                    if ( $item->only_mfa && !$isMFApp ) {
                        $status = SRC\QrCodeStatus::DISABLED;
                    } else {
                        $status = SRC\QrCode::getStatus( $item );
                    }
                    $item->status = $status;
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

                $log['status'] = $qrcode->status;

                if ( $isCypher || $this->config->anyQrHideEnabled->value ) {
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
                ,'cookie' => $otkey !== false ? $otkey : 'false'
            ], "API Redirect Parameters: " ); // note: it works if WP_DEBUG_LOG is true

        if ( $url != '' ) {
            $url = $this->getRedirectUrl( $url );

            if ( $insertLog ) {
                if ( $this->database->saveLog( $log ) == false ) {
                    sosidee_log($log, "A problem occurred saving log=");
                }
            }

            wp_redirect( $url, 302, 'Dynamic QR Code by SOSidee.com' );

        } else {
            return new \WP_REST_Response( "Server response: a problem occurred. Please check the Smart QR Code plugin configuration.", 500);
        }
        exit();
    }

    private function getRedirectUrl( $path ) {
        $ret = $path;
        if ( !sosidee_str_starts_with($ret, ['https://', 'http://', '//']) ) {
            if ( !sosidee_str_starts_with($ret, '/') ) {
                $ret = '/' . $ret;
            }
            $ret = get_site_url() . $ret;
        }
        if ( $this->config->randQsEnabled->value ) {
            $key = 'sos' . strval( random_int(6, 666) );
            $value = base64_encode( bin2hex( random_bytes(12) ) );
            $ret = add_query_arg( $key, $value, $ret );
        }
        return $ret;
    }

    private function deleteFiles( $folder ) {
        foreach ( glob($folder) as $file ) {
            if ( is_file($file) ) {
                unlink($file);
            }
        }
    }
    public function onDeactivate() {
        $tmp = $this->getTempFolder();
        if ( is_array($tmp) && key_exists('basedir', $tmp) ) {
            $this->deleteFiles( $tmp['basedir'] . '/*.png' );
            $this->deleteFiles( $tmp['basedir'] . '/*.csv' );
        }
    }

    public function help( $path = '', $style = 'margin: 0.5em; float: right;' ) {
        $url = self::$helpUrl . $path;
        $ret = '<a href="' . esc_url($url) . '" onclick="this.blur();" target="_blank" title="help"><i class="material-icons"';
        if ( !is_null($style) ) {
            $color = 'color: #ffcc00;';
            if ( $style != '' ) {
                $style = $color . ' ' . $style;
            } else {
                $style = $color;
            }
            $ret .= ' style="' . esc_attr($style) . '"';
        }
        $ret .= '>help</i></a>';
        return $ret;
    }

}


/**
 * DO NOT CHANGE BELOW UNLESS YOU KNOW WHAT YOU DO *
**/
$plugin = SosPlugin::instance(); //the class must be the one defined in this file
$plugin->run();


// this is the end (A B C)