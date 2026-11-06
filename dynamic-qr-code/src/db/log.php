<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\DB;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;
use SOSIDEE_DYNAMIC_QRCODE\SRC\LogStatus as Log_SearchStatus;
use SOSIDEE_DYNAMIC_QRCODE\SRC\OS as Log_SearchOS;
use SOSIDEE_DYNAMIC_QRCODE\SRC\DeviceType as Log_SearchDevType;

class Log extends Table
{

    const QUERY_DT_FROM = 'from';
    const QUERY_DT_TO = 'to';

    public $status = null;
    public $code = null;
    public $qrcode_id = null;
    public $user_key = null;
    public $event_id = null;
    public $op_sys = null;
    public $dev_type = null;
    public $lang = null;
    public $country = null;
    public $region = null;
    public $city = null;

    public $count_me = null;

    public function __construct() {
        parent::__construct('logs');
    }

    public function defineSchema() {

        $this->id = $this->addID("log_id");

        $this->status = $this->addTinyInteger("status");
        $this->city = $this->addVarChar("city", 255)->setDefaultValue('');
        $this->code = $this->addVarChar("code", 255);
        $this->qrcode_id = $this->addInteger("qrcode_id");
        $this->user_key = $this->addVarChar("user_key", 255)->setDefaultValue('');
        $this->event_id = $this->addVarChar("event_id", 255)->setDefaultValue('');
        $this->op_sys = $this->addTinyInteger("op_sys")->setDefaultValue(SRC\OS::UNKNOWN); //operative system
        $this->dev_type = $this->addTinyInteger("dev_type")->setDefaultValue(SRC\DeviceType::UNKNOWN);
        $this->lang = $this->addVarChar("lang");
        $this->country = $this->addVarChar("country");
        $this->region = $this->addVarChar("region");
        $this->count_me = $this->addTinyInteger("count_me")->setNullable(false)->setDefaultValue(1);

        parent::defineSchema();
    }

    public function save( $data, $id = 0 ) {
        if ( $id > 0 ) {
            return $this->update( $data, [ $this->id->name => $id ] );
        } else {
            if ( empty( $data[ $this->event_id->name ]) ) {
                return $this->insert( $data );
            } else {
                $count = $this->getCountByEventId( $data[ $this->event_id->name ] );
                if ( $count !== false ) {
                    if ( $count === 0 ) {
                        return $this->insert( $data );
                    } else {
                        return 1; //qr code already inserted (1 è come true, ma diverso...)
                    }
                } else {
                    return false;
                }
            }

        }
    }

    private function _getCountByEventId( $id ) {

        $results = $this->select( [
             $this->event_id->name => $id
            ,$this->cancelled->name => false
        ] );

        if ( is_array($results) ) {
            return count($results);
        } else {
            return false;
        }
    }

    public function loadList( $filters = [], $orders = [] ) {
        $where = [];

        $status = Log_SearchStatus::QUERY_NAME;

        foreach ( $filters as $key => $value ) {
            if ( $key == $this->code->name && !empty($value) ) {
                $where[ $this->code->name ] = $value;
            } elseif ( $key == $this->qrcode_id->name && $value > 0 ) {
                $where[ $this->qrcode_id->name ] = $value;
            } elseif ( $key == $status && $value != Log_SearchStatus::NONE ) {
                if ( !is_array($value) || !empty($value) ) {
                    $where[ $this->status->name ] = $value;
                }
            } elseif ( $key == $this->op_sys->name && $value != Log_SearchOS::NONE ) {
                if ( !is_array($value) || count($value)>0 ) {
                    $where[ $this->op_sys->name ] = $value;
                }
            } elseif ( $key == $this->dev_type->name && $value != Log_SearchDevType::UNKNOWN ) {
                if ( !is_array($value) || !empty($value) ) {
                    $where[ $this->dev_type->name ] = $value;
                }
            } elseif ( $key == self::QUERY_DT_FROM && $value instanceof \DateTimeInterface ) {
                $where[ "{$this->creation->name}[>=]" ] = $value;
            } elseif ( $key == self::QUERY_DT_TO && $value instanceof \DateTimeInterface ) {
                $where[ "{$this->creation->name}[<=]" ] = $value;
            } elseif ( $key == $this->cancelled->name ) {
                $where[ $key ] = boolval( $value );
            }
        }

        if ( !key_exists( $this->cancelled->name, $where ) ) {
            $where[ $this->cancelled->name ] = false;
        }

        if ( empty($orders) ) {
            $orders = [ $this->creation->name => 'DESC' ];
        }

        return $this->select( $where, $orders );
    }

    private function getCountByEventId( $id ) {
        $filters = [
             $this->event_id->name => $id
            ,$this->cancelled->name => false
        ];
        return $this->count( $filters );
    }


    public function countActiveById( $qrcode_id ) {
        $filters = [
             $this->qrcode_id->name => $qrcode_id
            ,$this->cancelled->name => false
        ];
        return $this->count( $filters );
    }

    public function countActiveByCode( $qrcode_code ) {
        $filters = [
             $this->code->name => $qrcode_code
            ,$this->cancelled->name => false
        ];
        return $this->count( $filters );
    }

    public function countById( $qrcode_id ) {
        $filters = [
             $this->qrcode_id->name => $qrcode_id
            ,$this->cancelled->name => false
            ,$this->count_me->name => 1
        ];

        return $this->count( $filters );
    }

    public function countByCode( $qrcode_code ) {
        $filters = [
             $this->code->name => $qrcode_code
            ,$this->cancelled->name => false
            ,$this->count_me->name => 1
        ];

        return $this->count( $filters );
    }

    public function resetCountByQrCodeId( $qrcode_id ) {
        $data = [
             $this->count_me->name => 0
        ];
        return $this->update( $data, [ $this->qrcode_id->name => $qrcode_id, $this->cancelled->name => false ] );
    }


    public function cancel( $id ) {
        return $this->update( [ $this->cancelled->name => true ], [ $this->id->name => $id ] );
    }

    public function clear() {
        return $this->update( [ $this->cancelled->name => true ], [ $this->cancelled->name => false ] );
    }

}