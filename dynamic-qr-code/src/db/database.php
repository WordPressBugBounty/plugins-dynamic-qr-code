<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\DB;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

class Database extends DATA\Database
{

    public $qrcodes;
    public $logs;
    public $otkeys;

    public function __construct() {
        parent::__construct( 'dqc' );

        // TABLES
        $this->qrcodes = new QrCode();
        $this->logs = new Log();
        $this->otkeys = new OTKey();

        $this->create();
    }

}