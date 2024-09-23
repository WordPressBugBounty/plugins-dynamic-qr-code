<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;

class LogSearch extends Base
{
    private $qid;
    private $status;
    private $dtFrom;
    private $dtTo;

    public $logs;

    private $log_id;

    public function __construct() {
        parent::__construct( 'logSearch', [$this, 'onSubmit'] );

        $this->qid = $this->addSelect('qid', '' );
        $this->qid->cached = true;
        $this->status = $this->addSelect('status', SRC\LogStatus::NONE );
        $this->status->cached = true;
        $this->dtFrom = $this->addDatePicker('from', 'now');
        $this->dtFrom->cached = true;
        $this->dtTo = $this->addDatePicker('up_to', 'now');
        $this->dtTo->cached = true;

        $this->log_id = $this->addHidden('delete_log_id', 0);

        $this->logs = [];
    }

    public function htmlQID() {
        $options = $this->_plugin->loadQrCodeList('- any -');
        $this->qid->html( ['options' => $options] );
    }

    public function htmlStatus() {
        $options = SRC\LogStatus::getList('- any -');
        $this->status->html( ['options' => $options] );
    }

    public function htmlFrom() {
        $this->dtFrom->html();
    }
    public function htmlTo() {
        $this->dtTo->html();
    }

    public function htmlLogId() {
        $this->log_id->html();
    }

    public function htmlCancel( $id ) {
        echo <<<EOD
<script type="application/javascript">
function jsSosDqcDeleteLog( v ) {
    let field = self.document.getElementById( '{$this->log_id->id}' );
    if ( self.confirm("Do you confirm to delete this log entry?") ) {
        field.value = v;
    } else {
        field.value = 0;
    }
    return field.value > 0;
}
</script>
EOD;
        parent::htmlButton( 'delete', 'delete', DATA\FormButton::STYLE_DANGER, null, "return jsSosDqcDeleteLog($id);" );
    }

    public function htmlCancelAll() {

        parent::htmlButton( 'clear', 'delete all logs', DATA\FormButton::STYLE_DANGER, null, "return self.confirm('Do you confirm to delete ALL the logs?');" );
    }

    public function htmlDownload($logs, $code_shared, $mfa_enabled) {

        if ( count($logs) > 0 ) {
            $folder = $this->_plugin->getTempFolder();
            if ($folder !== false) {
                $lines = array();
                $headers =[
                     'Date'
                    ,'Code'
                    ,'Status'
                ];
                if ($code_shared) {
                    $headers[] = 'Qid';
                }
                if ($mfa_enabled) {
                    $headers[] = 'My FastAPP User Key';
                }
                $lines[] = $headers;
                for ($n=0; $n<count($logs); $n++) {
                    $log = $logs[$n];
                    $row = [
                         $log->creation_string
                        ,$log->code
                        ,SRC\LogStatus::getDescription( $log->status )
                    ];
                    if ($code_shared) {
                        $row[] = $log->qrcode_id;
                    }
                    if ($mfa_enabled) {
                        $row[] = $log->user_key;
                    }
                    $lines[] = $row;
                }
                $file = 'log_' . uniqid() . '.csv';
                $path = $folder['basedir'] . "/{$file}";
                if ( $this->saveCSV($path, $lines) ) {
                    $url = $folder['baseurl'] . "/{$file}";
                    $onclick = "javascript:window.open('{$url}', 'sosidee', 'popup=1');";
                } else {
                    $onclick = "alert('" . htmlentities( addslashes("A problem occurred while saving the CSV file."), ENT_NOQUOTES ) . "')";
                }

            } else {
                $onclick = "alert('" . htmlentities( addslashes('A problem occurred.'), ENT_NOQUOTES ) . "')";
            }

            DATA\FormTag::html( 'input', [
                'type' => 'button'
                ,'value' => 'download'
                ,'onclick' => $onclick
                ,'class' => 'button button-primary'
                ,'style' => 'color: #ffffff; background-color: #28a745; border-color: #28a745;'
            ] );

        }

    }

    /*
    private function loadQrCodeList() {
        $ret = [ 0 => '- any -' ];

        $results = $this->_database->loadQrCodeList();

        if ( is_array($results) ) {
            if ( count($results) > 0 ) {
                for ( $n=0; $n<count($results); $n++ ) {
                    $ret[ $results[$n]->qrcode_id ] = $results[$n]->description;
                }
            }
        } else {
            self::msgErr( 'A problem occurred while reading the qr code list from the database.' );
        }
        return $ret;
    }
    */

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

    public function onSubmit() {

        if ( $this->_action == 'search' ) {
            $this->loadLogs();
        } else if ( $this->_action == 'delete' ) {

            $id = intval( $this->log_id->value );

            if ( $id > 0 ) {
                $result = $this->_database->deleteLog( $id );
                if ( $result !== false ) {
                    self::msgOk( 'Log data have been deleted.' );
                    $this->loadLogs();
                } else {
                    self::msgErr( 'A problem occurred.' );
                }
            } else {
                self::msgErr( "You can't delete data already deleted." );
            }


        } else if ( $this->_action == 'clear' ) {

            $result = $this->_database->clearLog();
            if ( $result !== false ) {
                self::msgOk( 'All logs have been deleted.' );
                $this->loadLogs();
            } else {
                self::msgErr( 'A problem occurred.' );
            }

        }
    }

    public function loadLogs() {
        $this->logs = [];

        $qid = intval( $this->qid->value );

        $filters = [
             'qrcode_id' => $qid
            ,'status' => $this->status->value
            ,'from' => $this->dtFrom->getValueAsDate()
            ,'to' => $this->dtTo->getValueAsDate( true )
        ];

        $orders = [ 'creation' => 'DESC' ];

        $results = $this->_database->loadLogs( $filters, $orders );

        if ( is_array($results) ) {
            if ( count($results) > 0 ) {
                for ( $n=0; $n<count($results); $n++ ) {
                    $results[$n]->creation_string = sosidee_datetime_format( $results[$n]->creation );
                    $results[$n]->status_icon = SRC\LogStatus::getStatusIcon( $results[$n]->status );
                }
                if ( $this->_posted ) {
                    $this->saveCache();
                }
            } else {
                if ( $this->_posted ) {
                    self::msgInfo( 'No results match the search.' );
                } else {
                    self::msgInfo( "There's no data in the database." );
                }
            }
            $this->logs = $results;
        } else {
            self::msgErr( 'A problem occurred.' );
        }
    }



    public function saveCSV($path, $lines, $parameters = array()) {
        $ret = false;

        $delimiter = ',';
        $enclosure = '"';
        $escape = "\\";
        $out_charset = 'Windows-1252';
        $in_charset = 'UTF-8';

        extract($parameters, EXTR_IF_EXISTS);

        if ( ($handle = fopen($path, "w")) !== false ) {
            $ret = true;
            for ($i=0; $i<count($lines); $i++) {
                $data = $lines[$i];
                for ($j=0; $j<count($data); $j++) {
                    $data[$j] = iconv( $in_charset, "$out_charset//TRANSLIT", $data[$j] );
                }
                $ret = (fputcsv($handle, $data, $delimiter, $enclosure, $escape) !== false) && $ret;
            }
            fclose($handle);
        }

        return $ret;
    }


}