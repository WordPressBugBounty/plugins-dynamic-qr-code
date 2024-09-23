<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/***
 * Field for Metaboxes.
 *
 * This class manages only the main properties of the field:
 * - key (key to get the field)
 * - id (html id/name)
 * - value
 * The property 'type' is excluded.

 * The type, the other properties and the layout of the field must be defined using the FormTag class
 * inside the metabox->html() function
 */
class MbField
{
    public $key;
    public $value;
    public $id;
    public $isCheckbox;
    
    public function __construct( $key, $value, $id, $isCheckbox = false ) {
        $this->key = $key;
        $this->value = $value;
        $this->id = $id;
        $this->isCheckbox = $isCheckbox;
    }

    public function getSelect( $data ) {
        $ret = '';

        $html = '';
        $options = $data['options'];
        if ( !is_null($options) ) {
            $counter = 0;
            foreach ( $options as $_value => $_text ) {
                if ( !is_array($_text) ) {
                    $html .= FormTag::get('option',[
                         'id' => "{$this->id}_$counter"
                        ,'name' => "{$this->id}_$counter"
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
                            ,'name' => "{$this->id}_$counter"
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
            ,'name' => $this->id
            ,'html' => $html
            ,'onchange' => $data['onchange'] ?? null
        ]);

        return $ret;
    }

    public function getCheckbox( $label = '' ) {
        $ret = FormTag::get( 'input', [
            'type' => 'checkbox'
            ,'id' => $this->id
            ,'name' => $this->id
            ,'checked' => boolval($this->value)
        ]);

        if ( $label != '' ) {
            $ret .= FormTag::get( 'label', [
                'for' => $this->id
                ,'content' => $label
            ]);
        }

        return $ret;
    }

}