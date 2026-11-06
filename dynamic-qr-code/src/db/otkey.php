<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\DB;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;

class OTKey extends TableBase
{

    public $qrcode_id = null;
    public $code = null;
    public $tally = null;

    public function __construct() {
        parent::__construct('otkeys');
    }

    public function defineSchema() {

        $this->id = $this->addID("otk_id");

        $this->qrcode_id = $this->addInteger("qrcode_id");
        $this->code = $this->addVarChar("code", 255);
        $this->tally = $this->addInteger("tally")->setDefaultValue(0);

        parent::defineSchema();
    }

    public function save( $data_or_id, $value = null ) {
        if ( is_array($data_or_id) ) {
            return $this->insert( $data_or_id );
        } elseif ( is_int($data_or_id) && is_int($value) ) {
            $data = [
                $this->tally->name => $value
            ];
            $filters = [
                $this->id->name => $data_or_id
            ];
            return $this->update( $data, $filters );
        } else {
            sosidee_log("DB\OTKey::save() - invalid parameter(s): data_or_id=" . print_r($data_or_id, true) . "; value=" . print_r($value, true));
            return false;
        }
    }

    public function load( $key, $qrcode_id ) {
        $results = $this->select( [
             $this->code->name => $key
            ,$this->qrcode_id->name => $qrcode_id
        ] );

        if ( is_array($results) && count($results) == 1 ) {
            return $results[0];
        } else {
            sosidee_log("DB\OTKey::load({$qrcode_id}) - failed for key={$key}.");
            return false;
        }
    }

    /*
    public function consumeOnce( $key, $qrcode_id ) {
        //sbagliata: se il record non esiste bisogna usare insert()
        $data = [
            $this->tally->name => 1
        ];

        $filters = [
            $this->code->name      => $key,
            $this->qrcode_id->name => $qrcode_id,
            $this->tally->name     => 0
        ];

        return $this->update( $data, $filters, true ) === 1;
    }
    */


}