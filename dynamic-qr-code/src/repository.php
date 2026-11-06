<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Repository extends \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Repository
{
    public static function getAddon() {
        return self::getData('sos-dynamic-qr-code-pro');
    }

}