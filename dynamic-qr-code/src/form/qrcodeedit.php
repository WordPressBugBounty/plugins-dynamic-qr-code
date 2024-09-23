<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

class QrCodeEdit extends Base
{
    const QS_ID = 'dqc-id';

    public $id;

    private $disabled;
    private $description;
    private $url_redirect;
    private $url_inactive;
    private $url_expired;
    private $date_start;
    private $date_end;
    private $time_start;
    private $time_end;
    private $dotw;
    private $priority;
    private $max_scan_tot;
    private $url_finished;
    private $only_mfa;
    private $device_os;
    private $device_lang;
    private $img_forecolor;
    private $img_backcolor;

    public $code;

    public $cypher;
    private $url_cypher;

    public $showCypher;

    public function __construct() {
        parent::__construct( 'qrcodeEdit', [$this, 'onSubmit'] );

        $this->id = $this->addHidden('id', 0);
        $this->disabled = $this->addCheckBox('disabled', false);
        $this->code = $this->addTextBox('code', $this->getRandomCode());
        $this->description = $this->addTextBox('description', '');
        $this->url_redirect = $this->addComboBox('url_redirect', '');
        $this->url_inactive = $this->addComboBox('url_inactive', '');
        $this->url_expired = $this->addComboBox('url_expired', '');
        $this->date_start = $this->addDatePicker('valid_from_date');
        $this->date_end = $this->addDatePicker('valid_to_date');
        $this->time_start = $this->addTimePicker('valid_from_time');
        $this->time_end = $this->addTimePicker('valid_to_time');
        $this->dotw = $this->addSelect('week_day');
        $this->priority = $this->addCheckBox('priority', false);
        $this->max_scan_tot = $this->addNumericBox('max_scan_tot');
        $this->url_finished = $this->addComboBox('url_finished', '');
        $description = self::getDescription("mandatory field", true);
        $this->description->description = $description;
        $this->code->description = $description;
        $this->url_redirect->description = $description;
        $this->cypher = $this->addHidden('cypher', '');
        $this->url_cypher = $this->addComboBox('url_cypher', '');
        $this->only_mfa = $this->addCheckBox('only_mfa', false);
        $this->device_os = $this->addSelect('device_os', SRC\OS::NONE);
        $this->device_lang = $this->addSelect('device_lang', '');

        $this->img_forecolor = $this->addColorPicker('img_forecolor', $this->_plugin->config->imgForeColor->getValue() );
        $this->img_backcolor = $this->addColorPicker('img_backcolor', $this->_plugin->config->imgBackColor->getValue() );

        $this->showCypher = false;

    }

    public function reset() {
        $this->id->value = 0;
        $this->disabled->value = false;
        $this->code->value = $this->getRandomCode();
        $this->description->value = '';
        $this->url_redirect->value = '';
        $this->url_inactive->value = '';
        $this->url_expired->value = '';
        $this->date_start->value = 'now';
        $this->date_end->value = 'now';
        $this->time_start->value = '';
        $this->time_end->value = '';
        $this->dotw->value = 0;
        $this->priority->value = false;
        $this->max_scan_tot->value = 0;
        $this->url_finished->value = '';
        $this->cypher->value = '';
        $this->url_cypher->value = '';
        $this->only_mfa->value = false;
        $this->device_os->value = SRC\OS::NONE;
        $this->device_lang->value = '';
        $this->img_forecolor->value = $this->_plugin->config->imgForeColor->value;
        $this->img_backcolor->value = $this->_plugin->config->imgBackColor->value;

        $this->showCypher = false;
    }

    public function htmlId() {
        $this->id->html();
    }
    public function htmlDisabled() {
        $this->disabled->html( ['label' => 'select to disable this QR-Code'] );
    }
    public function htmlCode() {
        $this->code->html( ['maxlength' => 255] );
    }
    public function htmlDescription() {
        $this->description->html( ['maxlength' => 255] );
    }
    public function htmlUrlRedirect() {
        $options = Base::getOptions();
        $this->url_redirect->html( [ 'options' => $options ] );
    }
    public function htmlUrlInactive() {
        $options = Base::getOptions();
        $this->url_inactive->html( [ 'options' => $options ] );
    }
    public function htmlUrlExpired() {
        $options = Base::getOptions();
        $this->url_expired->html( [ 'options' => $options ] );
    }
    public function htmlDateStart() {
        $this->date_start->html();
    }
    public function htmlDateEnd() {
        $this->date_end->html();
    }
    public function htmlTimeStart() {
        $this->time_start->html();
    }
    public function htmlTimeEnd() {
        $this->time_end->html();
    }
    public function htmlPriority() {
        $this->priority->html( ['label' => 'priority on other QR-Codes with the same key'] );
    }
    public function htmlOnlyMFA() {
        $this->only_mfa->html( ['label' => 'enables scans made only with MyFast APP applications'] );
    }
    public function htmlDotW() {
        $options = SRC\DotW::getList('- always -');
        $this->dotw->html( [ 'options' => $options ] );
    }
    public function htmlMaxScanTot() {
        $this->max_scan_tot->html([
             'min' => 0
            ,'max' => 2147483647
        ]);
    }
    public function htmlUrlFinished() {
        $options = Base::getOptions();
        $this->url_finished->html( [ 'options' => $options ] );
    }
    public function htmlCurrentScan() {
        if ( $this->id->value > 0 && $this->code->value != '' ) {
            $current = $this->_database->countActiveLogs( $this->code->value );
            if ( $current !== false ) {
                DATA\FormTag::html( 'span', [
                     'content' => $current
                    ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
                ]);
            }
        }
    }

    public function htmlDeviceOS() {
        $options = SRC\OS::getList('- any -');
        $this->device_os->html( [ 'options' => $options ] );
    }
    public function htmlDeviceLang() {
        $options = $this->getlanguageList('- any -');
        $this->device_lang->html( [ 'options' => $options ] );
    }
    public function htmlImgForeColor() {
        $this->img_forecolor->html();
    }
    public function htmlImgBackColor() {
        $this->img_backcolor->html();
    }

    public function htmlCypher() {
        $this->cypher->html();
    }
    public function htmlUrlCypher() {
        if ( $this->cypher->value != '' ) {
            $options = Base::getOptions();
            $this->url_cypher->html( [ 'options' => $options ] );
        } else {
            DATA\FormTag::html( 'label', [
                'html' => ' &nbsp; - - - '
                ,'style' => 'padding:2px 4px 2px 4px;margin-left:1em;'
            ]);
        }
    }

    public function htmlQId() {
        $id = $this->id->value;
        if ( $id > 0 ) {
            DATA\FormTag::html( 'span', [
                'content' => "Q-{$id}"
                ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
            ]);
        }
    }

    public function htmlQRUrl( $cypher = false ) {
        $id = $this->id->value;
        $code = !$cypher ? $this->code->value : base64_encode($this->cypher->value);
        $content = $code != '' ? $this->_plugin->getApiUrl( $code, $cypher ) : ' - - - ';
        if ( $code != '' && $cypher ) {
            $content = substr($content, 0, 80) .  " ...";
        }
        echo $this->_plugin->getCopyApiUrl2CBIcon( $id, $code, $cypher );
        echo '&nbsp; ';
        DATA\FormTag::html( 'label', [
             'content' => $content
            ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
        ]);
    }

    public function htmlQRShortcode1() {
        $id = $this->id->value;
        if ( $id > 0 ) {
            $text = $this->_plugin->getShortcode1Template( $id );
            echo $this->_plugin->getCopyShortcode2CBIcon( $id, 1 );
            echo '&nbsp; ';
            DATA\FormTag::html( 'label', [
                'content' => $text
                ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
            ]);
        }
    }

    public function htmlQRShortcode2() {
        $id = $this->id->value;
        if ( $id > 0 ) {
            $standard = $this->cypher->value == '';
            $text = $this->_plugin->getShortcode2Template( $id, $standard );
            echo $this->_plugin->getCopyShortcode2CBIcon( $id, 2, $standard );
            echo '&nbsp; ';
            DATA\FormTag::html( 'label', [
                'content' => $text
                ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
            ]);
        }
    }

    public function htmlFormHiddenField( $anyQrHideEnabled ) {
        $id = $this->id->value;
        if ( $id > 0 ) {
            if ( $anyQrHideEnabled || $this->cypher->value != '' ) {
                echo $this->_plugin->getCopyHiddenField2CBIcon( $id );
                $text = $this->_plugin->getHiddenFieldTemplate( $id );
            } else {
                echo $this->_plugin->getCopyHiddenField2CBIcon( -1 );
                $text = ' - - - ';
            }
            echo '&nbsp; ';
            DATA\FormTag::html( 'label', [
                'content' => $text
                ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
            ]);
        }
    }

    public function htmlImgQR( $cypher = false ) {
        $id = $this->id->value;
        if ( $id > 0) {

            $code_raw = !$cypher ? $this->code->value : $this->cypher->value;
            $code = !$cypher ? $code_raw : base64_encode($code_raw);

            if ( $code != '' ) {
                $url = $this->_plugin->getApiUrl( $code, $cypher );

                $size = $this->_plugin->config->imgSize->getValue();
                $pad = $this->_plugin->config->imgPad->getValue();

                $fore_color = SRC\QrCode::getColor( $this->img_forecolor->value );
                $back_color = SRC\QrCode::getColor( $this->img_backcolor->value );

                $name = ( !$cypher ? "q_" : "c_" ) . sha1( "{$url}_{$id}_{$size}_{$pad}_{$fore_color}_{$back_color}" );

                $img_url = SRC\QrCode::getUrl( $name, $url, $size, $pad, $fore_color, $back_color );

                $img = DATA\FormTag::get( 'img', [
                    'src' => $img_url
                    ,'alt' => 'click to download'
                    ,'style' => 'margin: 2px;'
                ] );

                echo '<div style="display: flex; justify-content: center; flex-wrap: wrap; margin-top: 2em;">';
                    echo '<div style="flex-basis: 100%; text-align: center; font-style: italic;">click the image to download it</div>';
                    DATA\FormTag::html( 'a', [
                        'href' => $img_url
                        ,'title' => 'click to download'
                        ,'download' => uniqid()
                        ,'html' => $img
                    ] );
                    echo '<div style="flex-basis: 100%; text-align: center;">size: ' . esc_attr($size) , 'x' . esc_attr($size) . '</div>';
                echo '</div>';
            }
        }
    }

    public function htmlCancelCypher() {
        if ( $this->id->value > 0 && $this->cypher->value != '' ) {
            $message = htmlentities( 'Are you sure to cancel the enhanced QR code?' );
            $onclick = "return self.confirm('$message');";
            $value = 'cancel';
            $this->htmlButton( 'cancel_cypher', $value, DATA\FormButton::STYLE_DANGER, null, $onclick );
        }
    }

    public function htmlGenerateCypher() {
        if ( $this->id->value > 0 ) {
            $action = 'generate_cypher';
            $value = 'generate';
            $style = null;
            if ( $this->cypher->value != '' ) {
                $value = 'generate new';
            }
            $this->htmlButton( $action, $value, $style );
        }
    }
    public function htmlSaveCypher() {
        if ( $this->cypher->value != '' ) {
            $action = 'save_cypher';
            $value = 'save';
            $this->htmlButton( $action, $value, DATA\FormButton::STYLE_SUCCESS );
        }
    }


    public function loadQrCode( $id ) {
        if ( $id > 0 ) {
            $qrcode= $this->_database->loadQrCode( $id );
            if ( $qrcode !== false ) {

                $this->id->value = $qrcode->qrcode_id;
                $this->disabled->value = $qrcode->disabled;
                $this->code->value = $qrcode->code;
                $this->description->value = $qrcode->description;
                $this->url_redirect->value = $qrcode->url_redirect;
                $this->url_inactive->value = $qrcode->url_inactive;
                $this->url_expired->value = $qrcode->url_expired;
                $this->date_start->setValueFromDate( $qrcode->date_from );
                $this->date_end->setValueFromDate( $qrcode->date_to );
                $this->time_start->setValueFromTime( $qrcode->time_from );
                $this->time_end->setValueFromTime( $qrcode->time_to );
                $this->priority->value = $qrcode->priority;
                $this->max_scan_tot->value = $qrcode->max_scan_tot;
                $this->url_finished->value = $qrcode->url_finished;
                $this->dotw->value = $qrcode->dotw;
                $this->cypher->value = $qrcode->cypher;
                $this->url_cypher->value = $qrcode->url_cypher;
                $this->only_mfa->value = $qrcode->only_mfa;
                $this->device_os->value = $qrcode->device_os;
                $this->device_lang->value = $qrcode->device_lang;
                $this->img_forecolor->value = $qrcode->img_forecolor;
                $this->img_backcolor->value = $qrcode->img_backcolor;

            } else {
                self::msgErr( "A problem occurred while reading the database." );
            }
        } else {
            self::msgErr( "A problem occurred: record id is zero." );
        }
    }

    public function htmlButtonLink( $id ) {
        $url = $this->_plugin->pageQrCode->getUrl( [self::QS_ID => $id] );
        if ( $id == 0 ) {
            parent::htmlLinkButton( $url, 'create new' );
        } else {
            parent::htmlLinkButton( $url, 'edit', DATA\FormButton::STYLE_SUCCESS );
        }
    }


    protected function initialize() {
        if ( !$this->_posted ) {
            $id = sosidee_get_query_var(self::QS_ID, 0);
            if ( $id > 0 ) {
                $this->loadQrCode( $id );
            }
        }
    }

    public function onSubmit() {

        if ( in_array($this->_action, [ 'save', 'generate_cypher', 'cancel_cypher', 'save_cypher' ]) ) {
            $save = true;

            $this->description->value = trim( $this->description->value );
            if ( $this->description->value == '' ) {
                $save = false;
                self::msgErr( 'Description is empty.' );
            }

            $this->code->value = trim( $this->code->value );
            if ( $this->code->value != '' ) {
                $this->_plugin->config->load();
                if ( !$this->_plugin->config->sharedCodeEnabled->value ) {
                    $code = $this->code->value;
                    $id = intval($this->id->value);
                    $items = $this->_database->loadQrCodeByKey( $code );
                    if ( is_array($items) ) {
                        for ($n=0; $n<count($items); $n++) {
                            if ($items[$n]->code == $code && $items[$n]->qrcode_id != $id) {
                                $save = false;
                                self::msgErr( "Code is already in use by another QR-Code, whereas it must be unique: you can disable this restriction <a href=\"{$this->_plugin->pageConfig->url}\">here</a>." );
                                break;
                            }
                        }
                    } else {
                        $save = false;
                        self::msgErr( 'A problem occurred while reading the data.' );
                        sosidee_log("QrCodeEdit.onSubmit(): database.loadQrCodeByCode($code) :: WpTable.select() did not return an array." );
                    }
                }
            } else {
                $save = false;
                self::msgErr( 'Code is empty.' );
            }

            $this->url_redirect->value = trim( $this->url_redirect->value );
            if ( $this->url_redirect->value == '' ) {
                $save = false;
                self::msgErr( 'Redirect URL is empty.' );
            }

            $max_scan_tot = intval( $this->max_scan_tot->value );
            if ( $max_scan_tot < 0 ) {
                $this->max_scan_tot->value = 0;
                self::msgWarn( "Field 'Max total scans' has been reset to 0 (can't be negative)." );
            } else if ( $max_scan_tot > 2147483647 ) {
                $this->max_scan_tot->value = 2147483647;
                self::msgWarn( "Field 'Max total scans' has been reset to 2147483647 (maximum value)." );
            } else {
                $this->max_scan_tot->value = $max_scan_tot;
            }

            if ($this->img_forecolor->value == $this->img_backcolor->value) {
                $save = false;
                self::msgErr( 'Foreground and background colors cannot be equal.' );
            }

            if ( $save ) {

                if ( $this->_action == 'generate_cypher' ) {
                    $this->cypher->value = SRC\QrCode::getNewCypher();
                    $this->showCypher = true;
                } else if ( $this->_action == 'cancel_cypher' ) {
                    $this->cypher->value = '';
                    $this->showCypher = true;
                } else if ( $this->_action == 'save_cypher' ) {
                    $this->showCypher = true;
                }

                if ( $this->_plugin->config->mfaEnabled->value == false ) {
                    $this->only_mfa->value = false;
                }

                $data = [
                     'disabled' => boolval( $this->disabled->value )
                    ,'description' => trim( $this->description->value )
                    ,'code' => trim( $this->code->value )
                    ,'url_redirect' => trim( $this->url_redirect->value )
                    ,'url_inactive' => trim( $this->url_inactive->value )
                    ,'url_expired' => trim( $this->url_expired->value )
                    ,'date_from' => $this->date_start->getValueAsDate()
                    ,'date_to' => $this->date_end->getValueAsDate( true )
                    ,'time_from' => $this->time_start->getValueAsTime()
                    ,'time_to' => $this->time_end->getValueAsTime()
                    ,'priority' => boolval( $this->priority->value )
                    ,'max_scan_tot' => $this->max_scan_tot->value
                    ,'url_finished' => trim( $this->url_finished->value )
                    ,'dotw' => intval( $this->dotw->value )
                    ,'cypher' => trim( $this->cypher->value )
                    ,'url_cypher' => trim( $this->url_cypher->value )
                    ,'only_mfa' => boolval( $this->only_mfa->value )
                    ,'device_os' => intval( $this->device_os->value )
                    ,'device_lang' => trim( $this->device_lang->value )
                    ,'img_forecolor' => trim( $this->img_forecolor->value )
                    ,'img_backcolor' => trim( $this->img_backcolor->value )
                ];

                $result = $this->_database->saveQrCode( $data, $this->id->value );

                if ( $result !== false ) {
                    if ( $result === true ) {
                        self::msgOk( 'Data have been saved.' );
                        $this->loadQrCode( $this->id->value );
                    } else {
                        $id = intval($result);
                        if ( $id > 0 ) {
                            self::msgOk( 'Data have been added.' );
                            $this->loadQrCode( $id );
                        } else {
                            self::msgErr( 'A problem occurred while adding the data.' );
                        }
                    }
                } else {
                    self::msgErr( 'A problem occurred while saving the data.' );
                }

            }

        } else if ( $this->_action == 'delete' ) {

            $id = intval( $this->id->value );

            if ( $id > 0 ) {
                $result = $this->_database->deleteQrCode( $id );
                if ( $result !== false ) {
                    $this->reset();
                    self::msgOk( 'Data have been deleted.' );
                } else {
                    self::msgErr( 'A problem occurred while deleting the data.' );
                }
            } else {
                self::msgErr( 'You cannot delete data before having saved them.' );
            }

        }
    }

    private function getRandomCode() {
        $ret = '';
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($n=0; $n<3; $n++) {
            $i = random_int(0, strlen($charset) - 1);
            $ret .= $charset[$i];
        }
        return $ret . time();
    }

}