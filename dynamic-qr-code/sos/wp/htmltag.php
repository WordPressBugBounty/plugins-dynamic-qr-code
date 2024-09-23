<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class HtmlTag
{
    private static $containers = [
        'a', 'b', 'button', 'caption', 'code', 'col', 'colgroup'
        , 'data', 'div', 'em' //, 'form'
        , 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
        , 'i'
        , 'label', 'legend', 'li', 'nav'
        , 'ol', 'optgroup', 'option'
        , 'p', 'pre', 'script', 'select', 'span', 'strong'
        , 'table', 'tbody', 'td', 'textarea', 'th', 'thead', 'title', 'tr'
        , 'ul'
    ];

    public static function get() {
        $args = func_get_args();
        $tag = $args[0]; // mandatory

        $is_container = in_array( $tag, self::$containers );

        $content = '';
        $ret = "<{$tag}";
        if ( func_num_args() > 1 ) {
            $attributes = $args[1];
            foreach ( $attributes as $key => $value ) {
                if ( $key == 'checked' || $key == 'selected' ) {
                    if ( $value === true ) {
                        $ret .= " $key";
                    }
                } else {
                    if ( !is_null($value) ) {
                        if ($key == 'html') {
                            $content .= $value; // sanitized in the previous call of this function
                        } else if ( $key == 'content' ) {
                            if ( $tag != 'script' ) {
                                $content .= esc_textarea( $value );
                            } else {
                                $content .= $value; // esc_js(): escaped javascript does NOT work!
                            }
                        } else if ( $key == 'href' ) {
                            $ret .= " $key=\"" .  esc_url( $value ) . '"';
                        } else if ( $key == 'src' ) {
                            if ( !sosidee_str_starts_with($value, 'data:') ) {
                                $ret .= " $key=\"" .  esc_url( $value ) . '"';
                            } else {
                                $ret .= " $key=\"" .  esc_attr( $value ) . '"';
                            }
                        } else {
                            $ret .= " $key=\"" . esc_attr( $value ) . '"';
                        }
                    }
                }
            }
        }
        $ret .= ">";
        if ( $is_container ) {
            $ret .= "$content</$tag>";
        }
        return $ret;
    }

    public static function html() {
        $args = func_get_args();
        if ( func_num_args() == 1) {
            echo sosidee_kses( self::get( $args[0] ) );
        } else if ( func_num_args() == 2) {
            echo sosidee_kses( self::get( $args[0], $args[1] ) );
        } else {
            echo "wrong number of arguments in HtmlTag.html()";
        }
    }


}