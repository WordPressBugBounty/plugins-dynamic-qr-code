<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class FormField
{
    use TDateTime;

    public $type;
    public $id;
    public $name;
    public $value;

    public $description;
    public $cached;
    public $data; // used with type FILE

    public $isUrl;
    public $isEmail;

    public function __construct( $type, $name, $value ) {
        $this->type = $type;
        $this->name = $name;
        $this->id = $name;
        $this->value = $value;

        $this->description = '';
        $this->cached = false;
        $this->data = null;

        $this->isUrl = false;
        $this->isEmail = false;
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
            ,'size' => null

            ,'onclick' => null
            ,'onchange' => null
            ,'onkeydown' => null

            ,'label' => null
            ,'options' => null
            ,'accept' => null
            ,'readonly' => null
            ,'disabled' => null
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
                    ,'size' => $keys['size']
                    ,'onclick' => $keys['onclick']
                    ,'onchange' => $keys['onchange']
                    ,'style' => $keys['style']
                    ,'class' => $keys['class'] // regular-text | small-text | large-text
                    ,'onkeydown' => $keys['onkeydown']
                    ,'readonly' => $keys['readonly'] ?? false
                ]
            );

        } else if ( $this->type == FormFieldType::CHECK ) {

            $disabled = $keys['disabled'] ?? null;
            $style = $keys['style'];
            if ( $disabled ) {
                $style = $this->getStyle( $style, 'cursor:not-allowed;' );
            }

            $ctrl = FormTag::get( 'input', [
                'type' => 'checkbox'
                ,'id' => $this->id
                ,'name' => $this->name
                ,'checked' => boolval($this->value) // ? 'checked' : null
                ,'disabled' => $disabled
                ,'style' => $style
                ,'class' => $keys['class'] ?? null
                ,'onchange' => $keys['onchange'] ?? null
            ]);

            $label = $keys['label'] ?? '';
            if ( !empty($label) ) {
                //$cursor = $disabled !== true ? 'cursor:pointer;' : 'cursor:not-allowed;'; $cursor .
                $style = $this->getStyle( $keys['style'], 'align-items:center; display:inline-flex;' );
                $html = $ctrl . '<span>' . $label . '</span>';

                $ret .= FormTag::get( 'label', [
                     'for' => $this->id
                    ,'html' => $html
                    ,'style' => $style
                ]);
            } else {
                $ret .= $ctrl;
            }

        } else if ( $this->type == FormFieldType::TEXTAREA ) {

            $ret .= FormTag::get( 'textarea', [
                 'id' => $this->id
                ,'name' => $this->name
                ,'content' => $this->value
                ,'maxlength' => $keys['maxlength']
                ,'cols' => $keys['cols']
                ,'rows' => $keys['rows']
                ,'readonly' => $keys['readonly'] ?? false
            ]);

        } else if ( $this->type == FormFieldType::RADIO ) {

            $options = $keys['options'] ?? null;
            if ( is_array($options) ) {
                foreach ( $options as $_value => $_text ) {
                    if ( $ret != '' ) {
                        $ret .= '<br>';
                    }
                    $item_id = $this->id . '_' . sosidee_check_id($_value);
                    $ret .= FormTag::get('input',[
                         'type' => 'radio'
                        ,'id' => $item_id
                        ,'name' => $this->name
                        ,'value' => $_value
                        ,'checked' => strcasecmp($_value, $this->value) == 0
                        ,'onclick' => $keys['onclick']
                    ]);
                    $ret .= FormTag::get( 'label', [
                         'for' => $item_id
                        ,'content' => $_text
                    ]);
                }
            }

        } else if ( $this->type == FormFieldType::SELECT ) {

            $html = '';
            $options = $keys['options'];
            if ( !is_null($options) ) {
                foreach ( $options as $_value => $_text ) {
                    $item_id = $this->id . '_' . sosidee_check_id($_value);
                    if ( !is_array($_text) ) {
                        $html .= FormTag::get('option',[
                             'id' => $item_id
                            ,'value' => $_value
                            ,'selected' => strcasecmp($_value, $this->value) == 0
                            ,'content' => $_text
                        ]);
                    } else {
                        $gr_html = '';
                        foreach ($_text as $gr_value => $gr_text) {
                            $gr_html .= FormTag::get('option',[
                                 'id' => $item_id
                                ,'value' => $gr_value
                                ,'selected' => strcasecmp($gr_value, $this->value) == 0
                                ,'content' => $gr_text
                            ]);
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
                ,'style' => $keys['style']
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
                    ,'style' => $keys['style']
                    ,'class' => $keys['class']
                    ,'onclick' => $keys['onclick']
                    ,'onchange' => $keys['onchange']
                    ,'readonly' => $keys['readonly'] ?? false
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
                    ,'readonly' => $keys['readonly'] ?? false
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
                    ,'readonly' => $keys['readonly'] ?? false
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
                    ,'readonly' => $keys['readonly'] ?? false
                ]
            );

        } else if ( $this->type == FormFieldType::HIDDEN ) {

            $value = $this->value;
            if ( is_array($value) ) {
                $value = json_encode($value);
            }

            $ret .= FormTag::get( 'input', [
                     'type' => 'hidden'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $value
                ]
            );

        } else if ( $this->type == FormFieldType::FILE ) {

            $ret .= FormTag::get( 'input', [
                     'type' => 'file'
                    ,'id' => $this->id
                    ,'name' => $this->name
                    ,'value' => $this->value // anyway it will be ignored by the browse
                    ,'accept' => $keys['accept']
                    ,'style' => $this->getStyle( $keys['style'], 'cursor:pointer;' )
                ]
            );

        } else if ( $this->type == FormFieldType::COMBOBOX ) {

            $fld_text = new self( FormFieldType::TEXT, $this->name, $this->value );
            $fld_text->name = $this->name;
            $ret .= $fld_text->get( $parameters );

            $fld_select = new self( FormFieldType::SELECT, "{$this->id}_select", $this->value );
            $fld_select->name = null; // so it will not be posted
            if ( is_array($parameters) ) {
                $parameters['onchange'] = "jsSosCombobox(this.value, '{$this->id}')";
            } else {
                $parameters = [ 'onchange' => "jsSosCombobox(this.value, '{$this->id}')" ];
            }
            $ret .= $fld_select->get( $parameters );

        } elseif ( $this->type == FormFieldType::CHECKLIST ) {

            $options = $keys['options'] ?? [];
            $base_id = $this->id;
            $name = $this->name . '[]';
            $count = 0;
            foreach ( $options as $value => $text ) {
                $current_id = "{$base_id}_{$count}";
                if ( $ret != '' ) {
                    $ret .= '<br>';
                }
                $ctrl = FormTag::get( 'input', [
                     'type' => 'checkbox'
                    ,'id' => $current_id
                    ,'name' => $name
                    ,'value' => $value
                    ,'checked' => is_array($this->value) && in_array($value, $this->value)
                    ,'style' => $this->getStyle( $keys['style'], 'cursor:pointer;' )
                ]);
                $html = $ctrl . '<span>' . $text . '</span>';
                $ret .= FormTag::get( 'label', [
                    'for' => $current_id
                    ,'html' => $html
                    ,'style' => $this->getStyle( $keys['style'], 'cursor:pointer; align-items: center; display:inline-flex;' )
                ]);
                $count++;
            }

        } else {

            // FieldType::LABEL
            $ret .= FormTag::get( 'label', [
                 'id' => $this->id
                ,'name' => $this->name
                ,'html' => $this->value
                ,'style' => $keys['style'] ?? null
                ,'class' => $keys['class'] ?? null
            ]);

        }

        $description = trim( $this->description );
        if ( $description != '' ) {
            if ( $description == wp_strip_all_tags($description) ) {
                $html = FormTag::get( 'span', [ 'content' => $description, 'style' => 'font-style:italic;' ] );
                $ret .= FormTag::get( 'p', [ 'html' => $html ] );
            } else {
                $ret .= $description;
            }
        }

        return  $ret;
    }

    /**
     * Outputs the HTML for the form field.
     *
     * The output is filtered through wp_kses(), in the sosidee_echo_kses() method, using a custom ruleset
     * as required by the WordPress.org plugin review team. This acts as a final security blanket
     * and a manifest of all allowed HTML generated by the plugin's form builder.
     *
     * @param array $parameters
     */
    public function html( $parameters = [] ) {
        //echo wp_kses( self::get( $parameters ), sosidee_allowed_html() );
        sosidee_echo_kses( self::get( $parameters ) );
    }

    public function getValueAsDate( $end_of_day = false ) {
        if ( is_null( $this->value ) ) {
            return null;
        }
        $value = trim( $this->value );
        if ( empty($value) ) {
            return null;
        }
        $ret = $this->getDateFromString( $value, 'Y-m-d' );
        if ( $ret instanceof \DateTimeInterface ) {
            if ($end_of_day) {
                return $ret->setTime(23, 59, 59);
            } else {
                return $ret;
            }
        } else {
            sosidee_log("FormField::getValueAsDate() - Could not parse date string from field value: " . $value);
            return null;
        }
    }

    public function setValueFromDate( $value, $format = 'Y-m-d' ) {
        if ( is_null( $value ) ) {
            $this->value = '';
        } else {
            if ( $value instanceof \DateTimeInterface ) {
                $this->value = self::getDateAsString($value, $format);
            } else {
                if ( is_string($value) ) {
                    $test = self::getDateFromString(trim($value), $format);
                    if ( $test instanceof \DateTimeInterface ) {
                        $this->setValueFromDate( $test, $format );
                    } else {
                        sosidee_log("FormField::setValueFromDate() - Unexpected value type: " . gettype($value));
                        $this->value = '';
                    }
                } else {
                    $this->value = '';
                }
            }
        }
    }

    public function getValueAsTime() {
        if ( is_null( $this->value ) ) {
            return null;
        }
        $value = trim( $this->value );
        if ( empty($value) ) {
            return null;
        }
        $ret = $this->getTimeFromString( $value, 'H:i:s' );
        if ( is_null($ret) ) {
            $ret = $this->getTimeFromString( $value, 'H:i' );
        }
        if ( $ret instanceof \DateTimeInterface ) {
            return $ret;
        } else {
            sosidee_log("FormField::getValueAsTime() - Could not parse date string from field value: " . $value);
            return null;
        }
    }

    public function setValueFromTime( $value, $format = 'H:i' ) {
        if ( is_null( $value ) ) {
            $this->value = '';
        } else {
            if ( $value instanceof \DateTimeInterface  ) {
                $this->value = $value->format($format);
            } else {
                if ( is_string($value) ) {
                    $test = self::getTimeFromString(trim($value), $format);
                    if ( $test instanceof \DateTimeInterface ) {
                        $this->setValueFromTime( $test, $format );
                    } else {
                        sosidee_log("FormField::setValueFromTime() - Unexpected value type: " . gettype($value));
                        $this->value = '';
                    }
                } else {
                    $this->value = '';
                }
            }
        }
    }

    /**
     * @deprecated form uses self::plugin()->addFormJs();
    * private function getJsFuncName($key) {
        * return 'js' . str_replace( ['-', '[', ']'], ['_'], $key );
    * }
    */
}
