<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Database
{
    private $native;

    public function __construct($prefix) {

        $this->native = new WpDatabase($prefix);

        Table::setDb( $this->native );

    }

    public function create() {
        $this->native->create();
    }

}