<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class InstallerSkin extends \Automatic_Upgrader_Skin
{
    private $captured_errors;

    public function __construct( $args = array() ) {
        parent::__construct( $args );
        $this->captured_errors = new \WP_Error();
    }

    public function error( $errors ) {
        if ( is_string( $errors ) ) {
            $this->captured_errors->add( 'skin_error_string', $errors );
        } elseif ( is_wp_error( $errors ) ) {
            foreach ( $errors->get_error_codes() as $code ) {
                $this->captured_errors->add( $code, $errors->get_error_message( $code ) );
                $error_data = $errors->get_error_data( $code );
                if ( $error_data ) {
                    $this->captured_errors->add_data( $error_data, $code );
                }
            }
        } elseif ( is_array( $errors ) ) {
            foreach ($errors as $error_string) {
                if (is_string($error_string)) {
                    $this->captured_errors->add( 'skin_error_array_item', $error_string );
                }
            }
        }
    }

    public function get_captured_errors() {
        return $this->captured_errors->has_errors() ? $this->captured_errors : false;
    }

}