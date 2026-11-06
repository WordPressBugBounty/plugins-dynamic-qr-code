<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class JsCode
{
    public $content;
    public $callback;
    public $before;

    public function __construct( ) {
        $this->content = null;
        $this->callback = null;
        $this->before = false;
    }
}