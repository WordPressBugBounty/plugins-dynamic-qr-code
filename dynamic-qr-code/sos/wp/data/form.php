<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

class Form
{
    use SOS_WP\TBase;
    use SOS_WP\TMessage;
    use SOS_WP\TTranslation;

    protected const LAST_ACTION_QS = 'la';
    public const FORM_NAME = 'sos_form_name';

    protected $_nonce_name;
    protected $_nonce_action;
    protected $_actions;

    protected $_pages;

    protected $_posted;
    protected $_isValid;

    protected $_name;
    protected $_callback;
    protected $_action;
    protected $_encType;

    protected $_fields;

    protected $_cache_timestamp;

    public function __construct( $name = '', $callback = null ) {
        $name = esc_attr( trim($name) );
        if ( empty($name) ) {
            $name = 'vag';
        }
        $this->_nonce_name = "sos_form_{$name}_nonce_name";
        $this->_nonce_action = "sos_form_{$name}_nonce_action";
        $this->_actions = array();
        $this->_name = $name;
        $this->_callback = $callback;
        $this->_action = '';
        $this->_encType = null;
        $this->_posted = false;
        $this->_isValid = false;

        $this->_fields = array();
        $this->_pages = array();

        $this->_cache_timestamp = null;

    }

    public function getName() {
        return $this->_name;
    }
    public function getKey() {
        return sosidee_check_id($this->_name);
    }

    private function addField( $type, $name, $value ) {
        //$id = "{$this->_name}_$name"; // no holy policy dictates id=name, but I'm lazy
        $field = new FormField( $type, $name, $value );
        //$this->{$name} = $field; not necessary because fields are declared in the derived form class as properties
        $this->_fields[] = $field;
        return $field;
    }
    private function insertField( $index, $type, $name, $value ) {
        $field = new FormField( $type, $name, $value );
        $index = max(0, $index);
        if ( $index < count($this->_fields) ) {
            array_splice($this->_fields, $index, 0, [$field]);
        } else {
            $this->_fields[] = $field;
        }
        return $field;
    }

    public function addTextBox( $name, $value = '' ) {
        return $this->addField( FormFieldType::TEXT, $name, $value );
    }
    public function insertTextBox( $index, $name, $value = '' ) {
        return $this->insertField( $index, FormFieldType::TEXT, $name, $value );
    }

    public function addNumericBox( $name, $value = 0 ) {
        return $this->addField( FormFieldType::NUMBER, $name, $value );
    }
    public function insertNumericBox( $index, $name, $value = 0 ) {
        return $this->insertField( $index, FormFieldType::NUMBER, $name, $value );
    }

    public function addColorPicker( $name, $value = null ) {
        return $this->addField( FormFieldType::COLOR, $name, $value );
    }
    public function insertColorPicker( $index, $name, $value = null ) {
        return $this->insertField( $index, FormFieldType::COLOR, $name, $value );
    }

    public function addDatePicker( $name, $value = null) {
        if ( $value == 'now' || $value == 'today' ) {
            $dt = sosidee_current_datetime();
            $value = $dt->format('Y-m-d');
        }
        return $this->addField( FormFieldType::DATE, $name, $value );
    }
    public function insertDatePicker( $index, $name, $value = null) {
        if ( $value == 'now' || $value == 'today' ) {
            $dt = sosidee_current_datetime();
            $value = $dt->format('Y-m-d');
        }
        return $this->insertField( $index, FormFieldType::DATE, $name, $value );
    }

    public function addTimePicker( $name, $value = null ) {
        return $this->addField(FormFieldType::TIME, $name, $value );
    }
    public function insertTimePicker( $index, $name, $value = null ) {
        return $this->insertField( $index,FormFieldType::TIME, $name, $value );
    }

    public function addHidden( $name, $value = '' ) {
        return $this->addField( FormFieldType::HIDDEN, $name, $value );
    }
    public function insertHidden( $index, $name, $value = '' ) {
        return $this->insertField( $index, FormFieldType::HIDDEN, $name, $value );
    }

    public function addTextArea( $name, $value = '' ) {
        return $this->addField( FormFieldType::TEXTAREA, $name, $value );
    }
    public function insertTextArea( $index, $name, $value = '' ) {
        return $this->insertField( $index, FormFieldType::TEXTAREA, $name, $value );
    }

    public function addCheckBox( $name, $value = false ) {
        return $this->addField( FormFieldType::CHECK, $name, $value );
    }
    public function insertCheckBox( $index, $name, $value = false ) {
        return $this->insertField( $index, FormFieldType::CHECK, $name, $value );
    }

    public function addRadio( $name, $value = false ) {
        return $this->addField( FormFieldType::RADIO, $name, $value );
    }
    public function insertRadio( $index, $name, $value = false ) {
        return $this->insertField( $index, FormFieldType::RADIO, $name, $value );
    }

    public function addSelect( $name, $value = 0 ) {
        return $this->addField( FormFieldType::SELECT, $name, $value );
    }
    public function insertSelect( $index, $name, $value = 0 ) {
        return $this->insertField( $index, FormFieldType::SELECT, $name, $value );
    }

    public function addComboBox( $name, $value = '' ) {
        self::plugin()->addFormJs(); // still necessary until datalist stops behaving oddly and can be used properly
        return $this->addField( FormFieldType::COMBOBOX, $name, $value );
    }
    public function insertComboBox( $index, $name, $value = '' ) {
        self::plugin()->addFormJs();
        return $this->insertField( $index, FormFieldType::COMBOBOX, $name, $value );
    }

    public function addFilePicker( $name ) {
        $this->_encType = 'multipart/form-data';
        return $this->addField( FormFieldType::FILE, $name, null );
    }
    public function insertFilePicker( $index, $name ) {
        $this->_encType = 'multipart/form-data';
        return $this->insertField( $index, FormFieldType::FILE, $name, null );
    }

    public function addCheckList( $name, $value = [] ) {
        return $this->addField( FormFieldType::CHECKLIST, $name, $value );
    }
    public function insertCheckList( $index, $name, $value = [] ) {
        return $this->insertField( $index, FormFieldType::CHECKLIST, $name, $value );
    }

    public function addLabel( $name, $value = '' ) {
        return $this->addField( FormFieldType::LABEL, $name, $value );
    }
    public function insertLabel( $index, $name, $value = '' ) {
        return $this->insertField( $index, FormFieldType::LABEL, $name, $value );
    }

    private function getButtonName($action ) {
        $ret = $this->_name;
        if ( $action != '' ) {
            if ( !in_array($action, $this->_actions) ) {
                $this->_actions[] = $action;
            }
            $ret .= "_action_{$action}";
        } else {
            $ret .= "_" . time();
        }
        return  $ret;
    }

    public function getButton( $action = '', $value = 'ok', $style = '', $class = '', $onclick = null ) {
        $name = $this->getButtonName( $action );
        return FormButton::getSubmit( $name, $value, $style, $class, $onclick );
    }

    public function htmlButton( $action = '', $value = 'ok', $style = '', $class = '', $onclick = null ) {
        echo wp_kses( $this->getButton( $action, $value, $style, $class, $onclick ), sosidee_allowed_html() );
    }

    public function getSave( $value = 'save' ) {
        return $this->getButton( 'save', $value, FormButton::STYLE_SUCCESS );
    }

    public function htmlSave( $value = 'save' ) {
        echo wp_kses(  $this->getSave( $value ), sosidee_allowed_html() );
    }

    public function getDelete( $value = 'delete', $message = 'Do you confirm to delete?' ) {
        $message = htmlentities( $message );
        $onclick = "this.blur();return self.confirm('$message');";
        return $this->getButton( 'delete', $value, FormButton::STYLE_DANGER, null, $onclick );
    }

    public function htmlDelete( $value = 'delete', $message = "Deleting?" ) {
        echo wp_kses( $this->getDelete($value, $message), sosidee_allowed_html() );
    }

    public function getLinkButton( $url, $value = 'ok', $style = '', $class = '' ) {
        $class = $class != '' ? $class : 'button button-primary';
        if ( !empty($url) ) {
            $onclick = "self.location.href='$url'";
        } else {
            $onclick = "javascript:void(0);";
        }
        return FormButton::getLink( $value, $style, $class, $onclick );
    }

    public function htmlLinkButton( $url, $value = 'ok', $style = '', $class = '' ) {
        echo wp_kses( $this->getLinkButton( $url, $value, $style, $class ), sosidee_allowed_html() );
    }

    public function getLinkButton2( $url, $value = 'ok', $style = '', $class = '' ) {
        $class = $class != '' ? $class : 'button button-secondary';
        return $this->getLinkButton( $url, $value, $style, $class );
    }

    public function htmlLinkButton2( $url, $value = 'ok', $style = '', $class = '' ) {
        echo wp_kses( $this->getLinkButton2( $url, $value, $style, $class ), sosidee_allowed_html() );
    }

    protected function isCached() {
        $ret = false;
        for ( $n=0; $n<count($this->_fields); $n++ ) {
            if ($this->_fields[$n]->cached) {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

    public function isPosted() {
        return $this->_posted;
    }
    public function isValid() {
        return !empty($this->_action) && $this->_isValid;
    }

    protected function initialize() { }

    private function checkPost() {
        if ( isset( $_POST[$this->_nonce_name] ) ) {
            $this->_posted = true;
            $sanitized_value = sanitize_text_field( wp_unslash( $_POST[$this->_nonce_name] ) );
            if ( wp_verify_nonce( $sanitized_value, $this->_nonce_action ) ) {
                $this->_isValid = true;
                if ( !$this->checkAction() ) {
                    sosidee_log("Form::checkPost() - the nonce is valid but the action is empty or invalid.");
                    if ( is_admin() && sosidee_is_dev() ) {
                        self::plugin()::msgErr("Form::checkPost() - empty or invalid action ('{$this->_action}').");
                    }
                }
            } else {
                sosidee_log("Form::checkPost() invalid nonce.");
            }
        }
    }

    private function checkAction() {
        $ret = false;
        $actList = "{$this->_name}_actions";
        if ( isset( $_POST[$actList]) ) {
            $actions = explode(',', sanitize_text_field( wp_unslash($_POST[$actList])) );
            foreach ( $actions as $action ) {
                $name = "{$this->_name}_action_{$action}";
                if ( isset( $_POST[$name]) ) {
                    $this->_action = $action;
                    $ret = true;
                    break;
                }
            }
        }
        return $ret;
    }

    private function checkPage() {
        $ret = false;
        foreach ( $this->_pages as $page ) {
            if ( $page instanceof \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\BE\Page ) {
                if ( $page->isCurrent() ) {
                    $ret = true;
                    break;
                }
            } else {
                if ( !is_string($page) ) {
                    $page = print_r($page, true);
                }
                sosidee_log("Form::checkPage() - uninstantiated page ('{$page}'). Maybe it was not added to menu.");
            }
        }
        return $ret;
    }

    protected function getCurrentPage() {
        if ( did_action('current_screen') ) {
            foreach ( $this->_pages as $page ) {
                if ( $page instanceof \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\BE\Page ) {
                    if ( $page->isCurrent() ) {
                        return $page;
                    }
                } else {
                    sosidee_log("Form::getPage() - wrong type for a page: " . gettype($page));
                }
            }
        } else {
            if ( isset( $_GET['page'] ) ) {
                $key = sanitize_text_field( wp_unslash($_GET['page']) );
                foreach ( $this->_pages as $page ) {
                    if ( $page instanceof \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\BE\Page ) {
                        if ( $page->key == $key ) {
                            return $page;
                        }
                    } else {
                        sosidee_log("Form::getPage() - wrong type for a page: " . gettype($page));
                    }
                }
            }
        }
        sosidee_log("Form::getPage() - no current page found.");
        return null;
    }

    protected function reload( $last_action = null ) {
        if ( isset($_POST['_wp_http_referer']) ) {
            $url = esc_url_raw( wp_unslash($_POST['_wp_http_referer']) );
            if ( !is_null($last_action) ) {
                $url = add_query_arg( [ self::LAST_ACTION_QS => $last_action ], $url );
            }
            wp_redirect( $url );
            exit;
        } else {
            if ( is_admin() && sosidee_is_dev() ) {
                self::msgErr("Data\Form::reload() - input field '_wp_http_referer' is empty.");
            }
            sosidee_log("Data\Form::reload() - input field '_wp_http_referer' is empty.");
            return false;
        }
    }

    protected function preSanitize() { }

    private function sanitize() {
        foreach ($this->_fields as $field) {
            $name = $field->name;
            $posted = isset($_POST[$name]);
            $value = $posted ? wp_unslash( $_POST[$name] ) : null;

            if ( $field->type == FormFieldType::FILE ) {
                if ( isset($_FILES[$name]) ) {
                    $file_data = $_FILES[$name];
                    if ( is_array($file_data) && !empty($file_data['tmp_name']) &&
                        is_uploaded_file($file_data['tmp_name']) && $file_data['error'] === UPLOAD_ERR_OK ) {
                        $field->data = new FormFile($file_data);
                    } elseif ( isset($file_data['error']) && $file_data['error'] !== UPLOAD_ERR_NO_FILE ) {
                        sosidee_log("Form::sanitize() - ". $file_data['error']);
                    } else {
                        //sosidee_log("Form::sanitize() - no file uploaded or no critic error.");
                    }
                }
            } elseif ($field->type == FormFieldType::CHECK) {
                $field->value = $posted;
            } elseif ( $posted ) {

                if ( !empty($field->isUrl) ) {
                    $field->value = esc_url_raw( is_string($value) ? trim($value) : $value );
                    continue;
                }
                if ( !empty($field->isEmail) ) {
                    $field->value = sanitize_email( is_string($value) ? trim($value) : $value );
                    continue;
                }

                switch ( $field->type ) {
                    case FormFieldType::TEXTAREA:
                        $field->value = sanitize_textarea_field( $value );
                        break;
                    case FormFieldType::CHECKLIST:
                        if ( isset($value) && is_array($value) ) {
                            $sanitized_values = array_map('sanitize_text_field', $value);
                            $field->value = $sanitized_values;
                        } else {
                            $field->value = [];
                        }
                        break;
                    case FormFieldType::SELECT:
                        $value = sanitize_text_field( trim($value) );
                        if ( filter_var($value, FILTER_VALIDATE_INT) !== false ) {
                            $field->value = (int)$value;
                        } else {
                            $field->value = $value;
                        }
                        break;
                    default:
                        $field->value = sanitize_text_field( $value );
                }
            }

        }
    }

    public function handleLoad() {
        $this->initialize();
        if ( $this->isCached() ) {
            $this->loadCache();
        }
        return true;
    }

    public function handleSubmission() {
        $this->checkPost();

        if ( ! $this->isValid() ) {
            self::plugin()::msgErr("The form has not been validated.");
            return false;
        }

        $this->preSanitize();
        $this->sanitize();

        if ( ! is_callable($this->_callback) ) {
            if ( is_admin() && sosidee_is_dev() ) {
                self::plugin()::msgErr("Form::handleSubmission() - _callback is not callable.");
            }
            return false;
        }

        return call_user_func( $this->_callback, $this->_fields );
    }

    public function getOpen() {
        return FormTag::get( 'form', [
                 'method' => 'post'
                ,'enctype' => $this->_encType
            ]
        );
    }

    public function htmlOpen() {
        echo wp_kses( $this->getOpen(), sosidee_allowed_html() );
    }

    public function getClose() {
        $ret = wp_nonce_field( $this->_nonce_action, $this->_nonce_name, true, false );
        if ( count($this->_actions) > 0 ) {
            $name = "{$this->_name}_actions";
            //$id = $name;
            $value = implode(',', $this->_actions);
            $ret .= FormTag::get( 'input', [
                     'type' => 'hidden'
                    //,'id' => $id
                    ,'name' => $name
                    ,'value' => $value
                ]
            );
        }
        $ret .= FormTag::get( 'input', [
             'type' => 'hidden'
            ,'name' => self::FORM_NAME
            ,'value' => $this->_name
        ]);
        return $ret. '</form>';
    }

    public function htmlClose() {
        echo wp_kses( $this->getClose(), sosidee_allowed_html() );
    }

    public function addToPage() {
        $pages = func_get_args();
        if ( func_num_args() == 1 && is_array($pages[0]) ) {
            $pages = $pages[0];
        }
        foreach ( $pages as $page ) {
            if ( !in_array($page, $this->_pages) ) {
                $this->_pages[] = $page;
                $page->addForm( $this );    // TODO: eliminare la funzione DATA\Form::addToPage()
                                            // SERVE FINO A QUANDO I PLUGIN NON SARANNO AGGIORNATI
                                            // poi si eliminerà l'intera funzione addToPage() perché è stata
                                            // aggiunta la funzione addForm() alle pagine (e si utilizzerà solo quella).
                                            // PRIMA dell'aggiornamento: form->addToPage( page );
                                            // DOPO l'aggiornamento: page->addForm( form );
            }
        }
        return $this;
    }

    private function getCacheKey() {
        $key = self::plugin()->key . "_{$this->_name}_cache";
        return strtolower( str_replace("-", "_", $key) );
    }

    public function saveCache() {
        $user = SOS_WP\User::get();
        if ( $user->id > 0 ) {
            $values = [];
            foreach ( $this->_fields as $field ) {
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
            $values = get_user_meta( $user->id, $key, true );
            if ( is_array($values) && count($values) > 0 ) {
                foreach ( $this->_fields as $field ) {
                    if ( $field->cached ) {
                        if ( key_exists( $field->name, $values ) ) {
                            $field->value = $values[$field->name];
                        }
                    }
                    unset($field);
                }
                if ( key_exists( '__ts', $values ) ) {
                    $ts = \DateTimeImmutable::createFromFormat('YmdHis', $values['__ts']);
                    if ( $ts instanceof \DateTimeInterface ) {
                        $this->_cache_timestamp = $ts;
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