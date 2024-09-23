<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class User
{

    /**
     * Retrieves the list of user roles
     * @return array of items with properties 'name' and 'description'
     *
     * WARNING: Translation of 'description' is NOT granted!!!
     */
    public static function getRoles() {
        $ret = array();
        foreach ( wp_roles()->roles as $role => $details ) {
            $item = new \stdClass();
            $item->name = esc_attr( $role );
            $item->description = translate_user_role( $details['name'] );
            $ret[] = $item;
        }
        return $ret;
    }
    
    /**
     * Retrieves all users ordered by $order
     * 
     * @return array of object
     *      - id
     *      - display_name
     *      - email
     *      - login
     *      - first_name
     *      - last_name
     *      - roles (array)
     */
    public static function getList( $order = 'display_name' ) {
        $ret = array();
        $list = get_users( ['orderby' => $order] );
        for ( $n = 0; $n < count($list); $n++ ) {
            $ret[] = self::load( $list[$n] );
        }
        return $ret;
    }

    /**
     * @return object
     *      - id
     *      - display_name
     *      - email
     *      - login
     *      - first_name
     *      - last_name
     *      - roles (array)
     */
    private static function load($native) {
        $ret = new \stdClass();

        $ret->id = $native->ID;
        $ret->roles = $native->roles;
        $ret->email = !empty( $native->user_email ) ? $native->user_email : '';
        $ret->login = !empty( $native->user_login ) ? $native->user_login : '';
        $ret->display_name = !empty( $native->display_name ) ? $native->display_name : '';
        $ret->first_name = !empty( $native->first_name ) ? $native->first_name : '';
        $ret->last_name = !empty( $native->last_name ) ? $native->last_name : '';

        return  $ret;
    }

    public static function get() {
        $native = wp_get_current_user();
        return  self::load( $native );
    }

    public static function lang() {
        return explode('_',  get_user_locale())[0];
    }

}