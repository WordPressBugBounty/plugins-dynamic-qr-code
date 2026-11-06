<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class MessageType
{
    public const SUCCESS = 'success';
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    private function __construct()
    {
    }
}