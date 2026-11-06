<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TOptions
{
    use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TBase;

    public static $FIELD_DEF_VAL = '-';

    private static function getFieldKey($field) {
        return self::plugin()->key . '_' . $field->id;
    }

    protected function loadOption( &$field, $default_value = null ) {
        $key = self::getFieldKey($field);
        $value = $field->value;
        $value = get_option($key, $value);
        if ( !is_null($default_value) && $value == self::$FIELD_DEF_VAL ) {
            $value = $default_value;
        }
        $field->value = $value;
        return $field->value;
    }

    protected function saveOption( &$field ) {
        $key = self::getFieldKey($field);
        $old_value = get_option( $key, null );
        $value = $field->value;
        if ( is_scalar( $value ) || is_null( $value ) ) {
           if ( $old_value === (string)$value ) {
               return true;
           }
        } else {
            if ( $value == $old_value ) {
                return true;
            }
        }
        return update_option($key, $value);
    }

    protected function deleteOption( &$field ) {
        $key = self::getFieldKey($field);
        if ( get_option($key) ) {
            $ret = delete_option($key);
        } else {
            $ret = true;
        }
        if ( $ret ) {
            $field->value = self::$FIELD_DEF_VAL;
        }
        return $ret;
    }

}