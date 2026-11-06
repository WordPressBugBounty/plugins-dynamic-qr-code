<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Data
{
    public $pid;
    public $key;
    public $status;
    public $lastcheck;

    public function __construct( $pid ) {
        $this->pid = $pid;
        $this->key = '';
        $this->status = Status::UNKNOWN;
        $this->lastcheck = 0;
    }
}