<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

/**
 * @property $key
 * @property $options associative array [value => text] for select tag (if 'text' is an associative array, then 'value' becomes an option group)
 */
#[\AllowDynamicProperties]
class Field
{
    use SOS_WP\TTranslation;
    use TEncryption;

    private $writableValue;
    private $defaultValue;

    public $loaded;
    public $encrypted;

    public $key;
    public $name;
    public $value;
    public $type;
    protected $parent;

    public $options;
    public $validate;

    public $title;
    public $description;
    public $label;

    public $min;
    public $max;
    public $step;
    public $class;
    public $style;

    public $handled; //to avoid the double call that happens when the record does not exist and must be inserted

    public $pro;

    public function __construct( $key, $title, $value, $type ) {

        $this->parent = null;
        
        $this->key = sosidee_check_id($key);
        $this->name = $key;
        $this->value = $value;
        $this->defaultValue = $value;
        $this->type = $type;

        $this->title = $title;
        $this->description = null;
        $this->label = null;

        $this->min = null;
        $this->max = null;
        $this->step = null;

        $this->class = null;
        $this->style = null;

        $this->options = array();
        $this->validate = null;

        $this->handled = false;
        $this->writableValue = null;

        $this->encrypted = false;

        $this->loaded = false;

        $this->pro = false;

        if ( $this->type === FieldType::LABEL ) {
            $this->loaded = true;
            $this->handled = true;
        }
    }

    public static function create( $parent, $key, $title, $value, $type ) {
        $ret = new self( $key, $title, $value, $type );
        $ret->parent = $parent;
        return $ret;
    }

    private static function getText( $key, $text, $class = 'regular-text' ) {
        return FormTag::get( 'input', [
             'type' => 'text'
            ,'id' => $key
            ,'name' => $key
            ,'value' => $text
            ,'class' => $class
        ]);
    }

    private static function getSelect( $key, $current, $options, $style = null, $onchange = null, $without_name = false ) {

        $html = '';
        foreach ( $options as $value => $text ) {
            if ( !is_array($text) ) {
                $html .= FormTag::get('option', [
                     'value' => $value
                    ,'selected' => strcasecmp($current, $value) == 0
                    ,'content' => $text
                ]);
            } else {
                $group = '';
                foreach ( $text as $grp_value => $grp_text ) {
                    $group .= FormTag::get('option', [
                         'value' => $grp_value
                        ,'selected' => strcasecmp($current, $grp_value) == 0
                        ,'content' => $grp_text
                    ]);
                }
                $html .= FormTag::get('optgroup',[
                     'label' => $value
                    ,'html' => $group
                ]);
            }
        }

        $attributes = [
             'id' => $key
            ,'name' => $key
            ,'html' => $html
            ,'style' => $style
            ,'onchange' => $onchange
        ];
        if ( $without_name === true ) {
            unset( $attributes['name'] );
        }
        return FormTag::get( 'select', $attributes );
    }

    public function getTagKey() {
        if ( $this->parent instanceof Section ) {
            return $this->key;
        } else if ( $this->parent instanceof Group ) {
            return "{$this->parent->key}[{$this->key}]";
        } else {
            return '?';
        }
    }

    public function html() {
        $tag_key = $this->getTagKey();

        $current = $this->getValue();
        $html = '';

        if ( $this->type == FieldType::CHECK ) {

            $tag = FormTag::get( 'input', [
                     'type' => 'checkbox'
                    ,'id' => $tag_key
                    ,'name' => $tag_key
                    ,'value' => '1'
                    ,'checked' => strcasecmp( $current, '1' ) == 0
                ]
            );
            if ( !is_null($this->label) && !empty(trim($this->label)) ) {
                $tag .= FormTag::get( 'label', [
                     'for' => $tag_key
                    ,'html' => $this->label
                ]);
            }
            $html .= $tag;

        } elseif ( $this->type == FieldType::SELECT ) {

            $html .= self::getSelect( $tag_key, $current, $this->options );

        } elseif ( $this->type == FieldType::OPTIONLIST ) {

            $html_tags = [];
            foreach ( $this->options as $value => $text ) {
                $id = $tag_key . '_' . sosidee_check_id($value);
                $tag = FormTag::get( 'input', [
                     'type'     => 'radio'
                    ,'id'       => $id
                    ,'name'     => $tag_key
                    ,'value'    => $value
                    ,'checked'  => strcasecmp( $current, $value ) == 0
                ]);
                if ( !empty(trim($text)) ) {
                    $tag .= FormTag::get( 'label', [
                         'for'      => $id
                        ,'content'  => $text
                    ]);
                }
                $html_tags[] = $tag;
            }
            $html .= implode('<br>', $html_tags);

        } elseif ( $this->type == FieldType::TEXTAREA ) {

            $html .= FormTag::get( 'textarea', [
                 'id' => $tag_key
                ,'name' => $tag_key
                ,'content' => $current
                ,'class' => 'large-text'
            ]);

        } elseif ( $this->type == FieldType::NUMBER ) {

            $html .= FormTag::get( 'input', [
                 'type' => 'number'
                ,'id' => $tag_key
                ,'name' => $tag_key
                ,'value' => $current
                ,'class' => 'small-text'
                ,'min' => $this->min
                ,'max' => $this->max
                ,'step' => $this->step
                ,'style' => 'text-align:center;'
            ] );

        } elseif ( $this->type == FieldType::COLOR ) {

            $html .= FormTag::get( 'input', [
                 'type' => 'color'
                ,'id' => $tag_key
                ,'name' => $tag_key
                ,'value' => $current
                ,'style' => 'cursor:pointer;'
            ] );

        } elseif ( $this->type == FieldType::TEXT ) {

            $class = $this->class ?? 'regular-text';
            $html .= FormTag::get( 'input', [
                 'type' => 'text'
                ,'id' => $tag_key
                ,'name' => $tag_key
                ,'value' => $current
                ,'class' => $class
                ,'style' => $this->style
            ]);

        } elseif ( $this->type == FieldType::CHECKLIST ) {

            $html_tags = [];
            foreach ($this->options as $_value => $_text) {
                $id = $tag_key . '-' . sosidee_check_id($_value);
                $checked = in_array( (string)$_value, $current, true);
                $tag = FormTag::get('input', [
                     'type'    => 'checkbox'
                    ,'id'      => $id
                    ,'name'    => $tag_key . '[]'
                    ,'value'   => $_value
                    ,'checked' => $checked
                ]);
                if ( !empty( trim($_text) ) ) {
                    $tag .= FormTag::get('label', [
                         'for'     => $id
                        ,'content' => $_text
                    ]);
                }
                $html_tags[] = $tag;
            }
            $html .= implode('<br>', $html_tags);

        } elseif ( $this->type == FieldType::COMBOBOX ) {

            $html .= self::getText($tag_key, $current);

            $tag_key_cbo = "{$tag_key}_select";
            $style = 'margin-left: 1em;';
            $onchange = "jsSosCombobox(this.value, '{$tag_key}');";
            $html .= self::getSelect( $tag_key_cbo, $current, $this->options, $style, $onchange, true );

        } elseif ($this->type == FieldType::HIDDEN ) {

            $html .= FormTag::get( 'input', [
                 'id' => $tag_key
                ,'name' => $tag_key
                ,'type' => 'hidden'
                ,'value' => $current
            ]);
            $html .= FormTag::get( 'span', [
                'id' => "{$tag_key}_span"
            ]);

        } else {

            $html .= FormTag::get( 'label', [
                 'id' => $tag_key
                ,'name' => $tag_key
                ,'html' => $current
            ]);
        }

        if ( $this->description != '' ) {
            $html .= FormTag::get( 'p', [
                'html' => $this->description
            ]);
        }

        //echo wp_kses( $html, sosidee_allowed_html() );
        sosidee_echo_kses( $html );
    }

    public function load() {
        if ( $this->loaded ) {
            return;
        }
        if ( $this->parent instanceof Section ) {
            $sentinel = '___OPTION_NOT_FOUND___' . uniqid();
            $value = get_option($this->key, $sentinel);
            if ( $value !== $sentinel ) {
                $this->setValue( $this->getReadableValue($value) );
            } else {
                $this->setValue( $this->defaultValue );
            }
        } else if ( $this->parent instanceof Group ) {
            $this->parent->load();
        }
        $this->loaded = true;
    }

    public function getReadableValue( $value ) {
        if ( $this->encrypted ) {
            $value = $this->decrypt($value);
            if ( $value === false ) {
                sosidee_log("Data\Field::getReadableValue() - method decrypt() returned false for field.key={$this->key}.");
                return $this->defaultValue;
            }
        }
        if ( $this->type === FieldType::CHECKLIST ) {
            $value = @unserialize($value);
            if ( !is_array($value) ) {
                $value = array_filter( explode(';', (string)$value) );
            }
            if ( !is_array($value) ) {
                $value = $this->defaultValue;
            }
        }
        return $value;
    }
    public function getWritableValue( $value ) {
        if ( $this->type === FieldType::CHECKLIST ) {
            $value = serialize($value ?? [] );
        }
        if ( $this->encrypted ) {
            $value = $this->encrypt($value);
        }
        return $value;
    }

    public function getValue() {
        $this->load();
        return $this->value;
    }

    public function setValue( $value ) {
        $this->value = $value;
    }
    
    /**
     * $input is the field value
     */
    private function callback( $input ) {
        if ( $this->handled ) {
            return $this->writableValue;
        }
        $this->handled = true;

        $validatedValue = $input;
        if ( is_callable($this->validate) ) {
            $result = call_user_func($this->validate, $this->parent->key, [$this->key => $input]);
            $validatedValue = is_array($result) ? ($result[array_key_first($result)] ?? null) : $result;
        }
        $this->writableValue = $this->getWritableValue($validatedValue);
        return $this->writableValue;
    }
    
    public function translate() {
        $this->title = self::t_( $this->title );
        $this->description = self::t_( $this->description );
        $this->label = self::t_( $this->label );
        foreach ( $this->options as $value => $text ) {
            if ( !is_array($text) ) {
                $this->options[$value] = self::t_( $text );
            } else {
                foreach ( $text as $opt_value => $opt_text ) {
                    $this->options[$value][$opt_value] = self::t_( $opt_text );
                }
                $value_t = self::t_( $value );
                if ( $value != $value_t ) {
                    $this->options[$value_t] = $this->options[$value];
                    unset($this->options[$value]);
                }
            }
        }
    }

    public function initialize() {

        add_settings_field(
             $this->key
            ,$this->title
            ,array($this, 'html')
            ,$this->parent->page
            ,$this->parent->key
        );
    }

    public function sanitize($input) {
        $input = sosidee_sanitize_recursive($input);
        return $this->callback($input);
    }
    
    public function register() {
        if ( $this->type === FieldType::LABEL ) {
            return; // there will be no posted value
        }
        register_setting(
             $this->parent->key
            ,$this->key
            ,[ 'sanitize_callback' => [$this, 'sanitize'] ]
        );

    }

}