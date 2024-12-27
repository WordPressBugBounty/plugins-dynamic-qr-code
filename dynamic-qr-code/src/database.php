<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

class Database
{
    private $native;

    public function __construct() {

        $this->native = new DATA\WpDatabase('sos_dqc_');

        // TABLE QR-CODES
        $tabQC = $this->native->addTable("qrcodes");
        $tabQC->addID("qrcode_id");
        $tabQC->addBoolean("disabled")->setDefaultValue(false);
        $tabQC->addVarChar("code", 255);
        $tabQC->addVarChar("description", 255);
        $tabQC->addVarChar("url_redirect", 255);
        $tabQC->addVarChar("url_inactive", 255);
        $tabQC->addVarChar("url_expired", 255);
        $fldDateFrom = $tabQC->addDateTime("date_from");
        $fldDateFrom->nullable = true;
        $fldDateUpto = $tabQC->addDateTime("date_to");
        $fldDateUpto->nullable = true;
        $fldTimeFrom = $tabQC->addTime("time_from");
        $fldTimeFrom->nullable = true;
        $fldTimeUpto = $tabQC->addTime("time_to");
        $fldTimeUpto->nullable = true;
        $tabQC->addTinyInteger("dotw");
        $tabQC->addBoolean("priority")->setDefaultValue(false);
        $tabQC->addInteger("max_scan_tot");
        $tabQC->addVarChar("url_finished", 255);
        $tabQC->addVarChar("cypher", 255);
        $tabQC->addVarChar("url_cypher", 255);
        $tabQC->addBoolean("only_mfa")->setDefaultValue(false);
        $tabQC->addTinyInteger("device_os")->setDefaultValue(0);
        $tabQC->addVarChar("device_lang", 2)->setDefaultValue('');
        $tabQC->addVarChar("img_forecolor", 16)->setDefaultValue(QRcode::IMAGE_FOREGROUND);
        $tabQC->addVarChar("img_backcolor", 16)->setDefaultValue(QRcode::IMAGE_BACKGROUND);
        $tabQC->addDateTime("creation")->setDefaultValueAsCurrentDateTime();
        $tabQC->addBoolean("cancelled")->setDefaultValue(false);

        // TABLE LOGS
        $tabLog = $this->native->addTable("logs");
        $tabLog->addID("log_id");
        $tabLog->addTinyInteger("status");
        $tabLog->addVarChar("code", 255);
        $tabLog->addInteger("qrcode_id");
        $tabLog->addVarChar("user_key", 255)->setDefaultValue('');
        $tabLog->addVarChar("event_id", 255)->setDefaultValue('');
        $tabLog->addTinyInteger("op_sys")->setDefaultValue(OS::UNKNOWN); //operative system
        $tabLog->addTinyInteger("dev_type")->setDefaultValue(DeviceType::UNKNOWN);
        $tabLog->addVarChar("lang", 2)->setDefaultValue('');
        $tabLog->addVarChar("country", 2)->setDefaultValue('');
        $tabLog->addVarChar("region", 255)->setDefaultValue('');
        $tabLog->addVarChar("city", 255)->setDefaultValue('');
        $tabLog->addDateTime("creation")->setDefaultValueAsCurrentDateTime();
        $tabLog->addBoolean("cancelled")->setDefaultValue(false);

        // TABLE ONE-TIME KEYS
        $tabOtk = $this->native->addTable("otkeys");
        $tabOtk->addID("otk_id");
        $tabOtk->addInteger("qrcode_id");
        $tabOtk->addVarChar("code", 255);
        $tabOtk->addInteger("tally")->setDefaultValue(0);
        $tabOtk->addDateTime("creation")->setDefaultValueAsCurrentDateTime();

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

    private function loadQrCodeByField($field, $value ) {
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

        return $this->loadQrCodeByField( $field, $code );
    }

    public function loadQrCodeByCypher( $code ) {
        $table = $this->native->qrcodes;
        $field = $table->cypher->name;

        $results = $this->loadQrCodeByField( $field, $code );
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

    public function loadQrCodeList( $include_cancelled = false ) {
        $table = $this->native->qrcodes;

        if ( !$include_cancelled ) {
            $filters = [ $table->cancelled->name => false ];
        } else {
            $filters = [];
        }
        $orders = ['description'];

        return $table->select( $filters, $orders );
    }

    public function loadQrKeyList() {
        $table = $this->native->qrcodes;

        $filters = [
            $table->cancelled->name => false
        ];
        $orders = ['code'];

        return $table->distinct( ['code'], $filters, $orders );
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

    public function countActiveLogsById( $qrcode_id ) {
        $table = $this->native->logs;
        $filters = [
             'qrcode_id' => $qrcode_id
            ,$table->cancelled->name => false
        ];
        return $table->count( $filters );
    }

    public function countActiveLogsByCode( $qrcode_code ) {
        $table = $this->native->logs;
        $filters = [
             'code' => $qrcode_code
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
            if ( !is_array($filters['status']) || count($filters['status']) > 0) {
                $where[ $table->status->name ] = $filters['status'];
            }
        }

        if ( array_key_exists('op_sys', $filters) && $filters['op_sys'] != OS::UNKNOWN ) {
            if ( !is_array($filters['op_sys']) || count($filters['op_sys']) > 0) {
                $where[ $table->op_sys->name ] = $filters['op_sys'];
            }
        }

        if ( array_key_exists('dev_type', $filters) && $filters['dev_type'] != DeviceType::UNKNOWN ) {
            $where[ $table->dev_type->name ] = $filters['dev_type'];
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