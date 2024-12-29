<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use SOSIDEE_DYNAMIC_QRCODE\SRC\QrCode;

class LogSearch extends Log
{

    private $status;

    private $qid;
    private $log_id;

    protected $chkLang;
    protected $chkGeo;
    protected $chkDesc;

    protected $chkOS;

    public function __construct() {
        parent::__construct( 'logSearch' );

        $this->qid = $this->addSelect('qid', '' );
        $this->qid->cached = true;
        $this->status = $this->addSelect('status', SRC\LogStatus::NONE );
        $this->status->cached = true;

        $this->log_id = $this->addHidden('delete_log_id', 0);

        $this->chkLang = $this->addCheckBox('show_lang');
        $this->chkGeo = $this->addCheckBox('show_geo');
        $this->chkDesc = $this->addCheckBox('show_desc');
        $this->chkOS = $this->addCheckBox('show_os');

    }

    public function showLang() {
        return self::plugin()->isPro && $this->chkLang->value;
    }
    public function showGeo() {
        return self::plugin()->isPro && $this->chkGeo->value;
    }
    public function showDesc() {
        return self::plugin()->isPro && $this->chkDesc->value;
    }
    public function showOS() {
        return self::plugin()->isPro && $this->chkOS->value;
    }

    public function htmlQID() {
        //$options = $this->loadQrCodeList();
        $options = QrCode::loadQrCodeList();
        $this->qid->html( ['options' => $options] );
    }

    public function htmlStatus() {
        $options = SRC\LogStatus::getList('- any -');
        $this->status->html( ['options' => $options] );
    }

    public function htmlLogId() {
        $this->log_id->html();
    }

    public function htmlShowLang() {
        $this->chkLang->html( ['label' => 'Language'] );
    }
    public function htmlShowGeo() {
        $this->chkGeo->html( ['label' => 'Country'] );
    }
    public function htmlShowDesc() {
        $this->chkDesc->html( ['label' => 'Description'] );
    }
    public function htmlShowOS() {
        $this->chkOS->html( ['label' => 'Operative System'] );
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
        parent::htmlButton( 'clear', 'delete ALL logs', DATA\FormButton::STYLE_DANGER, null, "return self.confirm('Do you confirm to delete ALL the logs present in the database?');" );
    }

    public function htmlDownload( $logs, $mfa_enabled ) {
        if ( count($logs) > 0 ) {
            $folder = self::plugin()->getTempFolder();
            if ($folder !== false) {
                $lines = array();
                $headers = $this->getCsvHeader( $logs[0] );
                if ( $mfa_enabled ) {
                    $headers[] = 'My FastAPP User Key';
                }
                $lines[] = $headers;
                for ( $n=0; $n<count($logs); $n++ ) {
                    $log = $logs[$n];
                    $row = [
                         $log->creation_string
                        ,SRC\LogStatus::getDescription( $log->status )
                        ,$log->code
                        ,QrCode::getQID( $log->qrcode_id )
                    ];
                    if ( isset($log->lang_desc) ) {
                        $row[] = $log->lang_desc;
                    }
                    if ( isset($log->country_desc) ) {
                        $row[] = $log->country_desc;
                    }
                    if ( isset($log->qrcode_desc) ) {
                        $row[] = $log->qrcode_desc;
                    }
                    if ( isset($log->os_desc) ) {
                        $row[] = $log->os_desc;
                    }
                    if ( $mfa_enabled ) {
                        $row[] = $log->user_key;
                    }
                    $lines[] = $row;
                }
                $file = 'log_' . uniqid() . '.csv';
                $path = $folder['path'] . "/{$file}";
                if ( $this->saveCSV($path, $lines) ) {
                    $url = $folder['url'] . "/{$file}";
                    $onclick = "javascript:window.open('{$url}', 'sosidee', 'popup=1');";
                } else {
                    $onclick = "alert('" . htmlentities( addslashes("A problem occurred while saving the CSV file."), ENT_NOQUOTES ) . "')";
                }

            } else {
                $onclick = "alert('" . htmlentities( addslashes("A problem occurred."), ENT_NOQUOTES ) . "')";
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

    public function onSubmit() {

        if ( $this->_action == 'search' ) {
            $this->loadLogs();
        } else if ( $this->_action == 'delete' ) {

            $id = intval( $this->log_id->value );

            if ( $id > 0 ) {
                $result = self::database()->deleteLog( $id );
                if ( $result !== false ) {
                    self::msgOk( "Log data have been deleted." );
                    $this->loadLogs();
                } else {
                    self::msgErr( "A problem occurred." );
                }
            } else {
                self::msgErr( "You can't delete data already deleted." );
            }

        } else if ( $this->_action == 'clear' ) {

            $result = self::database()->clearLog();
            if ( $result !== false ) {
                self::msgOk( "All logs have been deleted." );
                $this->loadLogs();
            } else {
                self::msgErr( "A problem occurred." );
            }

        }
    }

    public function loadLogs() {

        $filters = [
             'qrcode_id' => intval( $this->qid->value )
            ,'status' => $this->status->value
        ];

        parent::load( $filters );

        for ( $n=0; $n<count($this->logs); $n++ ) {
            $this->logs[$n]->creation_string = sosidee_datetime_format( $this->logs[$n]->creation );
            $this->logs[$n]->status_icon = SRC\LogStatus::getStatusIcon( $this->logs[$n]->status );
        }

        if ( count($this->logs)>0 ) {
            if ( !self::plugin()->isPro ) {
                $features = [];
                if ( $this->chkGeo->value ) {
                    $features[] = 'Country';
                }
                if ( $this->chkLang->value ) {
                    $features[] = 'Language';
                }
                if ( $this->chkDesc->value ) {
                    $features[] = 'QR-Code Description';
                }
                if ( $this->chkOS->value ) {
                    $features[] = 'Operative System';
                }
                $count = count($features);
                if ( $count > 0 ) {
                    $list = $features[0];
                    if ( $count > 1 ) {
                        $allButLast = array_slice($features, 0, -1);
                        $list = implode(', ', $allButLast) . ' and ' .  $features[$count - 1];
                    }
                    $msg = $this->getProMsg($list . ' logs are available only on the PRO version');
                    self::msgWarn($msg);
                }
            }
        }

    }

}