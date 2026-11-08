<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;

class QrCodeEdit extends Base
{
    use SOS_WP\TAsset;//, SOS_WP\TProperty;

    const QS_ID = 'dqc-id';

    public $id;

    private $disabled;
    private $description;
    public $url_redirect;
    public $url_inactive;
    public $url_expired;
    private $date_start;
    private $date_end;
    private $time_start;
    private $time_end;
    private $dotw;
    private $priority;
    public $max_scan_tot;
    public $url_finished;
    private $only_mfa;
    private $device_os;
    private $device_lang;
    private $img_forecolor;
    private $img_backcolor;

    public $quality_level;
    public $img_size;
    public $img_pad;

    private $code_old;
    public $code;

    public $cypher;
    public $url_cypher;

    public $redir_key_enabled;
    public $redir_key_name;

    public $showCypher;

    public $countStates;

    public function __construct( $name = '', $callback = null ) {
        if ( empty( $name ) ) {
            $name = 'qrcodeEdit';
        }
        if ( is_null( $callback ) ) {
            $callback = [$this, 'onSubmit'];
        }
        parent::__construct( $name, $callback );

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
        $description = self::getDescription('mandatory field', true);
        $this->description->description = $description;
        $this->code->description = $description;
        $this->url_redirect->description = $description;
        $this->cypher = $this->addHidden('cypher', '');
        $this->url_cypher = $this->addComboBox('url_cypher', '');

        $this->only_mfa = $this->addCheckBox('only_mfa', false);

        $this->device_os = $this->addSelect('device_os', SRC\OS::UNKNOWN);
        $this->device_lang = $this->addSelect('device_lang', '');

        $this->img_forecolor = $this->addColorPicker('img_forecolor', self::plugin()->config->imgForeColor->getValue() );
        $this->img_backcolor = $this->addColorPicker('img_backcolor', self::plugin()->config->imgBackColor->getValue() );

        $this->code_old = $this->addHidden('code_old', '');

        $this->showCypher = false;

        $this->countStates = null;

        add_action('plugins_loaded', function() {
            self::plugin()->doAction('_form_qrcode-edit_constructor', $this, self::plugin()->config );
        }, 12); // deve avvenire dopo che il plugin pro ha aggiunto filtri e azioni [TAddon::mainPluginChecked() ha priorità 11]

        add_action('plugins_loaded', [$this, 'finalizeSetup'], 13); // deve avvenire dopo quella sopra

    }

    public function finalizeSetup() {
        $plugin = self::plugin();
        if ( $plugin->isPro() ) {
            return;
        }

        $this->quality_level = $this->addLabel(  'qualityLevel', SRC\QrCodeLevel::UNKNOWN);
        $this->quality_level->description = '<p><i>higher settings improve scanning reliability, while lower values can produce simpler QR codes for small printing, engraving, or cutting</i> ' . self::plugin()->pro('color: #990099;', 'editable in the PRO version') . '</p>';

        $this->img_size = $this->addLabel(  'imgSize', $plugin->config->imgSize->getValue() );

        $this->img_pad = $this->addLabel(  'imgPad', $plugin->config->imgPad->getValue() );
        //$this->img_pad->description = '<p><i>editable in PRO</i> ' . self::plugin()->pro() . '</p>';

        //$this->redir_key_enabled = $this->addLabel(  'redirKeyEnabled', '');
        $this->redir_key_enabled = $this->addHidden(  'redirKeyEnabled', '');
        $this->redir_key_enabled->description = '<p>' . self::plugin()->pro() . '</p>';

        $this->redir_key_name = $this->addLabel('redirKeyName', '');

        $this->countStates = $this->addHidden('arr_states', []);

    }



    public function reset() {
        $config = self::plugin()->config;

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
        $this->device_os->value = SRC\OS::UNKNOWN;
        $this->device_lang->value = '';
        $this->img_forecolor->value = $config->imgForeColor->getValue();
        $this->img_backcolor->value = $config->imgBackColor->getValue();

        $this->quality_level->value = SRC\QrCodeLevel::UNKNOWN;
        $this->img_size->value = $config->imgSize->getValue();
        $this->img_pad->value = $config->imgPad->getValue();

        $this->showCypher = false;

        $this->countStates->value = [];

        self::plugin()->doAction('_form_qrcode-edit_reset', $this, $config );

    }

    protected function preSanitize() {
        $this->url_redirect->isUrl = true;
        $this->url_inactive->isUrl = true;
        $this->url_expired->isUrl = true;
        $this->url_finished->isUrl = true;
        $this->url_cypher->isUrl = true;
        self::plugin()->doAction('_form_qrcode-pre-sanitize', $this );
    }

    public function getId() {
        return intval($this->id->value);
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
    public function htmlCodeOld() {
        $this->code_old->value = $this->code->value;
        $this->code_old->html();
    }
    public function htmlDescription() {
        $this->description->html( ['maxlength' => 255] );
    }
    public function htmlUrlRedirect() {
        $options = self::getUrlList();
        $this->url_redirect->html( [ 'options' => $options ] );
    }
    public function htmlUrlInactive() {
        $options = self::getUrlList();
        $this->url_inactive->html( [ 'options' => $options ] );
    }
    public function htmlUrlExpired() {
        $options = self::getUrlList();
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
        $options = SRC\LogStatus::getList();
        $handled = self::plugin()->applyFilter('_form_qrcode-edit_field_max-scan-tot', false, $this, $options);
        if ($handled === false) {
            $this->max_scan_tot->html([
                'min' => 0
                ,'max' => 2147483647
            ]);
            $this->countStates->html();
        }
    }
    public function htmlUrlFinished() {
        $options = $this->getUrlList();
        $this->url_finished->html( [ 'options' => $options ] );
    }

    /*
    public function htmlCurrentScanTot() {
        if ( $this->id->value > 0 ) {
            $current = self::database()->logs->countByCode( $this->code->value );
            if ( $current !== false ) {
                DATA\FormTag::html( 'span', [
                     'content' => $current
                    ,'style' => 'cursor:text;padding:2px 4px 2px 4px;'
                ]);
            }
        }
    }
    */
    public function getCurrentScanTot() {
        if ( $this->getId() > 0 ) {
            $single = self::database()->logs->countById( $this->id->value );
            $total = self::database()->logs->countByCode( $this->code->value );
            if ( $single !== false && $total !== false ) {
                return [$single, $total];
            } else {
                return false;
            }
        } else {
            return [0, 0];
        }
    }

    public function resetScanTot() {
        $id = $this->getId();
        if ( $id > 0 ) {
            return self::database()->logs->resetCountByQrCodeId( $id );
        } else {
            return true;
        }
    }


    public function htmlDeviceOS() {
        $options = SRC\OS::getList('- any -');
        $this->device_os->html( [ 'options' => $options ] );
    }
    public function htmlDeviceLang() {
        $options = self::getLanguages('- any -');
        //$options = Locale::getLanguages('- any -');
        $this->device_lang->html( [ 'options' => $options ] );
    }
    public function htmlImgForeColor() {
        $this->img_forecolor->html();
    }
    public function htmlImgBackColor() {
        $this->img_backcolor->html();
    }

    public function htmlQualityLevel() {
        $options = self::plugin()->applyFilter('_form_qrcode-edit_field_quality-level', false);
        if ( is_array($options) ) {
            $this->quality_level->html( ['options' => $options] );
        } else {
            $default = SRC\QrCodeLevel::getDescription(SRC\QrCodeLevel::HIGH);
            $this->quality_level->value = SRC\QrCodeLevel::getDescription(SRC\QrCodeLevel::HIGH, $default);
            $this->quality_level->html();
        }
    }

    public function htmlImgSize() {
        $config = self::plugin()->config;
        $result = self::plugin()->applyFilter('_form_qrcode-edit_field_image-size', false);
        if ( $result === true ) {
            $min = SRC\QrCode::getImageMinSize();
            $max = SRC\QrCode::getImageMaxSize();
            if ( intval( $this->img_size->value ) < $min ) {
                $this->img_size->value = $config->imgSize->getValue();
            }
            $this->img_size->html( ['min' => $min, 'max' => $max] );
            $label = " pixels ($min &ndash; $max)";
            echo '<span style="font-style: italic;">' . esc_html($label) . '<span>';
        } else {
            $value = $config->imgSize->getValue();
            $this->img_size->value = "{$value}x{$value} pixels " . self::plugin()->pro('color: #990099;', 'editable in the PRO version');
            $this->img_size->html();
        }
    }
    public function htmlImgPad() {
        $config = self::plugin()->config;
        $result = self::plugin()->applyFilter('_form_qrcode-edit_field_image-pad', false);
        if ( $result === true ) {
            $min = SRC\QrCode::getImageMinPad();
            $max = SRC\QrCode::getImageMaxPad();
            if ( intval( $this->img_pad->value ) < $min ) {
                $this->img_pad->value = $config->imgPad->getValue();
            }
            $this->img_pad->html( ['min' => $min, 'max' => $max] );
            $label = " pixels ($min &ndash; $max)";
            echo '<span style="font-style: italic;">' . esc_html($label) . '<span>';
        } else {
            $value = $config->imgPad->getValue();
            $this->img_pad->value = "{$value} pixels " . self::plugin()->pro('color: #990099;', 'editable in the PRO version');
            $this->img_pad->html();
        }
    }

    public function htmlRedirKeyEnabled() {
        $pars = self::plugin()->applyFilter('_form_qrcode-edit_field_redir-key_enabled', []);
        $this->redir_key_enabled->html( $pars );
    }
    public function htmlRedirKeyName() {
        if ( !is_null( $this->redir_key_name) ) {
            $this->redir_key_name->html();
        }
    }


    public function htmlCypher() {
        $this->cypher->html();
    }
    public function htmlUrlCypher() {
        if ( $this->cypher->value != '' ) {
            $options = $this->getUrlList();
            $this->url_cypher->html( [ 'options' => $options ] );
        } else {
            DATA\FormTag::html( 'label', [
                 'html' => ' &nbsp; - - - '
                ,'style' => 'padding:2px 4px 2px 4px;margin-left:1em;'
            ]);
        }
    }

    public function htmlQId() {
        $id = $this->getId();
        if ( $id > 0 ) {
            DATA\FormTag::html( 'span', [
                 'content' => "Q-{$id}"
                ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
            ]);
        }
    }

    public function htmlQRUrl( $cypher = false ) {
        $content =' - - - ';
        $id = $this->getId();
        if ( $id > 0) {
            $code = !$cypher ? $this->code->value : base64_encode($this->cypher->value);
            if ( !empty($code) ) {
                $content = self::plugin()->getApiUrl( $code, $cypher );
                if ( $cypher ) {
                    $content = substr($content, 0, 80) .  " ...";
                }
            }
            echo wp_kses( SRC\Copy2CB::getApiUrlIcon( $id, $code, $cypher ), sosidee_allowed_html() );
            echo '&nbsp; ';
        }
        if ( !sosidee_is_dev() ) {
            DATA\FormTag::html( 'label', [
                'content' => $content
                ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
            ]);
        } else {
            if ( SOS_WP\Http::isHostPrivateIp($content) ) {
                $content = sosidee_add_query_arg('XDEBUG_SESSION_START', 'PHPSTORM', $content);
                //$content .= '&XDEBUG_SESSION_START=PHPSTORM'; // per agganciare il debugger quando l'host diventa IP
            }
            DATA\FormTag::html( 'a', [
                 'href' => $content
                ,'content' => $content
                ,'target' => '_blank'
                ,'title' => 'click to open in a new window'
                ,'style' => 'background-color:white;color:inherit;text-decoration:none;padding:2px 4px 2px 4px;'
            ]);
        }
    }

    public function htmlQRShortcode1() {
        $id = $this->getId();
        if ( $id > 0 ) {
            $text = SRC\Shortcode::getTemplate1( $id );
            echo wp_kses( SRC\Copy2CB::getShortcodeIcon( $id, 1 ), sosidee_allowed_html() );
            echo '&nbsp; ';
            DATA\FormTag::html( 'label', [
                 'content' => $text
                ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
            ]);
        }
    }

    public function htmlQRShortcode2() {
        $id = $this->getId();
        if ( $id > 0 ) {
            $standard = $this->cypher->value == '';
            $text = SRC\Shortcode::getTemplate2( $id, $standard );
            echo wp_kses( SRC\Copy2CB::getShortcodeIcon( $id, 2, $standard ), sosidee_allowed_html() );
            echo '&nbsp; ';
            DATA\FormTag::html( 'label', [
                 'content' => $text
                ,'style' => 'cursor:text;background-color:white;padding:2px 4px 2px 4px;'
            ]);
        }
    }

    public function htmlFormHiddenField( $anyQrHideEnabled ) {
        $id = $this->getId();
        if ( $id > 0 ) {
            if ( $anyQrHideEnabled || $this->cypher->value != '' ) {
                echo wp_kses( SRC\Copy2CB::getHiddenFieldIcon( $id ), sosidee_allowed_html() );
                $text = SRC\FORM\Base::getHiddenFieldTemplate( $id );
            } else {
                echo wp_kses( SRC\Copy2CB::getHiddenFieldIcon( -1 ), sosidee_allowed_html() );
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
        $id = $this->getId();
        if ( $id > 0) {

           //$code_raw = !$cypher ? $this->code->value : $this->cypher->value;
            $code = !$cypher ? $this->code->value : base64_encode($this->cypher->value);

            if ( !empty($code) ) {
                $plugin = self::plugin();
                $url = $plugin->getApiUrl( $code, $cypher );

                if ( sosidee_is_dev() ) {
                    $url = sosidee_add_query_arg('XDEBUG_SESSION_START', 'PHPSTORM', $url);
                }

                $size = intval( $this->img_size->value );
                if ( $size < SRC\QRCode::getImageMinSize() ) {
                    $size = $plugin->config->imgSize->getValue();
                }

                $pad = intval( $this->img_pad->value );
                if ( $pad < SRC\QRCode::getImageMinPad() ) {
                    $pad = $plugin->config->imgPad->getValue();
                }

                $fore_color = SRC\QrCode::getColor( $this->img_forecolor->value );
                $back_color = SRC\QrCode::getColor( $this->img_backcolor->value );

                $level = intval( $this->quality_level->value );

                $prefix = !$cypher ? "q" : "c";
                $parameters = [
                    'api' => $url,
                    'id' => $id,
                    'size' => $size,
                    'pad' => $pad,
                    'foreColor' => $fore_color,
                    'backColor' => $back_color,
                    'level' => $level
                ];
                // $name_pars = filtro pro ???

                $name = SRC\QrCode::getUniqueName( $parameters, $prefix );

                unset($parameters[ 'api']);
                unset($parameters[ 'id']);
                $parameters[ 'text'] = $url;

                // $img_pars = filtro pro !? (sì, ma quali parametri?)

                $img_url = SRC\QrCode::saveImg( $name, $parameters ) ? esc_url( SRC\QrCode::getUrl( $name ) ) : '';
                if ( $img_url === '' ) {
                    $data = SRC\QrCode::getString( $parameters );
                    if ( $data === '' ) {
                        return false;
                    }
                    $img_url = esc_attr( 'data:image/png;base64,' . $data );
                }

                $download = $prefix . '_' . $this->code->value . '_' . base_convert($this->id->value, 10, 36) . '_' . $size . 'x' . $size . '.png';
                echo '<div style="display: flex; justify-content: center; flex-wrap: wrap; margin-top: 2em;">';
                    echo '<div style="flex-basis: 100%; text-align: center; font-style: italic;">click the image to download it</div>';
                    // The generated PNG data URI is escaped above and is allowed only in this preview.
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    printf( '<a href="%1$s" title="click to download" download="%2$s"><img src="%1$s" alt="click to download" style="margin: 2px;"></a>', $img_url, esc_attr($download) );
                    echo '<div style="flex-basis: 100%; text-align: center;">size: ' . esc_attr($size) , 'x' . esc_attr($size) . '</div>';
                echo '</div>';

                return true;
            }
        } else {
            echo '<div style="display: flex; justify-content: center; flex-wrap: wrap; margin-top: 2em; text-align: center;"><div style="display:inline-block;border: 1px gray solid; padding: 1em;">qr code image<br>will be displayed here</div></div>';
            return false;
        }
    }

    public function htmlCancelCypher() {
        if ( $this->getId() > 0 && $this->cypher->value != '' ) {
            $message = htmlentities( 'Are you sure to cancel the enhanced QR code?' );
            $onclick = "return self.confirm('$message');";
            $value = 'cancel';
            $this->htmlButton( 'cancel_cypher', $value, DATA\FormButton::STYLE_DANGER, null, $onclick );
        }
    }

    public function htmlGenerateCypher() {
        if ( $this->getId() > 0 ) {
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

    public function htmlEliminate() {
        if ( $this->getId() > 0 ) {
            $this->htmlDelete( 'delete', 'Are you sure to delete it?' );
        }
    }

    public function htmlDuplicate() {
        if ( $this->getId() > 0 ) {

            /*
            $button = DATA\FormTag::get( 'input', [
                'type' => 'button'
                ,'value' => 'save as new'
                ,'style' => 'color: #ffffff; background-color: #bf00ff; border-color: #a200d9; min-width: 90px; cursor: pointer;'
                ,'class' => 'button'
                ,'onclick' => "alert('foobar');"
            ]);
            */

            $button = self::plugin()->applyFilter('_form_qrcode-edit_btn_duplicate', false, $this );

            if ( $button === false ) {
                $button = DATA\FormTag::get( 'input', [
                    'type' => 'button'
                    ,'value' => 'save as new'
                    //,'style' => 'color: #ffffff; background-color: #bf00ff; border-color: #a200d9; min-width: 90px; cursor: pointer;'
                    ,'style' => 'min-width: 90px; cursor: pointer;'
                    ,'class' => 'button'
                    ,'onclick' => "alert('available in the PRO version');this.blur();"
                ]);
                //$button .= ' ' . self::plugin()->pro('color: #bf00ff;', 'PRO version required');
                $button .= ' ' . self::plugin()->pro();
            }

            echo wp_kses( $button, sosidee_allowed_html() );

        }
    }

    public function htmlExport() {
        if ( $this->getId() > 0 ) {
            $button = self::plugin()->applyFilter('_form_qrcode-edit_btn_export', false, $this );

            if ( $button === false ) {
                $button = DATA\FormTag::get( 'input', [
                    'type' => 'button'
                    ,'value' => 'export'
                    ,'class' => 'button'
                    ,'style' => 'min-width: 90px; cursor: pointer;'
                    ,'onclick' => "alert('available in the PRO version');this.blur();"
                ]);
                $button .= ' ' . self::plugin()->pro();
            }

            echo wp_kses( $button, sosidee_allowed_html() );
        }
    }


    public function loadQrCode( $id ) {
        if ( $id > 0 ) {
            $qrcode= self::database()->qrcodes->load( $id );
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
                $this->quality_level->value = $qrcode->quality_level;
                $this->img_size->value = $qrcode->img_size;
                $this->redir_key_enabled->value = $qrcode->redir_key_enabled;
                $this->redir_key_name->value = $qrcode->redir_key_name;
                $this->countStates->value = json_decode($qrcode->count_states, true) ?? [];

                return true;
            } else {
                self::msgErr( "A problem occurred while reading the database." );
            }
        } else {
            self::msgErr( "A problem occurred: record id is zero." );
        }
        return false;
    }

    public function htmlButtonLink( $id ) {
        $url = self::plugin()->pageQrCode->getUrl( [self::QS_ID => $id] );
        if ( $id == 0 ) {
            parent::htmlLinkButton( $url, 'create new' );
        } else {
            parent::htmlLinkButton( $url, 'edit', DATA\FormButton::STYLE_SUCCESS );
        }
    }


    protected function initialize() {
        if ( !$this->isPosted() ) {
            $id = sosidee_get_query_var(self::QS_ID, 0);
            if ( $id > 0 ) {
                $this->loadQrCode( $id );
            }
        }
    }

    public function onSubmit() {
        $id = $this->getId();

        if ( in_array($this->_action, [ 'save', 'generate_cypher', 'cancel_cypher', 'save_cypher', 'duplicate', 'export' ]) ) {
            $save = true;

            $plugin = self::plugin();
            $config = $plugin->config;

            $this->description->value = trim( $this->description->value );
            if ( empty($this->description->value) ) {
                $save = false;
                self::msgErr( 'Description is empty.' );
            }

            $this->code->value = trim( $this->code->value );
            if ( !empty($this->code->value) ) {
                $config->load();
                if ( !$config->sharedCodeEnabled->value ) {
                    $code = $this->code->value;
                    $items = self::database()->qrcodes->loadByKey( $code );
                    if ( is_array($items) ) {
                        for ($n=0; $n<count($items); $n++) {
                            if ( $items[$n]->code == $code && $items[$n]->qrcode_id != $id ) {
                                $save = false;
                                $url = $plugin->pageConfig->url;
                                self::msgErr( "Code is already in use by another QR-Code, whereas it must be unique: you can disable this restriction <a href=\"{$url}\">here</a>." );
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
                //$save = false;
                self::msgWarn( "Redirect URL is empty: the qr code won't work!" );
            }

            $max_scan_tot = intval( $this->max_scan_tot->value );
            if ( $max_scan_tot < 0 ) {
                $this->max_scan_tot->value = 0;
                self::msgWarn( "'Max total scans' field has been reset to 0 (it cannot be negative)." );
            } elseif ( $max_scan_tot > PHP_INT_MAX ) {
                $this->max_scan_tot->value = PHP_INT_MAX;
                $max = PHP_INT_MAX;
                self::msgWarn( "The 'Max total scans' field has been reset to {$max} (the maximum value)." );
            } else {
                $this->max_scan_tot->value = $max_scan_tot;
            }

            $count_states_value = $this->countStates->value;
            if ( !is_array( $count_states_value ) ) {
                $count_states_value = json_decode($this->countStates->value, true) ?? [];
            }
            if ( is_array($count_states_value) ) {
                $this->countStates->value = array_map('intval', $count_states_value);
            } else {
                $this->countStates->value = [];
            }

            if ( $this->img_forecolor->value == $this->img_backcolor->value ) {
                $save = false;
                self::msgErr( 'Foreground and background colors cannot be equal.' );
            }

            //nota: sarebbe meglio non salvare i dati, ma male non fa...
            if ( $this->_action === 'duplicate' ) {
                $flag_duplicate = $plugin->applyFilter('_form_qrcode-edit_submit_duplicate', 0, $this );
                $msg_duplicate = '';
                if ( $flag_duplicate === 0 ) {
                    $save = false;
                    $msg_duplicate = $this->getProMsg('Duplication is available only in the PRO version');
                } elseif ( $flag_duplicate === -1 ) {
                    $save = false;
                    $msg_duplicate = $this->getProMsg('Please check your license.');
                }
                if ( !empty( $msg_duplicate ) ) {
                    self::msgWarn($msg_duplicate);
                }
            }

            if ( $save ) {

                if ( $this->_action == 'generate_cypher' ) {
                    $this->cypher->value = SRC\QrCode::getNewCypher();
                    $this->showCypher = true;
                } elseif ( $this->_action == 'cancel_cypher' ) {
                    $this->cypher->value = '';
                    $this->showCypher = true;
                } elseif ( $this->_action == 'save_cypher' ) {
                    $this->showCypher = true;
                }

                if ( !isset($config->mfaEnabled) || $config->mfaEnabled->value == false ) {
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
                    ,'quality_level' => intval( $this->quality_level->value )
                    ,'img_size' => intval( $this->img_size->value )
                    ,'img_pad' => intval( $this->img_pad->value )
                    ,'count_states' => wp_json_encode($this->countStates->value)
                ];

                $data = self::plugin()->applyFilter('_form_qrcode-edit_submit_data', $data, $this );

                $result = self::database()->qrcodes->save( $data, $id );

                if ( $result !== false ) {
                    if ( $result === true ) {
                        if ( $this->_action === 'save' ) {
                            self::msgOk( 'Data have been saved.' );
                        }
                        if ( $this->code_old->value != '' && $this->code_old->value != $this->code->value ) {
                            self::msgWarn( "Image(s) changed because the qr code Key has been modified." );
                        }
                        $loaded = $this->loadQrCode( $id );
                        if ( $loaded && $this->_action === 'export' ) {
                            $result = self::plugin()->applyFilter('_form_qrcode-edit_submit_export', false, $this );
                            if ( is_array( $result ) ) {
                                $success = $result['success'] ?? false;
                                if ( $success ) {
                                    $exportUrl = $result['url'] ?? null;
                                    if ( !empty( $exportUrl ) ) {
                                        $link = SOS_WP\HtmlTag::get('a', [
                                            'href' => $exportUrl
                                            ,'download' => true
                                            ,'content' => 'here'
                                        ]);
                                        self::msgOk("Data has been exported: click $link to download the file");
                                    } else {
                                        self::msgErr("Export procedure failed.");
                                    }
                                }
                            } else {
                                self::msgInfo("Export procedure requires the PRO version.");
                            }
                        }

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

        } elseif ( $this->_action == 'delete' ) {

            if ( $id > 0 ) {
                $result = self::database()->qrcodes->delete($id);
                if ($result !== false) {
                    $this->reset();
                    self::msgOk('Data have been deleted.');
                } else {
                    self::msgErr('A problem occurred while deleting the data.');
                }
            } else {
                self::msgErr('You cannot delete data before having saved them.');
            }

        } elseif ( $this->_action == 'reset-count' ) {

            if ( $id > 0 ) {
                $result = self::plugin()->applyFilter('_form_qrcode-edit_submit_reset-count', false, $this);
                if ( $result === false ) {
                    self::msgInfo("This procedure requires the PRO version.");
                } else {
                    if ( $result == 1 ) {
                        self::msgOk("Counts have been reset to zero for this QR-Code.");
                    } elseif ( $result == -1 ) {
                        self::msgErr("Reset procedure failed.");
                    }
                }
            }

        } else {
            self::msgErr( "This action is not supported: '{$this->_action}'." );
        }
    }

    private function getRandomCode() {
        $ret = '';
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $length = strlen($charset);

        try {
            random_int(0, 1);
            $use_random_int = true;
        } catch ( \Exception $_ ) {
            $use_random_int = false;
        }

        for ( $n = 0; $n < 3; $n++ ) {
            if ( $use_random_int ) {
                $i = random_int(0, $length - 1);
            } else {
                $i = mt_rand(0, $length - 1);
            }
            $ret .= $charset[$i];
        }

        return $ret . time();
    }

}
