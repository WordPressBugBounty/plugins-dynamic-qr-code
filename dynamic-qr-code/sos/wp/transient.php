<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Transient
{
    use TBase;

    private static function key($key) {
        $key = '_' . ltrim( trim( $key ), '_');
        return self::plugin()->key . $key;
    }

    public static function get($key) {
        $_key = self::key($key);
        return get_transient($_key);
    }

    public static function set($key, $value, $expiration = 0) {
        $_key = self::key($key);
        return set_transient($_key, $value, $expiration);
    }

    public static function del(string $key) {
        $_key = self::key($key);
        return delete_transient($_key);
    }

    public static function getUserKey( $prefix = '' ) {
        $user_id = get_current_user_id();
        if ( !$user_id ) {
            return false;
        }
        $prefix = rtrim( trim( $prefix ), '_') . '_';
        return $prefix . $user_id;
    }
}