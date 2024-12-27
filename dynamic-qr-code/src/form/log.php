<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SRC\QrCode;

class Log extends Base
{
    protected $dtFrom;
    protected $dtTo;

    public $logs;

    public function __construct($name) {
        parent::__construct( $name, [$this, 'onSubmit'] );

        $this->dtFrom = $this->addDatePicker('from', 'now');
        $this->dtFrom->cached = true;
        $this->dtTo = $this->addDatePicker('up_to', 'now');
        $this->dtTo->cached = true;

        $this->logs = [];
    }

    public function htmlFrom() {
        $this->dtFrom->html();
    }

    public function htmlTo() {
        $this->dtTo->html();
    }


    protected function initialize() {
        if ( !$this->_posted ) {
            if ( $this->_cache_timestamp instanceof \DateTime ) {
                $now = sosidee_current_datetime();
                if ( $this->_cache_timestamp->format('Ymd') != $now->format('Ymd') ) {
                    $this->dtFrom->value = $now->format('Y-m-d');
                    $this->dtTo->value = $now->format('Y-m-d');
                }
            }
        }
    }

    protected function load( $filters, $orders = [ 'creation' => 'DESC' ] ) {
        $this->logs = [];

        $filters['from'] = $this->dtFrom->getValueAsDate();
        $filters['to'] = $this->dtTo->getValueAsDate(true);

        $results = self::database()->loadLogs( $filters, $orders );
        if ( is_array($results) ) {
            if ( count($results) == 0 ) {
                if ( $this->_posted ) {
                    self::msgInfo( 'No results match the search.' );
                } else {
                    self::msgInfo( "There's no data in the database." );
                }
            } else {
                $plugin = self::plugin();
                if ( $plugin->isPro ) {
                    $plugin->addon->log->load(self::database(), $results);
                } else {
                    for ( $n=0; $n<count($results); $n++ ) {
                        $result = &$results[$n];
                        if ( isset($result->country) ) {
                            unset($result->country);
                        }
                        if ( isset($result->lang) ) {
                            unset($result->lang);
                        }
                        if ( isset($result->op_sys) ) {
                            unset($result->op_sys);
                        }
                        unset($result);
                    }
                }
            }
            $this->logs = $results;
            if ( $this->_posted ) {
                $this->saveCache();
            }
        } else {
            self::msgErr( 'A problem occurred.' );
        }

    }

    /*
    protected function loadQrCodeList( $caption = false, $include_cancelled = false ) {
        return QrCode::loadQrCodeList( $caption, $include_cancelled );
        //return self::plugin()->loadQrCodeList( $caption, $include_cancelled );
    }
    */

    public function saveCSV( $path, $lines, $parameters = [] ) {
        $ret = false;

        $delimiter = ',';
        $enclosure = '"';
        $escape = "\\";
        $out_charset = 'Windows-1252';
        $in_charset = 'UTF-8';

        extract($parameters, EXTR_IF_EXISTS);

        if ( ( $handle = fopen($path, "w") ) !== false ) {
            $ret = true;
            for ( $i=0; $i<count($lines); $i++ ) {
                $data = $lines[$i];
                for ( $j=0; $j<count($data); $j++ ) {
                    $data[$j] = iconv( $in_charset, "$out_charset//TRANSLIT", $data[$j] );
                }
                $ret = (fputcsv($handle, $data, $delimiter, $enclosure, $escape) !== false) && $ret;
            }
            fclose($handle);
        }

        return $ret;
    }

    protected function getCsvHeader( $item ) {
        $rets = [
             'Date'
            ,'Status'
            ,'Code'
            ,'Qid'
        ];
        if ( isset($item->lang_desc) ) {
            $rets[] = 'Language';
        }
        if ( isset($item->country_desc) ) {
            $rets[] = 'Country';
        }
        if ( isset($item->qrcode_desc) ) {
            $rets[] = 'Description';
        }
        if ( isset($item->os_desc) ) {
            $rets[] = 'Operative System';
        }
        return $rets;
    }

}