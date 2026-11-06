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
                if ( in_array( $key, ['checked', 'selected', 'readonly', 'disabled'] ) ) {
                    if ( $value === true ) {
                        $ret .= " $key";
                    }
                } else {
                    if ( !is_null($value) ) {
                        if ( $key == 'html' ) {
                            $content .= wp_kses( $value, sosidee_allowed_html() );
                        } elseif ( $key == 'content' ) {
                            if ( $tag != 'script' ) {
                                $content .= $tag !== 'textarea' ? esc_html( $value ) : esc_textarea( $value );
                            } else {
                                $content .= $value; // esc_js(): escaped javascript does NOT work!
                            }
                        } elseif ( $key == 'href' ) {
                            $ret .= " $key=\"" .  esc_url( $value ) . '"';
                        } else if ( $key == 'src' ) {
                            if ( !sosidee_str_starts_with($value, 'data:') ) {
                                $ret .= " $key=\"" .  esc_url( $value ) . '"';
                            } else {
                                $ret .= " $key=\"" .  esc_attr( $value ) . '"';
                            }
                        } elseif ( $key == 'download' ) {
                            if ( $value === true ) {
                                $ret .= " $key";
                            } elseif ( is_string($value) ) {
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
        if ( func_num_args() == 1 ) {
            sosidee_echo_kses( self::get( $args[0] ) );
        } else if ( func_num_args() == 2 ) {
            sosidee_echo_kses( self::get( $args[0], $args[1] ) );
        } else {
            echo "wrong number of arguments in HtmlTag.html()";
        }
    }

     public static function getStyle( $parameter, $default = null ) {
        $parseStyles = function ($style) {
            $styles = [];
            foreach ( explode(';', $style) as $rule ) {
                $rule = trim($rule);
                if ( !$rule ) continue; // skip blank lines

                $pos = strpos($rule, ':');
                if ( $pos === false ) continue; // skip invalid rules

                $key = strtolower(trim(substr($rule, 0, $pos))); // normalized to lowercase
                $value = trim(substr($rule, $pos + 1));

                $styles[$key] = rtrim($value, ';'); // remove the last ';' if present
            }
            return $styles;
        };

        // merge rules ($parameter overwrites $default)
        $mergedStyles = array_merge(
            $default ? $parseStyles($default) : [],
            $parameter ? $parseStyles($parameter) : []
        );

        // build the resulting style string
        return implode('; ', array_map(fn($k, $v) => "$k: $v", array_keys($mergedStyles), $mergedStyles)) . ';';
    }


}