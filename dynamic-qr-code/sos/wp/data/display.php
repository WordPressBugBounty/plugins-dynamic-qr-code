<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Display
{

    public static function getColumn( $head = [ [], null ], $body = [ [], null ] ) {
        if ( !is_array( $head ) ) {
            $head  = [ [], null ];
        }
        if ( count( $head ) == 1 ) {
            $head[0] = $head;
            $head[] = null;
        }
        if ( count( $body ) == 1 ) {
            $body[] = null;
        }
        return [
             'head' => self::getCell( $head[0], $head[1] )
            ,'body' => self::getCell( $body[0], $body[1] )
        ];
    }

    public static function getCell( $attributes = [], $callback = null) {
        if ( !is_array( $attributes ) ) {
            $attributes  = [];
        }
        return [
             'attrs'    => $attributes
            ,'callback' => $callback
        ];
    }

    private static function htmlCell( $data, $tag ) {

    }

    public static function htmlHead( $table, $context ) {
        foreach ( $table as $data ) :
            if ( is_array( $data ) ) {
                if ( isset($data['head']) && is_array( $data['head'] ) ) {
                    echo '<th';
                    $cell = $data['head'];
                    if ( isset($cell['attrs']) && is_array( $cell['attrs'] ) ) {
                        foreach ( $cell['attrs'] as $attribute => $value ) {
                            echo ' ' . esc_attr($attribute) . '="' . esc_attr($value) . '"';
                        }
                    }
                    echo '>';
                    if ( isset($cell['callback']) && is_callable($cell['callback']) ) {
                        $cell['callback']($context);
                    }
                    echo '</th>';
                }
            }
        endforeach;
    }

    public static function htmlRow( $table, $item, $context = [] ) {
        echo '<tr>';
        foreach ( $table as $data ) :
            if ( isset($data['body']) && is_array( $data['body'] ) ) {
                echo '<td';
                $cell = $data['body'];
                if ( isset($cell['attrs']) && is_array( $cell['attrs'] ) ) {
                    foreach ( $cell['attrs'] as $attribute => $value ) {
                        echo ' ' . esc_attr($attribute) . '="' . esc_attr($value) . '"';
                    }
                }
                echo '>';
                if ( isset($cell['callback']) && is_callable($cell['callback']) ) {
                    $cell['callback']($item, $context);
                }
                echo '</td>';
            }
        endforeach;
        echo '</tr>';
    }

    public static function insertBefore( $reference, $element, $table ) {
        if ( empty($table) || array_key_first($table) === $reference || !array_key_exists($reference, $table) ) {
            return $element + $table;
        }
        $results = [];
        $key = array_keys($element)[0];
        foreach ( $table as $k => $v ) {
            if ( $reference === $k ) {
                $results[$key] = $element[$key];
            }
            $results[$k] = $v;
        }
        return $results;
    }

    public static function insertAfter( $reference, $element, $table ) {
        $key = array_keys($element)[0];
        if ( empty($table) || array_key_last($table) === $reference || !array_key_exists($reference, $table) ) {
            $table[$key] = $element[$key];
            return $table;
        }
        $results = [];
        foreach ( $table as $k => $v ) {
            $results[$k] = $v;
            if ( $reference === $k ) {
                $results[$key] = $element[$key];
            }
        }
        return $results;
    }

}