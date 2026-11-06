<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic;

class Response
{
    public $status;
    public $error;
    public $message;

    public function __construct() {

        $this->status = Status::UNKNOWN;
        $this->error = false;
        $this->message = false;
    }
}