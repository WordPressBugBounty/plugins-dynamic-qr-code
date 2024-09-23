<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

class Database
{
    private $native;

    public function __construct() {

        $this->native = new DATA\WpDatabase('sos_dqc_');

        // TABLE QR-CODES
        $tab1 = $this->native->addTable("qrcodes");
        $tab1->addID("qrcode_id");
        $tab1->addBoolean("disabled")->setDefaultValue(false);
        $tab1->addVarChar("code", 255);
        $tab1->addVarChar("description", 255);
        $tab1->addVarChar("url_redirect", 255);
        $tab1->addVarChar("url_inactive", 255);
        $tab1->addVarChar("url_expired", 255);
        $fldDateFrom = $tab1->addDateTime("date_from");
        $fldDateFrom->nullable = true;
        $fldDateUpto = $tab1->addDateTime("date_to");
        $fldDateUpto->nullable = true;
        $fldTimeFrom = $tab1->addTime("time_from");
        $fldTimeFrom->nullable = true;
        $fldTimeUpto = $tab1->addTime("time_to");
        $fldTimeUpto->nullable = true;
        $tab1->addTinyInteger("dotw");
        $tab1->addBoolean("priority")->setDefaultValue(false);
        $tab1->addInteger("max_scan_tot");
        $tab1->addVarChar("url_finished", 255);
        $tab1->addVarChar("cypher", 255);
        $tab1->addVarChar("url_cypher", 255);
        $tab1->addBoolean("only_mfa")->setDefaultValue(false);
        $tab1->addTinyInteger("device_os")->setDefaultValue(0);
        $tab1->addVarChar("device_lang", 2)->setDefaultValue('');
        $tab1->addVarChar("img_forecolor", 16)->setDefaultValue(QRcode::IMAGE_FOREGROUND);
        $tab1->addVarChar("img_backcolor", 16)->setDefaultValue(QRcode::IMAGE_BACKGROUND);
        $tab1->addDateTime("creation")->setDefaultValueAsCurrentDateTime();
        $tab1->addBoolean("cancelled")->setDefaultValue(false);

        // TABLE LOGS
        $tab2 = $this->native->addTable("logs");
        $tab2->addID("log_id");
        $tab2->addTinyInteger("status");
        $tab2->addVarChar("code", 255);
        $tab2->addInteger("qrcode_id");
        $tab2->addVarChar("user_key", 255)->setDefaultValue('');
        $tab2->addVarChar("event_id", 255)->setDefaultValue('');
        $tab2->addDateTime("creation")->setDefaultValueAsCurrentDateTime();
        $tab2->addBoolean("cancelled")->setDefaultValue(false);

        // TABLE ONE-TIME KEYS
        $tab3 = $this->native->addTable("otkeys");
        $tab3->addID("otk_id");
        $tab3->addInteger("qrcode_id");
        $tab3->addVarChar("code", 255);
        $tab3->addInteger("tally")->setDefaultValue(0);
        $tab3->addDateTime("creation")->setDefaultValueAsCurrentDateTime();

        $this->native->create();
    }

    public function loadQrCodes( $filters = [], $orders = ['creation' => 'DESC'] ) {
        $table = $this->native->qrcodes;
        $where = [];
        if ( key_exists('status', $filters) && $filters['status'] != QrCodeSearchStatus::NONE  ) {
            $where[ $table->disabled->name ] = $filters['status'] == QrCodeSearchStatus::DISABLED;
        }

        if ( !key_exists('cancelled', $filters) ) {
            $where[ $table->cancelled->name ] = false;
        } else {
            $where[ $table->cancelled->name ] = boolval( $filters['cancelled'] );
        }

        return $table->select( $where, $orders );
    }

    public function loadQrCode( $id ) {
        $table = $this->native->qrcodes;
        $field = $table->qrcode_id->name;

        $results = $table->select( [
            $field => $id
        ] );

        if ( is_array($results) ) {
            if ( count($results) == 1 ) {
                return $results[0];
            } else {
                sosidee_log("Database.loadQrCode($id) :: WpTable.select() returned a wrong array length: " . count($results) . " (requested: 1)" );
                return false;
            }
        } else {
            return false;
        }
    }

    private function loadQrCodeByCode( $field, $value ) {
        $table = $this->native->qrcodes;

        $results = $table->select( [
            $field => $value
            ,'cancelled' => false
        ] );

        if ( is_array($results) ) {
            return $results;
        } else {
            return false;
        }
    }

    public function loadQrCodeByKey( $code ) {
        $table = $this->native->qrcodes;
        $field = $table->code->name;

        return $this->loadQrCodeByCode( $field, $code );
    }

    public function loadQrCodeByCypher( $code ) {
        $table = $this->native->qrcodes;
        $field = $table->cypher->name;

        $results = $this->loadQrCodeByCode( $field, $code );
        if ( is_array($results) && count($results) == 1 ) {
            return $results[0];
        } else {
            return false;
        }
    }

    public function saveQrCode( $data, $id = 0 ) {
        $table = $this->native->qrcodes;
        if ( $id > 0 ) {
            return $table->update( $data, [ 'qrcode_id' => $id ] );
        } else {
            return $table->insert( $data );
        }
    }

    public function deleteQrCode( $id ) {
        $table = $this->native->qrcodes;
        return $table->update( [ 'cancelled' => true ], [ 'qrcode_id' => $id ] );
    }

    public function loadQrCodeList() {
        $table = $this->native->qrcodes;

        $filters = [ $table->cancelled->name => false ];
        $orders = ['description'];

        return $table->distinct( ['qrcode_id', 'description'], $filters, $orders );
    }

    public function insertOTKey( $data ) {
        $table = $this->native->otkeys;
        return $table->insert( $data );
    }

    public function loadOTKey( $key, $qrcode_id ) {
        $table = $this->native->otkeys;
        $results = $table->select( [
             'code' => $key
            ,'qrcode_id' => $qrcode_id
        ] );

        if ( is_array($results) && count($results) <= 1 ) {
            return $results[0];
        } else {
            sosidee_log("database.loadOTKey({$qrcode_id}) failed for key={$key}");
            return false;
        }
    }

    public function updateOTKey( $id, $value ) {
        $table = $this->native->otkeys;
        $data = [
            'tally' => $value
        ];
        $filters = [
            'otk_id' => $id
        ];
        return $table->update( $data, $filters );
    }

    public function countActiveLogs( $code ) {
        $table = $this->native->logs;
        $filters = [
             'code' => $code
            ,$table->cancelled->name => false
        ];
        return $table->count( $filters );
    }

    public function loadLogs( $filters = [], $orders = ['creation' => 'DESC'] ) {
        $table = $this->native->logs;

        $where = [];

        if ( array_key_exists('code', $filters) && $filters['code'] != '' ) {
            $where[ $table->code->name ] = $filters['code'];
        }

        if ( array_key_exists('qrcode_id', $filters) && $filters['qrcode_id'] > 0 ) {
            $where[ $table->qrcode_id->name ] = $filters['qrcode_id'];
        }

        if ( array_key_exists('status', $filters) && $filters['status'] != LogStatus::NONE ) {
            $where[ $table->status->name ] = $filters['status'];
        }

        if ( array_key_exists('from', $filters) && $filters['from'] instanceof \DateTime ) {
            $where[ "{$table->creation->name}[>=]" ] = $filters['from'];
        }
        if ( array_key_exists('to', $filters) && $filters['to'] instanceof \DateTime ) {
            $where[ "{$table->creation->name}[<=]" ] = $filters['to'];
        }

        if ( !array_key_exists('cancelled', $filters) ) {
            $where[ $table->cancelled->name ] = false;
        } else {
            $where[ $table->cancelled->name ] = boolval( $filters['cancelled'] );
        }

        return $table->select( $where, $orders );
    }

    private function getLogCountByEventId( $id ) {
        $table = $this->native->logs;

        $results = $table->select( [
             $table->event_id->name => $id
            ,$table->cancelled->name => false
        ] );

        if ( is_array($results) ) {
            return count($results);
        } else {
            return false;
        }
    }

    public function saveLog( $data, $id = 0 ) {
        $table = $this->native->logs;
        if ( $id > 0 ) {
            return $table->update( $data, [ 'log_id' => $id ] );
        } else {
            if ( empty($data['event_id']) ) {
                return $table->insert( $data );
            } else {
                $count = $this->getLogCountByEventId( $data['event_id'] );
                if ( $count !== false ) {
                    if ( $count === 0 ) {
                        return $table->insert( $data );
                    } else {
                        return 1; //qr code already inserted
                    }
                } else {
                    return false;
                }
            }

        }
    }

    public function deleteLog( $id ) {
        $table = $this->native->logs;
        return $table->update( [ 'cancelled' => true ], [ 'log_id' => $id ] );
    }

    public function clearLog() {
        $table = $this->native->logs;
        return $table->update( [ 'cancelled' => true ], [ 'cancelled' => false ] );
    }

}