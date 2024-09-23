<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class FormButton
{
    const STYLE_SUCCESS = 'color: #ffffff; background-color: #5cb85c; border-color: #4cae4c;';
    const STYLE_DANGER = 'color: #ffffff; background-color: #d9534f; border-color: #d43f3a;';

    private static function get( $type, $name, $value, $style, $class, $onclick ) {
        return FormTag::get( 'input', [
                'type' => $type
                ,'id' => $name
                ,'name' => $name
                ,'value' => $value
                ,'class' => $class
                ,'style' => $style
                ,'onclick' => $onclick
                ,'title' => $value
            ]
        );
    }


    public static function getSubmit( $name, $value = 'ok', $style = '', $class = '', $onclick = null ) {
        $style = !is_null($style) ? FormTag::getStyle($style,'min-width: 90px; cursor: pointer;') : null;
        $class = $class != '' ? $class : 'button button-primary';
        return self::get( 'submit', $name, $value, $style, $class, $onclick);
    }

    public static function htmlSubmit( $name, $value = 'ok', $style = '', $class = '', $onclick = null ) {
        echo sosidee_kses( self::getSubmit( $name, $value, $style, $class, $onclick ) );
    }

    public static function getLink( $value = 'ok', $style = '', $class = '', $onclick = null ) {
        $style = !is_null($style) ? FormTag::getStyle($style,'min-width: 90px; cursor: pointer;') : null;
        $class = $class != '' ? $class : 'button button-primary';
        return self::get( 'button', null, $value, $style, $class, $onclick);
    }

    public static function htmlLink( $value = 'ok', $style = '', $class = '', $onclick = null ) {
        echo sosidee_kses( self::getButton( null, $value, $style, $class, $onclick ) );
    }

}