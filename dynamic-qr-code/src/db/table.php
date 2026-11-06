<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\DB;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

class Table extends TableBase
{
    use SOS_WP\TMessage;

    public $cancelled = null;

    public function __construct( $name ) {
        parent::__construct( $name );
    }

    public function defineSchema() {
        $this->cancelled = $this->addBoolean("cancelled")->setDefaultValue(false);

        parent::defineSchema();
    }


}