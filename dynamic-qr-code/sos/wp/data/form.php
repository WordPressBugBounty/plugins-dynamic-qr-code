<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Form
{
    use SOS_WP\Message;
    use SOS_WP\Translation;

    private $_nonce_name;
    private $_nonce_action;
    private $_actions;

    protected $_plugin;

    public $_name;
    public $_callback;
    public $_posted;
    public $_action;
    public $_encType;

    public $_fields;

    protected $_cache_timestamp;

    private $_pages;

    public function __construct($name, $callback = null) {
        $this->_nonce_name = "sos_form_{$name}_nonce_name";
        $this->_nonce_action = "sos_form_{$name}_nonce_action";
        $this->_actions = array();
        $this->_name = $name;
        $this->_callback = $callback;
        $this->_posted = false;
        $this->_action = '';
        $this->_encType = null;

        $this->_fields = array();
        $this->_pages = array();

        $this->_cache_timestamp = null;

        if ( is_admin() ) {
            // hook moved from 'init' to 'current_screen' in order to allow checking for screen object in initialize()
            add_action( 'current_screen', array($this,'sanitize') );
        } else {
            add_action( 'the_post', array($this,'sanitize') );
        }

        $this->_plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();

    }

    private function addField( $type, $name, $value ) {
        $id = "{$this->_name}_$name";
        $ret = new FormField( $type, $id, $value );
        $ret->name = $name;
        //$this->{$name} = $ret; not necessary (why?)
        $this->_fields[] = $ret;
        return $ret;
    }

    public function addTextBox( $name, $value = '' ) {
        return $this->addField( FormFieldType::TEXT, $name, $value );
    }
    public function addNumericBox( $name, $value = 0 ) {
        return $this->addField( FormFieldType::NUMBER, $name, $value );
    }
    public function addColorPicker( $name, $value = null ) {
        return $this->addField( FormFieldType::COLOR, $name, $value );
    }
    public function addDatePicker( $name, $value = null) {
        if ($value == 'now') {
            $dt = sosidee_current_datetime();
            $value = $dt->format('Y-m-d');
        }
        return $this->addField( FormFieldType::DATE, $name, $value );
    }
    public function addTimePicker( $name, $value = null ) {
        return $this->addField(FormFieldType::TIME, $name, $value );
    }
    public function addHidden( $name, $value = '' ) {
        return $this->addField( FormFieldType::HIDDEN, $name, $value );
    }
    public function addTextArea( $name, $value = '' ) {
        return $this->addField( FormFieldType::TEXTAREA, $name, $value );
    }
    public function addCheckBox( $name, $value = false ) {
        return $this->addField( FormFieldType::CHECK, $name, $value );
    }
    public function addSelect( $name, $value = 0 ) {
        return $this->addField( FormFieldType::SELECT, $name, $value );
    }
    public function addComboBox( $name, $value = '' ) {
        return $this->addField( FormFieldType::COMBOBOX, $name, $value );
    }
    public function addFilePicker( $name ) {
        $this->_encType = 'multipart/form-data';
        return $this->addField( FormFieldType::FILE, $name, null );
    }

    private function getActionName( $action ) {
        $ret = "{$this->_name}_action";
        if ($action != '') {
            if ( !in_array($action, $this->_actions) ) {
                $this->_actions[] = $action;
            }
            $ret .= "_{$action}";
        }
        return  $ret;
    }

    public function getButton( $action = '', $value = 'ok', $style = '', $class = '', $onclick = null ) {
        $name = $this->getActionName( $action );
        return FormButton::getSubmit( $name, $value, $style, $class, $onclick );
    }

    public function htmlButton( $action = '', $value = 'ok', $style = '', $class = '', $onclick = null ) {
        echo sosidee_kses( $this->getButton( $action, $value, $style, $class, $onclick ) );
    }

    public function getSave( $value = 'save' ) {
        return $this->getButton( 'save', $value, FormButton::STYLE_SUCCESS );
    }

    public function htmlSave( $value = 'save' ) {
        echo sosidee_kses(  $this->getSave( $value ) );
    }

    public function getDelete( $value = 'delete', $message = 'Do you confirm to delete?' ) {
        $message = htmlentities( $message );
        $onclick = "return self.confirm('$message');";
        return $this->getButton( 'delete', $value, FormButton::STYLE_DANGER, '', $onclick );
    }

    public function htmlDelete( $value = 'delete', $message = "Deleting?" ) {
        echo sosidee_kses( $this->getDelete($value, $message) );
    }

    public function getLinkButton( $url, $value = 'ok', $style = '', $class = '' ) {
        $class = $class != '' ? $class : 'button button-primary';
        $onclick = "self.location.href='$url'";
        return FormButton::getLink( $value, $style, $class, $onclick );
    }

    public function htmlLinkButton( $url, $value = 'ok', $style = '', $class = '' ) {
        echo sosidee_kses( $this->getLinkButton( $url, $value, $style, $class ) );
    }

    public function getLinkButton2( $url, $value = 'ok', $style = '', $class = '' ) {
        $class = $class != '' ? $class : 'button button-secondary';
        return $this->getLinkButton( $url, $value, $style, $class );
    }

    public function htmlLinkButton2( $url, $value = 'ok', $style = '', $class = '' ) {
        echo $this->getLinkButton2( $url, $value, $style, $class );
    }

    protected function isCached() {
        $ret = false;
        for ($n=0; $n<count($this->_fields); $n++) {
            if ($this->_fields[$n]->cached) {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

    protected function initialize() { }

    private function chekPost() {
        $this->_posted = false;
        if ( isset( $_POST[$this->_nonce_name] ) ) {
            if ( wp_verify_nonce( $_POST[$this->_nonce_name], $this->_nonce_action ) ) {
                $this->_posted = true;
            }
        }
    }

    public function sanitize() {
        $this->chekPost();

        $continue = !is_admin();
        if ( !$continue ) {
            for ( $n=0; $n<count($this->_pages); $n++ ) {
                if ( $this->_pages[$n]->isCurrent() ) {
                    $continue = true;
                    break;
                }
            }
        }
        if ( !$continue ) {
            return false;
        }

        if ( $this->_posted == true ) {

            $this->initialize();

            $actList = "{$this->_name}_actions";
            if ( isset( $_POST[$actList]) ) {
                $actions = explode(',', sanitize_text_field( $_POST[$actList]) );
                for ( $n=0; $n<count($actions); $n++ ) {
                    $action = $actions[$n];
                    $name = "{$this->_name}_action_{$action}";
                    if ( isset( $_POST[$name]) ) {
                        $this->_action = $action;
                        break;
                    }
                }
            }
            for ( $n=0; $n<count($this->_fields); $n++ ) {
                $field = $this->_fields[$n];
                if ( isset($_POST[$field->name]) || $field->type == FormFieldType::CHECK ) {
                    switch ( $field->type ) {
                        case FormFieldType::TEXTAREA:
                            $field->value = sanitize_textarea_field( wp_unslash( $_POST[$field->name] ) );
                            break;
                        case FormFieldType::CHECK:
                            $field->value = isset( $_POST[$field->name] );
                            break;
                        default:
                            $field->value = sanitize_text_field( wp_unslash( $_POST[$field->name] ) );
                    }
                }
                if ( $field->type == FormFieldType::FILE && isset($_FILES[$field->name]) ) {
                    $field->data = new FormFile( $_FILES[$field->name] );
                }
            }
            if ( !is_null($this->_callback) ) {
                return call_user_func( $this->_callback, $this->_fields );
            } else {
                return true;
            }
        } else {
            if ( $this->isCached() ) {
                $this->loadCache();
            }
            $this->initialize();
        }
        return false;
    }

    public function getOpen() {
        return FormTag::get( 'form', [
                 'method' => 'post'
                ,'enctype' => $this->_encType
            ]
        );
    }

    public function htmlOpen() {
        echo sosidee_kses( $this->getOpen() );
    }

    public function getClose() {
        $ret = wp_nonce_field( $this->_nonce_action, $this->_nonce_name, true, false );
        if ( count($this->_actions) > 0 ) {
            $name = "{$this->_name}_actions";
            $id = $name;
            $value = implode(',', $this->_actions);
            $ret .= FormTag::get( 'input', [
                    'type' => 'hidden'
                    ,'id' => $id
                    ,'name' => $name
                    ,'value' => $value
                ]
            );
        }
        return $ret. '</form>';
    }

    public function htmlClose() {
        echo sosidee_kses( $this->getClose() );
;    }

    public function addToPage() {
        $pages = func_get_args();
        if ( func_num_args() == 1 && is_array($pages[0]) ) {
            $pages = $pages[0];
        }
        for ( $n = 0; $n < count($pages); $n++ ) {
            $this->_pages[] = $pages[$n];
        }
        return $this;
    }

    private function getCacheKey() {
        return strtolower( str_replace("-", "_", "{$this->_plugin->key}_{$this->_name}_cache") );
    }

    public function saveCache() {
        $user = SOS_WP\User::get();
        if ( $user->id > 0 ) {
            $values = [];
            for ( $n=0; $n<count($this->_fields); $n++ ) {
                $field = $this->_fields[$n];
                if ( $field->cached ) {
                    $values[$field->name] = $field->value;
                }
            }
            $key = $this->getCacheKey();
            if ( count($values) > 0 ) {
                $ts = sosidee_current_datetime();
                $values['__ts'] = $ts->format('YmdHis');
                update_user_meta( $user->id, $key, $values );
            } else {
                delete_user_meta( $user->id, $key );
            }
        }
    }

    private function loadCache() {
        $user = SOS_WP\User::get();
        if ( $user->id > 0 ) {
            $key = $this->getCacheKey();
            $values = get_user_meta( $user->id, $key );
            if ( is_array($values) && count($values) > 0 ) {
                $values = $values[0];
                if ( is_array($values) && count($values) > 0 ) {
                    for ( $n=0; $n<count($this->_fields); $n++ ) {
                        $field = &$this->_fields[$n];
                        if ( $field->cached ) {
                            if ( key_exists( $field->name, $values ) ) {
                                $field->value = $values[$field->name];
                            }
                        }
                        unset($field);
                    }
                    if ( key_exists( '__ts', $values ) ) {
                        $ts = \DateTime::createFromFormat('YmdHis', $values['__ts']);
                        if ($ts instanceof \DateTime) {
                            $this->_cache_timestamp = $ts;
                        }
                    }
                }
            }
        }
    }

    public function deleteCache() {
        $user = SOS_WP\User::get();
        if ( $user->id > 0 ) {
            $key = $this->getCacheKey();
            delete_user_meta( $user->id, $key );
        }
    }

}