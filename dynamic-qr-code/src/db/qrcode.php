<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\DB;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;
use SOSIDEE_DYNAMIC_QRCODE\SRC\QRcode as QRC;
use SOSIDEE_DYNAMIC_QRCODE\SRC\QrCodeSearchStatus as QRC_SearchStatus;

class QrCode extends Table
{
    public $code = null;
    public $description = null;
    public $disabled = null;
    public $url_redirect = null;
    public $url_inactive = null;
    public $url_expired = null;
    public $url_finished = null;
    public $date_from = null;
    public $date_to = null;
    public $time_from = null;
    public $time_to = null;
    public $dotw = null;
    public $priority = null;
    public $max_scan_tot = null;
    public $cypher = null;
    public $url_cypher = null;

    public $only_mfa = null;
    public $device_os = null;
    public $device_lang = null;
    public $img_forecolor = null;
    public $img_backcolor = null;

    public $quality_level = null;
    public $img_size = null;
    public $img_pad = null;

    public $redir_key_enabled = null;
    public $redir_key_name = null;

    public $count_states = null;

    public function __construct() {
        parent::__construct('qrcodes');
    }

    public function defineSchema() {

        $this->id = $this->addID("qrcode_id");

        $this->code = $this->addVarChar("code", 255);
        $this->description = $this->addVarChar("description", 255);
        $this->disabled = $this->addBoolean("disabled")->setDefaultValue(false);
        $this->url_redirect = $this->addVarChar("url_redirect", 255);
        $this->url_inactive = $this->addVarChar("url_inactive", 255);
        $this->url_expired = $this->addVarChar("url_expired", 255);
        $this->url_finished = $this->addVarChar("url_finished", 255);
        $this->date_from = $this->addDateTime("date_from")->setNullable();
        $this->date_to = $this->addDateTime("date_to")->setNullable();
        $this->time_from = $this->addTime("time_from")->setNullable();
        $this->time_to = $this->addTime("time_to")->setNullable();
        $this->dotw = $this->addTinyInteger("dotw");
        $this->priority = $this->addBoolean("priority")->setDefaultValue(false);
        $this->max_scan_tot = $this->addInteger("max_scan_tot");
        $this->cypher = $this->addVarChar("cypher", 255);
        $this->url_cypher = $this->addVarChar("url_cypher", 255);
        $this->only_mfa = $this->addBoolean("only_mfa")->setDefaultValue(false);
        $this->device_os = $this->addTinyInteger("device_os")->setDefaultValue(0);
        $this->device_lang = $this->addVarChar("device_lang", 2);
        $this->img_forecolor = $this->addVarChar("img_forecolor", 16)->setDefaultValue(QRC::IMAGE_FOREGROUND);
        $this->img_backcolor = $this->addVarChar("img_backcolor", 16)->setDefaultValue(QRC::IMAGE_BACKGROUND);
        $this->quality_level = $this->addTinyInteger("quality_level")->setDefaultValue(0);
        $this->img_size = $this->addSmallInteger("img_size")->setDefaultValue(0);
        $this->img_pad = $this->addTinyInteger("img_pad")->setDefaultValue(-1);
        $this->redir_key_enabled = $this->addBoolean("redir_key_enabled")->setDefaultValue(false);
        $this->redir_key_name = $this->addVarChar("redir_key_name", 255);
        $this->count_states = $this->addVarChar("count_states", 64)->setNullable(false)->setDefaultValue('[]');

        parent::defineSchema();
    }

    public function load( $id ) {
        $filters = [ $this->id->name => $id ];
        $results = $this->select( $filters );
        if ( is_array($results) ) {
            if ( count($results) == 1 ) {
                return $results[0];
            } else {
                sosidee_log("DB\QrCode::load($id) - Table.select() returned a wrong array length: " . count($results) . " (expected: 1)" );
                return false;
            }
        } else {
            return false;
        }
    }

    public function loadList( $filters = [], $orders = [], $include_cancelled = false ) {

        $status = QRC_SearchStatus::QUERY_NAME;

        $where = [];
        foreach ( $filters as $key => $value ) {
            if ( $key == $status ) {
                if ( $value != QRC_SearchStatus::NONE ) {
                    $where[$this->disabled->name] = ($value == QRC_SearchStatus::DISABLED);
                }
            } elseif ( $key == $this->cancelled->name ) {
                $where[ $key ] = boolval( $value );
            } else {
                $where[$key] = $value;
            }
        }

        if ( !$include_cancelled && !key_exists( $this->cancelled->name, $where ) ) {
            $where[ $this->cancelled->name ] = false;
        }

        if ( empty($orders) ) {
            $orders = [ $this->creation->name => 'DESC' ];
        }

        return $this->select( $where, $orders );
    }

    private function loadByCol( $column, $value ) {
        $results = $this->select( [
            $column => $value
            ,$this->cancelled->name => false
        ] );

        if ( is_array($results) ) {
            return $results;
        } else {
            return false;
        }
    }

    public function loadByKey( $value ) {
        return $this->loadByCol( $this->code->name, $value );
    }

    public function loadByCypher( $value ) {
        $results = $this->loadByCol( $this->cypher->name, $value );
        if ( is_array($results) && count($results) == 1 ) {
            return $results[0];
        } else {
            return false;
        }
    }

    public function save( $data, $id = 0 ) {
        if ( $id > 0 ) {
            return $this->update( $data, [ $this->id->name => $id ] );
        } else {
            return $this->insert( $data );
        }
    }

    public function delete( $id ) {
        return $this->update( [ $this->cancelled->name => true ], [ $this->id->name => $id ] );
    }

    public function loadKeyList() {
        return $this->distinct(  $this->code->name, [ $this->cancelled->name => false ], [ $this->code->name ] );
    }

    public function loadFields( $fields ) {
        return $this->selectFields( $fields, [ $this->cancelled->name => false ], [ $this->code->name ] );
    }

    public function upsert( $data, $mode = UpsertMode::ON_ID_DUPLICATE_SKIP ) {
        $id = $data['qrcode_id'] ?? 0;
        unset($data['qrcode_id']);
        if ( $id > 0 ) {
            $present = $this->load( $id );
            if ( $present === false ) {
                $res = $this->save( $data ); //insert
                if ( $res !== false ) {
                    return 'inserted';
                } else {
                    return 'error';
                }
            }
            $cancelled = $present->cancelled;
            if ( $cancelled ) {
                $res = $this->save( $data ); //insert
                if ( $res !== false ) {
                    return 'inserted';
                } else {
                    return 'error';
                }
            }
            if ( $mode == UpsertMode::ON_ID_DUPLICATE_SKIP ) {
                return 'skipped';
            } elseif ( $mode == UpsertMode::ON_ID_DUPLICATE_UPDATE ) {
                $res = $this->save( $data, $id ); //update
                if ( $res !== false ) {
                    return 'updated';
                } else {
                    return 'error';
                }
            } elseif ( $mode == UpsertMode::ON_ID_DUPLICATE_INSERT_NEW ) {
                $res = $this->save( $data ); //insert
                if ( $res !== false ) {
                    return 'inserted';
                } else {
                    return 'error';
                }
            }
            return 'error';
        } else {
            $res = $this->save( $data ); //insert
            if ( $res !== false ) {
                return 'inserted';
            } else {
                return 'error';
            }
        }

    }

}