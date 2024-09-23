<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Elementor;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Handler
{
    public $class;
    public $native;

    public function __construct( $class ) {
        $this->class = $class;
        $this->native = null;
    }
}