<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\LIBS\Psr;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Loader
{
    public static function execute() {
        Cache\Loader::execute();
        SimpleCache\Loader::execute();
    }

}