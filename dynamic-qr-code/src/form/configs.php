<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA\FieldType;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TProperty;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TBase;
use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;

class Configs
{
    use TBase, DATA\TCluster;

    public $urlError;
    public $urlInactive;
    public $urlExpired;
    public $urlFinished;

    //public $mfaEnabled;
    public $anyDeviceEnabled;
    public $logDisabled;
    public $sharedCodeEnabled;
    public $anyQrHideEnabled;
    public $imgSize;
    public $imgPad;
    public $formCheckMode;

    public $imgForeColor;
    public $imgBackColor;

    public $cypherLength;

    public $randQsEnabled;

    public $scanValidationLevel;

    public $qualityLevel;
    public $geoEnabled;
    public $geoKey;

    public $middlePage;

    public function __construct( $section ) {

        $this->native = $section;
        $section->validate = [$this, 'validate'];

        $this->urlError = $this->addUrlField('url-error', '<span style="color:darkred;">Redirect URL for disabled/invalid keys</span>');
        $this->urlInactive = $this->addUrlField('url-inactive', 'Redirect URL for inactive keys');
        $this->urlExpired = $this->addUrlField('url-expired', 'Redirect URL for expired keys');
        $this->urlFinished = $this->addUrlField('url-finished', 'Redirect URL for finished keys');

        $this->anyDeviceEnabled = $this->native->addField( 'anydevice-enabled', '<span style="color:darkblue;">Enable requests from any type of device</span>', false, DATA\FieldType::CHECK );

        $this->imgSize = $this->native->addField( 'image-size', 'QR image size', SRC\QrCode::IMAGE_DEF_SIZE, FieldType::NUMBER );
        $this->imgPad = $this->native->addField( 'image-pad', 'QR image border', 0, FieldType::NUMBER );

        $this->imgForeColor = $this->native->addField( 'image-color-foreground', 'QR image foreground color', SRC\QrCode::IMAGE_FOREGROUND, FieldType::COLOR );
        $this->imgBackColor = $this->native->addField( 'image-color-background', 'QR image background color', SRC\QrCode::IMAGE_BACKGROUND, FieldType::COLOR );

        $this->sharedCodeEnabled = $this->native->addField( 'shared-code-enabled', 'Disable unique keys', false, FieldType::CHECK );

        $this->anyQrHideEnabled = $this->native->addField( 'anyqrhide-enabled', 'Enable content hiding with standard QR code images', false, FieldType::CHECK );

        $this->formCheckMode = $this->native->addField( 'form-check-mode', 'Form check mode', CheckMode::REFERER, FieldType::SELECT );

        $this->cypherLength = $this->native->addField( 'cypher_length', 'Enhanced image URL length', SRC\QrCode::CYPHER_LENGTH, FieldType::HIDDEN );

        $this->randQsEnabled = $this->native->addField( 'random-qs-enabled', 'Enable random querystring', false, FieldType::CHECK );

        $this->scanValidationLevel = $this->native->addField( 'scan-validation-level', 'Scan validation level', SOS_WP\RealBrowserThreshold::BALANCED, FieldType::SELECT );

        $this->logDisabled = $this->native->addField( 'log-disabled', 'Disable logs in database', false, FieldType::CHECK );

        //$this->mfaEnabled = $this->native->addField( 'mfa-enabled', 'Enable My FastAPP options', false, FieldType::CHECK );


        $data = [
            'qr-code-level' => SRC\QrCodeLevel::class,
        ];
        add_action('plugins_loaded', function() use ($data) {
            self::plugin()->doAction('_form_config_constructor', $this, $data);
            }, 12); // deve avvenire dopo che il plugin pro ha aggiunto filtri e azioni [TAddon::mainPluginChecked() ha priorità 11]

        add_action('plugins_loaded', [$this, 'finalizeSetup'], 13); // deve avvenire dopo quella sopra

    }

    public function finalizeSetup() {
        $plugin = self::plugin();
        if ( $plugin->isPro() ) {
            return;
        }

        $this->qualityLevel = $this->insertFieldLabel( 9, 'qualityLevel', 'Image quality' );
        $this->qualityLevel->value = SRC\QrCodeLevel::getDescription( SRC\QrCodeLevel::HIGH );
        $this->qualityLevel->description = self::getDescription('higher settings improve scanning reliability, while lower values can produce simpler QR codes for small printing, engraving, or cutting ' . self::plugin()->pro('color: #990099;', 'editable in the PRO version') );
        $this->geoEnabled = $this->insertFieldLabel( 13, 'geoEnabled', 'Enable Country detection' );
        $this->geoEnabled->value = '<i>the Pro version can retrieve geographic information</i> ' . self::plugin()->pro();
        $this->geoKey = $this->insertFieldLabel( 14, 'geoKey', 'IPinfo API token' );
        $this->geoKey->value = '<i>the Pro version supports <a href="https://ipinfo.io" target="_blank">IPinfo</a> geolocation services</i> ' . self::plugin()->pro();
        $this->middlePage = $this->insertFieldLabel( 18, 'middlePage', 'App preview handling' );
        $this->middlePage->value = '<i>the Pro version controls when a temporary page is shown to prevent previews from triggering scans automatically, based on the Scan validation level setting</i> ' . self::plugin()->pro();
    }


    private function addUrlField( $key, $title, $value = '' ) {
        return $this->addFieldCombo( $key, $title, $value );
    }

    private function initializeUrlField( $field ) {
        $field->options = Base::getUrlList();
    }


    public static function getDescription( $value ) {
        return Base::getDescription( $value );
    }


    private function initialize() {
        $this->initializeUrlField( $this->urlError );
        $this->urlError->description = self::getDescription('<span style="color:darkred;">mandatory field</span>');
        $this->initializeUrlField( $this->urlInactive );
        $this->initializeUrlField( $this->urlExpired );
        $this->initializeUrlField( $this->urlFinished );

        $this->anyDeviceEnabled->description = self::getDescription('<span style="color:darkblue;">generally checked for debugging and testing: if unchecked, only mobile devices are accepted</span>');

        $imgMin = SRC\QrCode::getImageMinSize();
        $imgMax = SRC\QrCode::getImageMaxSize();
        $this->imgSize->min = $imgMin;
        $this->imgSize->max = $imgMax;
        $this->imgSize->description = self::getDescription("range: {$imgMin} &ndash; {$imgMax} pixels");

        $padMin = SRC\QrCode::getImageMinPad();
        $padMax = SRC\QrCode::getImageMaxPad();
        $this->imgPad->min = $padMin;
        $this->imgPad->max = $padMax;
        $this->imgPad->description = self::getDescription("max. {$padMax} pixels (does not affect the image size)");

        $this->sharedCodeEnabled->description = self::getDescription('if enabled, multiple QR-Codes can share the same key');
        $this->sharedCodeEnabled->label = ' ' . self::plugin()->help('settings#disableUniqueKeys', 'vertical-align: text-bottom;');

        $this->anyQrHideEnabled->description = self::getDescription('if unchecked: content hiding is enabled only with the enhanced versions of the QR code images');

        //$this->mfaEnabled->label = ' ' . self::plugin()->help('myfast-app-integration', 'vertical-align: text-bottom;');

        $this->formCheckMode->options = CheckMode::getList();
        $this->formCheckMode->description = self::getDescription('used in posts/pages with hidden content containing a form');

        $min = SRC\QrCode::CYPHER_LENGTH_MIN;
        $min64 = self::plugin()->getApiUrlLength($min);
        $this->cypherLength->description = Base::getDescription("number of characters embedded in the QR code enhanced image (min. $min64)");
        $this->randQsEnabled->description = self::getDescription('it should/could help to prevent redirecting to cached version of pages');

        $this->scanValidationLevel->options = SOS_WP\RealBrowserThreshold::getList('Disabled (no blocking)');
        $this->scanValidationLevel->description = self::getDescription('controls how strictly scans are checked before being logged: more restrictive levels block more automatic app previews, but may ignore some real scans');

        $data = [
            'qr-code-level' => SRC\QrCodeLevel::class,
        ];
        self::plugin()->doAction('_form_config_initialize', $this, $data);


    }

    public function setPage($page) {
        $this->native->setPage($page);
    }

    public function check() {
        $this->load();
        return $this->urlError->value != ''; // && $this->urlInactive->value != '' && $this->urlExpired->value != '';
    }

    /***
     * @param string $cluster_key key of the data cluster
     * @param array $inputs values sent by the user ( associative array [field key => input value] )
     * @return array $outputs values to be saved ( associative array [field key => output value] )
     */
    public function validate( $cluster_key, $inputs ) {

        $outputs = [];
        $plugin = self::plugin();
        foreach ( $inputs as $field_key => $field_value ) {
            $field = $this->getField($field_key);
            if ( is_null($field) ) {
                $plugin::msgErr("Field '{$field_key}': not found!");
                continue;
            }

            $old_value = $field->getValue(); // the old value is the fallback

            if ( $field->name === $this->urlError->name ) {

                $value = trim( $field_value );
                if ( !empty( $value ) ) {
                    $outputs[$field_key] = $value;
                } else {
                    $outputs[$field_key] = $old_value;
                    $plugin::msgErr( "{$field->title} is empty." );
                }

            } elseif ( $field->name === $this->imgSize->name ) {

                $value = intval( $field_value );
                $min = SRC\QrCode::getImageMinSize();
                $max = SRC\QrCode::getImageMaxSize();
                if ( $min <= $value && $value <= $max ) {
                    $outputs[$field_key] = $value;
                } else {
                    $outputs[$field_key] = $old_value;
                    $plugin::msgErr( "{$field->title}: the value must be between {$min} and {$max}." );
                }

            }  elseif ( $field->name === $this->imgPad->name ) {

                $value = intval( $field_value );
                $min = 0;
                $max = SRC\QrCode::IMAGE_MAX_PAD;
                if ( $min <= $value && $value <= $max ) {
                    $outputs[$field_key] = $value;
                } else {
                    $outputs[$field_key] = $old_value;
                    $plugin::msgErr( "{$field->title}: the value must be between {$min} and {$max}." );
                }

            } elseif ( $field->name === $this->cypherLength->name ) {

                $value = intval( $field_value );
                $minByte = SRC\QrCode::CYPHER_LENGTH_MIN;
                if ( $value >= $minByte ) {
                    $outputs[$field_key] = $value;
                } else {
                    $outputs[$field_key] = $old_value;
                    $minBase64 = self::plugin()->getApiUrlLength($minByte);
                    $plugin::msgErr( "{$field->title} cannot be smaller than {$minBase64}." );
                }

            } else {

                $data = [ 'result' => false, 'name' => $field->name, 'value' => $field_value ];
                $data = $plugin->applyFilter('_form_config_validate_field', $data );

                if ( $data['result'] ) {
                    $outputs[$field_key] = $data['value'];
                } else {

                    if ( $field->type === FieldType::COMBOBOX ) {

                        $outputs[$field_key] = trim( $field_value );

                    } elseif ( $field->type === FieldType::CHECK ) {

                        $outputs[$field_key] = !is_null( $field_value );

                    } elseif ( $field->type === FieldType::COLOR ) {

                        $outputs[$field_key] = trim( $field_value );

                    } elseif ( $field->type === FieldType::NUMBER ) {

                        $outputs[$field_key] = intval( $field_value );

                    } else {

                        $outputs[$field_key] = $field_value;

                    }
                }
            }

        }

        return $outputs;
    }

}