<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class FormButton
{
    const STYLE_SUCCESS = 'color: #ffffff; background-color: #5cb85c; border-color: #4cae4c; cursor: pointer;';
    const STYLE_DANGER = 'color: #ffffff; background-color: #d9534f; border-color: #d43f3a; cursor: pointer;';
    const STYLE_WARNING = 'color: #ffffff; background-color: #f0ad4e; border-color: #f0ad4e; cursor: pointer;';
    const STYLE_SECONDARY = 'color: #ffffff; background-color: #6c757d; border-color: #6c757d; cursor: pointer;';

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
        //echo wp_kses( self::getSubmit( $name, $value, $style, $class, $onclick ), sosidee_allowed_html() );
        sosidee_echo_kses( self::getSubmit( $name, $value, $style, $class, $onclick ) );
    }

    public static function getLink( $value = 'ok', $style = '', $class = '', $onclick = null ) {
        $style = !is_null($style) ? FormTag::getStyle($style,'min-width: 90px; cursor: pointer;') : null;
        $class = $class != '' ? $class : 'button button-primary';
        return self::get( 'button', null, $value, $style, $class, $onclick);
    }

    public static function htmlLink( $value = 'ok', $style = '', $class = '', $onclick = null ) {
        // echo wp_kses( self::getLink( $value, $style, $class, $onclick ), sosidee_allowed_html() );
        sosidee_echo_kses( self::getLink( $value, $style, $class, $onclick ) );
    }

}