<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class ShortCode
{

    public $isTrueCall; // it should be true only if the call comes from the plugin that created the shortcode

	public $callback;
    public $tag;

    public function __construct( $tag, $callback ) {
        //@TODO: set it to false as long as the TODO at line 32 will be performed
        $this->isTrueCall = true; // in order not to stop Elementor to work

        $this->tag = $tag;
        $this->callback = $callback;
        
        add_shortcode( $this->tag, array( $this, 'sanitize' ) );
    }
    
    public function sanitize( $attributes, $content, $tag ) {

        if ( sosidee_is_rest() ) {
            // do not sanitize if it's a block editor api call (in this case is_admin() always returns false and can't be used)
            return;
        }

        if ( !$this->isTrueCall ) {
            // prevent other plugins to call the $callback function
            //@TODO: unless the call comes from Elementor (otherwise its display doesn't work...)
            return;
        }

        $args = array();
        if (is_array($attributes)) {
            foreach ( $attributes as $key => $value ) {
                $args[ sanitize_key($key) ] = sanitize_text_field($value);
            }
        }
        return call_user_func( $this->callback, $args, $content, $tag );
    }
    
}