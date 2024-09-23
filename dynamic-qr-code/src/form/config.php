<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use \SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;

class Config
{
    private $_plugin;

    private $native;

    public $urlError;
    public $urlInactive;
    public $urlExpired;
    public $urlFinished;

    public $mfaEnabled;
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

    public function __construct($section) {

        $this->_plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();

        $this->native = $section;
        $section->validate = array($this, 'validate');

        $this->urlError = $this->addUrlField('url-error', '<span style="color:darkred;">Redirect URL for disabled/invalid keys</span>');
        $this->urlInactive = $this->addUrlField('url-inactive', 'Redirect URL for inactive keys');
        $this->urlExpired = $this->addUrlField('url-expired', 'Redirect URL for expired keys');
        $this->urlFinished = $this->addUrlField('url-finished', 'Redirect URL for finished keys');

        $this->anyDeviceEnabled = $this->native->addField( 'anydevice-enabled', '<span style="color:darkblue;">Enable requests from any type of device</span>', false, DATA\FieldType::CHECK );

        $this->imgSize = $this->native->addField( 'image-size', 'QR image size', 256, DATA\FieldType::NUMBER );
        $this->imgPad = $this->native->addField( 'image-pad', 'QR image border', 0, DATA\FieldType::NUMBER );

        $this->imgForeColor = $this->native->addField( 'image-color-foreground', 'QR image foreground color', SRC\QrCode::IMAGE_FOREGROUND, DATA\FieldType::COLOR );
        $this->imgBackColor = $this->native->addField( 'image-color-background', 'QR image background color', SRC\QrCode::IMAGE_BACKGROUND, DATA\FieldType::COLOR );

        $this->sharedCodeEnabled = $this->native->addField( 'shared-code-enabled', 'Disable unique keys', false, DATA\FieldType::CHECK );

        $this->anyQrHideEnabled = $this->native->addField( 'anyqrhide-enabled', 'Enable content hiding with standard QR code images', false, DATA\FieldType::CHECK );

        $this->formCheckMode = $this->native->addField( 'form-check-mode', 'Form check mode', CheckMode::REFERER, DATA\FieldType::SELECT );

        $this->cypherLength = $this->native->addField( 'cypher_length', 'Enhanced image URL length', SRC\QrCode::CYPHER_LENGTH, DATA\FieldType::HIDDEN );

        $this->logDisabled = $this->native->addField( 'log-disabled', 'Disable logs in database', false, DATA\FieldType::CHECK );

        $this->randQsEnabled = $this->native->addField( 'random-qs-enabled', 'Enable random querystring', false, DATA\FieldType::CHECK );

        $this->mfaEnabled = $this->native->addField( 'mfa-enabled', 'Enable My FastAPP options', false, DATA\FieldType::CHECK );

    }

    private function addUrlField($key, $title, $value = '') {
        return $this->native->addField($key, $title, $value, DATA\FieldType::COMBOBOX);
    }

    private function initializeUrlField( $field ) {
        $field->options = Base::getOptions();
    }
    private function initialize() {
        $this->initializeUrlField( $this->urlError );
        $this->urlError->description = Base::getDescription('<span style="color:darkred;">mandatory field</span>');
        $this->initializeUrlField( $this->urlInactive );
        $this->initializeUrlField( $this->urlExpired );
        $this->initializeUrlField( $this->urlFinished );

        $this->anyDeviceEnabled->description = Base::getDescription('<span style="color:darkblue;">generally checked for debugging and testing: if unchecked, only mobile devices are accepted</span>');

        $imgMax = SRC\QrCode::getImageMaxSize();
        $this->imgSize->min = 1;
        $this->imgSize->max = $imgMax;
        $this->imgSize->description = Base::getDescription("max. {$imgMax}");

        $padMax = SRC\QrCode::IMAGE_PAD_MAX;
        $this->imgPad->min = 0;
        $this->imgPad->max = $padMax;
        $this->imgPad->description = Base::getDescription("max. {$padMax} (does not affect the image size)");

        $this->sharedCodeEnabled->description = Base::getDescription('if unchecked: QR-Codes must have unique keys<br>if checked: the same key can be shared among various QR-Codes');

        $this->anyQrHideEnabled->description = Base::getDescription('if unchecked: content hiding is enabled only with the enhanced versions of the QR code images');

        $this->mfaEnabled->label = ' ' . $this->_plugin->help('mfa', 'vertical-align: text-bottom;');

        $this->formCheckMode->options = CheckMode::getList();
        $this->formCheckMode->description = Base::getDescription('used in posts/pages with hidden content containing a form');

        $key = $this->cypherLength->getTagKey();
        $base = $this->_plugin->getApiUrlLength();
        $min = SRC\QrCode::CYPHER_LENGTH_MIN;
        $this->cypherLength->javascript = "jsDynInitializeCypher('{$key}',{$base},{$min});";
        $min64 = $this->_plugin->getApiUrlLength($min);
        $this->cypherLength->description = Base::getDescription("number of characters embedded in the QR code enhanced image (min. $min64)");

        $this->randQsEnabled->description = Base::getDescription('it should/could help to prevent redirecting to cached pages');

    }

    public function html() {
        $this->initialize();
        $this->native->html();
    }

    public function setPage($page) {
        $this->native->setPage($page);
    }

    public function getField($key) {
        return $this->native->getField($key);
    }

    public function check() {
        $this->load();
        return $this->urlError->value != ''; // && $this->urlInactive->value != '' && $this->urlExpired->value != '';
    }

    public function load() {
        $this->native->load();
    }

    /***
     * @param string $cluster_key key of the data cluster
     * @param array $inputs values sent by the user ( associative array [field key => input value] )
     * @return array $outputs values to be saved ( associative array [field key => output value] )
     */
    public function validate( $cluster_key, $inputs ) {
        $outputs = array();

        foreach ( $inputs as $field_key => $field_value ) {
            $field = $this->getField($field_key);
            if ( !is_null($field) ) {
                if ( $field->type == DATA\FieldType::COMBOBOX ) {
                    $value = trim( sanitize_text_field( $field_value ) );
                    if ( strlen($value) != 0 || $field->name != $this->urlError->name ) {
                        $outputs[$field_key] = $value;
                    } else {
                        $outputs[$field_key] = '';
                        $this->_plugin::msgErr( "{$field->title}: empty field." );
                    }
                } else if ( $field->type == DATA\FieldType::CHECK ) {
                    $outputs[$field_key] = !is_null( $field_value );
                } else if ( $field->type == DATA\FieldType::COLOR ) {
                    $outputs[$field_key] = trim( $field_value );
                } else if ( $field->type == DATA\FieldType::NUMBER ) {
                    $value = intval( $field_value );
                    if ( $field->name == $this->imgSize->name ) {
                        $old = $this->imgSize->getValue();
                        $min = 1;
                        $max = SRC\QrCode::getImageMaxSize();
                    } else if ( $field->name == $this->imgPad->name ) {
                        $old = $this->imgPad->getValue();
                        $min = 0;
                        $max = SRC\QrCode::IMAGE_PAD_MAX;
                    } else {
                        $old = 0;
                        $min = 0;
                        $max = 0;
                    }
                    if ( $value < $min) {
                        $outputs[$field_key] = $old;
                        $this->_plugin::msgErr( "{$field->title}: value is smaller than {$min}." );
                    } else if ($value > $max) {
                        $outputs[$field_key] = $old;
                        $this->_plugin::msgErr( "{$field->title}: value is greater than {$max}." );
                    } else {
                        $outputs[$field_key] = $value;
                    }
                } else if ( $field->type == DATA\FieldType::HIDDEN ) {
                    $value = intval( $field_value );
                    $min = SRC\QrCode::CYPHER_LENGTH_MIN;
                    if ( $value >= $min ) {
                        $outputs[$field_key] = $value;
                    } else {
                        $outputs[$field_key] = $this->cypherLength->getValue();
                        $min = $this->_plugin->getApiUrlLength($min);
                        $this->_plugin::msgErr( "{$field->title}: value cannot be smaller than {$min}." );
                    }
                }
            } else {
                $this->_plugin::msgErr( "Field '{$field_key}': not found!" );
            }
        }

        return $outputs;
    }

}