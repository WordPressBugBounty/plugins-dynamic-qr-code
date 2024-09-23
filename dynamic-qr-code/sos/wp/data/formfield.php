<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class FormField
{
    public $type;
    public $id;
    public $name;
    public $value;

    public $description;
    public $cached;
    public $data;

    public function __construct($type, $id, $value) {
        $this->type = $type;
        $this->value = $value;
        $this->id = $id;
        $this->name = '';

        $this->description = '';
        $this->cached = false;
        $this->data = null;
    }

    private function getKeys( $parameters ) {
        $defaults = [
             'class' => null
            ,'style' => null
            ,'maxlength' => null
            ,'min' => null
            ,'max' => null
            ,'step' => null
            ,'width' => null
            ,'height' => null
            ,'cols' => null
            ,'rows' => null

            ,'onclick' => null
            ,'onchange' => null
            ,'onkeydown' => null

            ,'label' => null
            ,'options' => null
            ,'accept' => null
        ];
        return array_merge( $defaults, $parameters );
    }

    private function getStyle($parameter, $default = null) {
        return FormTag::getStyle( $parameter, $default );
    }

    public function get( $parameters = [] ) {
        $ret = '';

        $keys = $this->getKeys( $parameters );


        if ( $this->type == FormFieldType::TEXT ) {

            $ret .= FormTag::get( 'input', [
                    'type' => 'text'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $this->value
                    ,'maxlength' => $keys['maxlength']
                    ,'onclick' => $keys['onclick']
                    ,'onchange' => $keys['onchange']
                    ,'style' => $keys['style']
                    ,'class' => $keys['class']
                    ,'onkeydown' => $keys['onkeydown']
                ]
            );

        } else if ( $this->type == FormFieldType::CHECK ) {

            $html = FormTag::get( 'input', [
                'type' => 'checkbox'
                ,'id' => $this->id
                ,'name' => $this->name
                ,'checked' => boolval($this->value) // ? 'checked' : null
            ]);

            $label = $keys['label'];
            if ( !is_null($label) ) {
                $html .= FormTag::get( 'label', [
                    'for' => $this->id
                    ,'content' => $label
                ]);
            }

            $ret .= $html;

        } else if ( $this->type == FormFieldType::TEXTAREA ) {

            $ret .= FormTag::get( 'textarea', [
                'id' => $this->id
                ,'name' => $this->name
                ,'content' => $this->value
                ,'maxlength' => $keys['maxlength']
                ,'cols' => $keys['cols']
                ,'rows' => $keys['rows']
            ]);

        } else if ( $this->type == FormFieldType::SELECT ) {

            $html = '';
            $options = $keys['options'];
            if ( !is_null($options) ) {
                $counter = 0;
                foreach ( $options as $_value => $_text ) {
                    if ( !is_array($_text) ) {
                        $html .= FormTag::get('option',[
                            'id' => "{$this->id}_$counter"
                            ,'name' => "{$this->name}_$counter"
                            ,'value' => $_value
                            ,'selected' => strcasecmp($_value, $this->value) == 0
                            ,'content' => $_text
                        ]);
                        $counter++;
                    } else {
                        $gr_html = '';
                        foreach ($_text as $gr_value => $gr_text) {
                            $gr_html .= FormTag::get('option',[
                                'id' => "{$this->id}_$counter"
                                ,'name' => "{$this->name}_$counter"
                                ,'value' => $gr_value
                                ,'selected' => strcasecmp($gr_value, $this->value) == 0
                                ,'content' => $gr_text
                            ]);
                            $counter++;
                        }

                        $html .= FormTag::get('optgroup',[
                             'label' => $_value
                            ,'html' => $gr_html
                        ]);
                    }
                }

            }

            $ret .= FormTag::get( 'select', [
                'id' => $this->id
                ,'name' => $this->name
                ,'html' => $html
                ,'onchange' => $keys['onchange']
            ]);

        } else if ( $this->type == FormFieldType::NUMBER ) {

            $ret .= FormTag::get( 'input', [
                    'type' => 'number'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $this->value
                    ,'min' => $keys['min']
                    ,'max' => $keys['max']
                    ,'step' => $keys['step']
                    ,'onclick' => $keys['onclick']
                    ,'onchange' => $keys['onchange']
                ]
            );

        } else if ( $this->type == FormFieldType::COLOR ) {

            $ret .= FormTag::get( 'input', [
                    'type' => 'color'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $this->value
                    ,'onclick' => $keys['onclick']
                    ,'onchange' => $keys['onchange']
                    ,'style' => $this->getStyle( $keys['style'], 'cursor:pointer;' )
                ]
            );

        } else if ( $this->type == FormFieldType::DATE ) {

            $ret .= FormTag::get( 'input', [
                    'type' => 'date'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $this->value
                    ,'min' => $keys['min']
                    ,'max' => $keys['max']
                    ,'step' => $keys['step']
                    ,'onclick' => $keys['onclick']
                    ,'onchange' => $keys['onchange']
                    ,'style' => $this->getStyle( $keys['style'], 'cursor:pointer;' )
                ]
            );

        } else if ( $this->type == FormFieldType::TIME ) {

            $ret .= FormTag::get( 'input', [
                    'type' => 'time'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $this->value
                    ,'min' => $keys['min']
                    ,'max' => $keys['max']
                    ,'step' => $keys['step']
                    ,'onclick' => $keys['onclick']
                    ,'onchange' => $keys['onchange']
                    ,'style' => $this->getStyle( $keys['style'], 'cursor:pointer;' )
                ]
            );

        } else if ( $this->type == FormFieldType::HIDDEN ) {

            $ret .= FormTag::get( 'input', [
                    'type' => 'hidden'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $this->value
                ]
            );

        } else if ( $this->type == FormFieldType::FILE ) {

            $ret .= FormTag::get( 'input', [
                    'type' => 'file'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $this->value
                    ,'accept' => $keys['accept']
                ]
            );

        } else if ( $this->type == FormFieldType::COMBOBOX ) {

            $jsFunc = $this->getJsFuncName( $this->id );

            $fld_text = new self( FormFieldType::TEXT, $this->id, $this->value );
            $fld_text->name = $this->name;
            $ret .= $fld_text->get( $parameters );

            $fld_select = new self( FormFieldType::SELECT, "{$this->id}_select", $this->value );
            $fld_select->name = "{$this->name}_select";
            if ( is_array($parameters) ) {
                $parameters['onchange'] = "{$jsFunc}(this.value)";
            } else {
                $parameters = [ 'onchange' => "{$jsFunc}(this.value)" ];
            }
            $ret .= $fld_select->get( $parameters );

            $js = <<<EOD
function {$jsFunc}( v ) {
    let field = self.document.getElementById( '{$this->id}' );
    if (field) {
        field.value = v;
        if (v == '') {
            field.focus();
        }
    } else {
        alert('A javascript problem occurred in your browser.');
    }
}
EOD;

            $ret .= FormTag::get( 'script', [
                 'type' => 'application/javascript'
                ,'content' => $js
            ]);

        }

        $description = trim( $this->description );
        if ( $description != '' ) {
            if ( $description == strip_tags($description) ) {
                $html = HtmlTag::get( 'span', [ 'content' => $description, 'style' => 'font-style:italic;' ]);
                $ret .= HtmlTag::get( 'p', [ 'html' => $html ]);
            } else {
                $ret .= $description;
            }
        }

        return  $ret;
    }

    public function html( $parameters = [] ) {
        echo self::get( $parameters );
    }

    private function getDatetimeFromString($value, $format = 'Y-m-d H:i:s') {
        try {
            $ret = \DateTime::createFromFormat($format, $value);
            if ( !($ret instanceof \DateTime) ) {
                $ret = null;
            }
        }
        catch (\Exception $e) {
            sosidee_log($e);
            $ret = null;
        }
        return $ret;
    }


    public function getValueAsDate( $end_of_day = false ) {
        if ( is_null( $this->value ) ) {
            return null;
        }
        $value = trim( $this->value );
        if ( empty($value) ) {
            return null;
        }
        $value = !$end_of_day ? "$value 00:00:00" : "$value 23:59:59";
        return $this->getDatetimeFromString( $value );
    }

    public function setValueFromDate( $value, $format = 'Y-m-d' ) {
        if ( $value instanceof \DateTime ) {
            $this->value = $value->format($format);
        } else {
            $this->value = $value;
        }
    }

    private function getTimeFromString($value, $format = 'H:i:s') {
        try {
            $ret = \DateTime::createFromFormat($format, $value);
            if ( !($ret instanceof \DateTime) ) {
                $ret = null;
            }
        }
        catch (\Exception $e) {
            sosidee_log($e);
            $ret = null;
        }
        return $ret;
    }


    public function getValueAsTime() {
        if ( is_null( $this->value ) ) {
            return null;
        }
        $value = trim( $this->value );
        if ( empty($value) ) {
            return null;
        }
        if ( strlen($value) == 5 ) {
            $value .= ':00';
        }
        return $this->getTimeFromString( $value );
    }

    public function setValueFromTime( $value, $format = 'H:i' ) {
        if ( $value instanceof \DateTime ) {
            $this->value = $value->format($format);
        } else {
            $this->value = $value;
        }
    }


    private function getJsFuncName($key) {
        return 'js' . str_replace( ['-', '[', ']'], ['_'], $key );
    }

}