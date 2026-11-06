<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

// if cache_users() is not loaded (with WP 6.1 - Fixed in WP 6.1.1)
if ( ! function_exists( 'cache_users' ) ) {
    require_once realpath(ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'pluggable.php');
}

class UserData {
    public $id = 0;
    public $display_name = '';
    public $email = '';
    public $login = '';
    public $first_name = '';
    public $last_name = '';
    public $roles = [];
}

class User
{

    /**
     * Retrieves the list of user roles
     * @return array<int, object{name: string, description: string}>
     *
     * WARNING: Translation of 'description' is NOT granted!!!
     */
    public static function getRoles() {
        $ret = array();

        if ( function_exists('get_editable_roles') ) {
            $roles = get_editable_roles();
        } else {
            $roles = wp_roles()->roles;
        }
        foreach ( $roles as $slug => $details) {
            $item = new \stdClass();
            $item->name = $slug;
            $item->description = translate_user_role( $details['name'] );
            $ret[] = $item;
        }

        return $ret;
    }
    
    /**
     * Retrieves all users ordered by $order
     * 
     * @return array<int, UserData>
     */
    public static function getList( $order = 'display_name' ) {
        $ret = array();
        $list = get_users( ['orderby' => $order] );
        foreach ($list as $item) {
            $ret[] = self::load($item);
        }
        return $ret;
    }

    /**
     * @param \WP_User $native
     * @return UserData
     */
    public static function load($native) {
        $ret = new UserData();

        if ( $native->ID > 0 ) {
            $ret->id           = $native->ID;
            $ret->roles        = $native->roles ?? [];
            $ret->email        = $native->user_email ?? '';
            $ret->login        = $native->user_login ?? '';
            $ret->display_name = $native->display_name ?? '';
            $ret->first_name   = $native->first_name ?? '';
            $ret->last_name    = $native->last_name ?? '';
        }

        return $ret;
    }

    public static function get() {
        $native = wp_get_current_user();
        return self::load( $native );
    }

    public static function lang() {
        $locale = get_user_locale();
        return substr($locale, 0, 2);
    }

}