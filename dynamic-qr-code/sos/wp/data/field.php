<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * @property $key
 * @property $options associative array [value => text] for select tag (if 'text' is an associative array, then 'value' becomes an option group)
 */
class Field
{
    use SOS_WP\Property
    {
        SOS_WP\Property::__get as __getProp;
        SOS_WP\Property::__set as __setProp;
    }
    use SOS_WP\Translation;
    use Encryption;

    private $loaded;
    public $encrypted;

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

    public $javascript;

    public $handled; //to avoid the double call that happens when the record does not exist and must be inserted

    public function __construct( $key, $title, $value, $type ) {
        $this->_addProperty('key', '');

        $this->parent = null;
        
        $this->key = $key;
        $this->name = $key;
        $this->value = $value;
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
        $this->encrypted = false;

        $this->javascript = null;

        $this->loaded = false;
    }

    public function __set( $name, $value ) {
        switch ($name) {
            case 'key':
                $value = self::checkId( $value );
                break;
        }
        return $this->__setProp( $name, $value );
    }
    
    public static function create( $parent, $key, $title, $value, $type ) {
        $ret = new self( $key, $title, $value, $type );
        $ret->parent = $parent;
        return $ret;
    }

    private static function getText( $key, $text ) {
        return FormTag::get( 'input', [
             'type' => 'text'
            ,'id' => $key
            ,'name' => $key
            ,'value' => $text
            ,'class' => 'regular-text'
        ]);
    }

    private static function getCheck( $key, $checked, $value = '1', $onclick = null ) {
        return FormTag::get( 'input', [
                 'type' => 'checkbox'
                ,'id' => $key
                ,'name' => $key
                ,'value' => $value
                ,'checked' => $checked
                ,'onclick' => $onclick
            ]
        );
    }

    private static function getSelect( $key, $current, $options, $style = null, $onchange = null ) {
        $html = '';
        foreach ($options as $value => $text) {
            if ( !is_array($text) ) {
                $html .= FormTag::get('option', [
                     'value' => $value
                    ,'selected' => strcasecmp($current, $value) == 0
                    ,'content' => $text
                ]);
            } else {
                $group = '';
                foreach ($text as $grp_value => $grp_text) {
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

        return FormTag::get( 'select', [
             'id' => $key
            ,'name' => $key
            ,'html' => $html
            ,'style' => $style
            ,'onchange' => $onchange
        ]);
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

        $current = $this->value;
        $html = '';

        if ( $this->type == FieldType::CHECK ) {

            $checked = strcasecmp($current, '1') == 0;
            $html_tag = self::getCheck($tag_key, $checked);

            if ( $this->label != '' ) {
                $html_tag = FormTag::get( 'label', [
                     'for' => $tag_key
                    ,'html' => $html_tag . $this->label
                ]);
            }
            $html .= $html_tag;

        } else if ( $this->type == FieldType::SELECT ) {

            $html .= self::getSelect($tag_key, $current, $this->options);

        } else if ( $this->type == FieldType::OPTION ) {

            $html_tag = '';
            foreach ($this->options as $value => $text) {
                if ( $html_tag != '' ) {
                    $html_tag .= '<br>';
                }
                $html_opt = FormTag::get( 'input', [
                     'type' => 'radio'
                    ,'name' => $tag_key
                    ,'value' => $value
                    ,'checked' => strcasecmp($current, $value) == 0
                ]);

                $html_tag .= FormTag::get( 'label', [
                     'html' => $html_opt
                    ,'content' => $text
                ]);
            }

            $html .= $html_tag;

        } else if ($this->type == FieldType::TEXTAREA ) {

            $html .= FormTag::get( 'textarea', [
                 'id' => $tag_key
                ,'name' => $tag_key
                ,'content' => $current
                ,'class' => 'large-text'
            ]);

        } else if ( $this->type == FieldType::NUMBER ) {

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

        } else if ( $this->type == FieldType::COLOR ) {

            $html .= FormTag::get( 'input', [
                 'type' => 'color'
                ,'id' => $tag_key
                ,'name' => $tag_key
                ,'value' => $current
                ,'style' => 'cursor:pointer;'
            ] );

        } else if ( $this->type == FieldType::TEXT ) {

            $class = $this->class ?? 'regular-text';
            $html .= FormTag::get( 'input', [
                 'type' => 'text'
                ,'id' => $tag_key
                ,'name' => $tag_key
                ,'value' => $current
                ,'class' => $class
                ,'style' => $this->style
            ]);
            //$html .= self::getText($tag_key, $current);

        } else if ( $this->type == FieldType::CHECKLIST ) {

            $jsFunc = $this->getJsFuncName($tag_key);
            $count = 0;
            $currents = explode(';', $current);
            $html_tag = '';
            foreach ($this->options as $value => $text) {
                $tag_key_chk = $tag_key . $count;
                if ($html_tag != '') {
                    $html_tag .= '<br>';
                }

                $checked = in_array($value, $currents);
                $html_chk = self::getCheck($tag_key_chk, $checked, $value, "{$jsFunc}(this.value,this.checked);");

                $html_tag .= FormTag::get( 'label', [
                     'html' => $html_chk
                    ,'content' => $text
                ]);
                $count++;
            }

            $html_tag .= FormTag::get( 'input', [
                 'type' => 'hidden'
                ,'id' => $tag_key
                ,'name' => $tag_key
                ,'value' => $current
            ] );

            $html .= $html_tag;

            $js = <<<EOD
function {$jsFunc}( v, m ) {
    let field = self.document.getElementById( '$tag_key' );
    let values = field.value.split( ';' );
    if ( m && !values.includes(v) ) {
        values.push(v);
    } else if ( !m && values.includes(v) ) {
        values = values.filter( function(e, i, a) { return e != v; }, v );
    }
    field.value = values.join( ';' );
}
EOD;

            $html .= FormTag::get( 'script', [
                 'type' => 'application/javascript'
                ,'content' => $js
            ]);

        } else if ( $this->type == FieldType::COMBOBOX ) {

            $tag_key_sel = "{$tag_key}_cbo";

            $jsFunc = $this->getJsFuncName($tag_key);

            $html .= self::getText($tag_key, $current);

            $style = 'margin-left: 1em;';
            $onchange = "{$jsFunc}(this.value);";
            $html .= self::getSelect($tag_key_sel, $current, $this->options, $style, $onchange);

            $js = <<<EOD
function {$jsFunc}( v ) {
    let field = self.document.getElementById( '{$tag_key}' );
    field.value = v;
    if (v == '') {
        field.focus();
    }
}
EOD;

            $html .= FormTag::get( 'script', [
                 'type' => 'application/javascript'
                ,'content' => $js
            ]);

        } else if ($this->type == FieldType::HIDDEN ) {

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
                ,'content' => $current
            ]);
        }

        if ( $this->description != '' ) {
            $html .= FormTag::get( 'p', [
                'html' => $this->description
            ]);
        }

        if ( !is_null($this->javascript) ) {
            $html .= FormTag::get( 'script', [
                 'type' => 'application/javascript'
                ,'content' => $this->javascript
            ]);
        }

        echo sosidee_kses( $html );
    }

    private function getJsFuncName($tag_key) {
        return 'js_' . str_replace( ['-', '[', ']'], ['_'], $tag_key );
    }

    public function load() {
        if ( !$this->loaded ) {
            if ( $this->parent instanceof Section ) {
                $value = get_option($this->key, $this->value);
                $this->setValue( $value );
            } else if ( $this->parent instanceof Group ) {
                $this->parent->load();
            }
            $this->loaded = true;
        }
    }

    public function getValue() {
        $this->load();
        return $this->value;
    }

    public function setValue( $value ) {
        if ( !$this->encrypted ) {
            $this->value = $value;
        } else {
            $this->value = $this->decrypt( $value );
        }
    }
    
    /**
     * $input is the field value
     */
    public function callback( $input ) {
        if ( !is_null($this->validate) ) {
            if ( !$this->handled ) {
                $this->handled = true;
                $result = call_user_func( $this->validate, $this->parent->key, array($this->key => $input) );
                if ( is_array($result) ) {
                    $ret = array_values($result)[0];
                } else {
                    $ret = $result;
                }
                if ( !$this->encrypted ) {
                    return $ret;
                } else {
                    return $this->encrypt( $ret );
                }
            } else {
                return $input;
            }
        }
    }
    
    public function translate() {
        $this->title = self::t_( $this->title );
        $this->description = self::t_( $this->description );
        $this->label = self::t_( $this->label );
        foreach ($this->options as $value => $text) {
            if ( !is_array($text) ) {
                $this->options[$value] = self::t_( $text );
            } else {
                foreach ($text as $opt_value => $opt_text) {
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
    
    public function register() {

        $callback = is_null($this->validate) ? null : ["sanitize_callback" => array($this, 'callback')];
        
        register_setting(
             $this->parent->key
            ,$this->key
            ,$callback
        );

    }

}