<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use SOSIDEE_DYNAMIC_QRCODE\SRC\QrCode;
use SOSIDEE_DYNAMIC_QRCODE\SRC\DB as DB;
use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;

class LogSearch extends Base
{

    private $status;

    private $qid;
    private $log_id;

    protected $chkLang;
    protected $selGeo;
    protected $chkDesc;

    protected $chkOS;

    protected $dtFrom;
    protected $dtTo;

    public $logs;


    public function __construct( $name = '', $callback = null ) {
        if ( empty( $name ) ) {
            $name = 'logSearch';
        }
        if ( is_null( $callback ) ) {
            $callback = [$this, 'onSubmit'];
        }
        parent::__construct( $name, $callback );

        $this->logs = [];

        $this->qid = $this->addSelect('qid', 0 );
        $this->qid->cached = true;
        $this->status = $this->addSelect('status', SRC\LogStatus::NONE );
        $this->status->cached = true;

        $this->log_id = $this->addHidden('delete_log_id', 0);

        $this->chkLang = null;
        $this->selGeo = null;
        $this->chkDesc = null;
        $this->chkOS = null;

        $this->dtFrom = $this->addDatePicker('from', 'now');
        $this->dtFrom->cached = true;
        $this->dtTo = $this->addDatePicker('up_to', 'now');
        $this->dtTo->cached = true;

        add_action('plugins_loaded', function() {
            self::plugin()->doAction('_form_log-search_constructor', $this);
        }, 12); // deve avvenire dopo che il plugin pro ha aggiunto filtri e azioni (priority=11)

    }

    protected function initialize() {
        // reimposta i valori se le date sono state salvate nella cache prima di oggi
        if ( !$this->isPosted() ) {
            if ( $this->_cache_timestamp instanceof \DateTimeInterface ) {
                $now = sosidee_current_datetime();
                $date = $now->format('Ymd'); // ignora la cache se non odierna
                if ( $this->_cache_timestamp->format('Ymd') != $date ) {
                    $this->dtFrom->value = $date;
                    $this->dtTo->value = $date;
                }
            }
        }
    }

    public function isCodeShared() {
        $option = self::plugin()->config->sharedCodeEnabled;
        $option->load();
        return $option->value;
    }

    public function htmlFrom() {
        $this->dtFrom->html();
    }

    public function htmlTo() {
        $this->dtTo->html();
    }

    public function htmlQID() {
        $options = QrCode::loadQrCodeList();
        $options = self::plugin()->applyFilter('_form_log-search_qc-selector', $options);
        $this->qid->html( ['options' => $options] );
    }

    public function htmlStatus() {
        $options = SRC\LogStatus::getList('- any -');
        $this->status->html( ['options' => $options] );
    }

    public function htmlLogId() {
        $this->log_id->html();
    }

    public function htmlCancel( $id ) {
        parent::htmlButton( 'delete', 'delete', DATA\FormButton::STYLE_DANGER, null, "return jsSosDqcDeleteLog('{$this->log_id->id}', $id);" );
    }

    public function htmlCancelAll() {
        parent::htmlButton( 'clear', 'delete ALL logs', DATA\FormButton::STYLE_DANGER, null, "return self.confirm('Do you confirm to delete ALL the logs present in the database?');" );
    }

    protected function getCsvHeader() {
        $headers = [
             'Date'
            ,'Status'
            ,'Key'
            ,'Q-ID'
        ];
        return self::plugin()->applyFilter('_form_log-search_qc_csv-header', $headers, $this);
    }

    public function htmlDownload() {
        $logs = $this->logs;
        if ( !empty($logs) ) {
            $path = SRC\IO::getLogPath();
            if ( !empty($path) ) {
                $lines = [ $this->getCsvHeader() ];
                foreach ( $logs as $log ) {
                    $lines[] = [
                         $log->creation_string
                        ,SRC\LogStatus::getDescription( $log->status )
                        ,$log->code
                        ,QrCode::getQID( $log->qrcode_id )
                    ];
                }
                $lines = self::plugin()->applyFilter('_form_log-search_qc_csv-body', $lines, $logs, $this);

                $file = 'log_' . uniqid() . '.csv';
                $path .= $file;
                if ( $this->saveCSV($path, $lines) ) {
                    $url = SRC\IO::getLogUrl() . $file;
                    //$onclick = "javascript:window.open('{$url}', 'sosidee', 'popup=1');";
                    $onclick = null;
                } else {
                    $url = null;
                    $onclick = "alert('" . htmlentities( addslashes("A problem occurred while saving the CSV file."), ENT_NOQUOTES ) . "')";
                }
            } else {
                $url = null;
                $onclick = "alert('" . htmlentities( addslashes("A problem occurred."), ENT_NOQUOTES ) . "')";
            }

            DATA\FormTag::html( 'a', [
                 'href' => $url
                ,'download' => true
                ,'content' => 'download'
                ,'onclick' => $onclick
                ,'class' => 'button button-success'
                ,'style' => 'min-width: 90px;' . DATA\FormButton::STYLE_SUCCESS
            ] );

        }

    }

    public function onSubmit() {

        if ( $this->_action == 'search' ) {

            $this->loadLogs();

        } elseif ( $this->_action == 'delete' ) {

            $id = intval( $this->log_id->value );
            $this->log_id->value = 0;

            if ( $id > 0 ) {
                $result = self::database()->logs->cancel( $id );
                if ( $result !== false ) {
                    self::msgOk( "Log entry has been deleted." );
                    $this->loadLogs();
                } else {
                    self::msgErr( "A problem occurred." );
                }
            } else {
                self::msgErr( "There are no logs to delete." );
            }

        } elseif ( $this->_action == 'clear' ) {

            $result = self::database()->logs->clear();
            if ( $result !== false ) {
                self::msgOk( "All logs have been deleted." );
            } else {
                self::msgErr( "A problem occurred." );
            }
            $this->loadLogs();

        }
    }

    public function loadLogs() {

        $this->logs = [];

        $table = self::database()->logs;

        $filters = [];
        $quid = intval( $this->qid->value );
        if ( $quid > 0 ) {
            $filters[$table->qrcode_id->name] = $quid;
        }
        if ( $this->status->value !== SRC\LogStatus::NONE ) {
            $filters[SRC\LogStatus::QUERY_NAME] = $this->status->value;
        }

        if ( !empty($this->dtFrom->value) ) {
            $filters[DB\Log::QUERY_DT_FROM] = $this->dtFrom->getValueAsDate();
        }
        if ( !empty($this->dtTo->value) ) {
            $filters[DB\Log::QUERY_DT_TO] = $this->dtTo->getValueAsDate(true);
        }

        $orders = [ $table->creation->name => 'DESC' ];

        $results = $table->loadList( $filters, $orders );

        if ( !is_array($results) ) {
            self::msgErr( 'A problem occurred while loading logs.' );
            return;
        }

        if ( empty($results) ) {
            self::msgInfo( 'No results match the search.' );
            return;
        }

        $this->logs = $results;

        $code_shared = $this->isCodeShared();
        foreach ( $this->logs as $log ) {
            $log->creation_string = sosidee_datetime_format( $log->creation );
            $log->status_icon = SRC\LogStatus::getStatusIcon( $log->status );
            if ( $code_shared ) {
                $log->quid = SRC\QrCode::getQID( $log->qrcode_id );
            }
        }
        $this->logs = self::plugin()->applyFilter('_form_log-search_load_data', $this->logs, $this, SRC\OS::class);

        $this->saveCache();

    }

    private function saveCSV( $path, $lines, $parameters = [] ) {
        return SOS_WP\Filesystem::saveCSV( $path, $lines, $parameters );
    }

    public function getDataDisplayTable() {
        $table = [];
        $config = self::plugin()->config;
        $config->load(); // load current configuration
        $code_shared = $config->sharedCodeEnabled->value;
        //$mfa_enabled = false; // $config->mfaEnabled->value;

        $attr_std = ['class' => 'bordered middled centered'];

        //$table['test'] = DATA\Display::getColumn( [ $attr_std, function() { echo '#'; } ], [ $attr_std, function() { echo '#'; } ] );

        $table['creation'] = [
            'head' => DATA\Display::getCell( $attr_std, function() {
                    echo 'Date';
                } )
            ,'body' => DATA\Display::getCell( $attr_std, function( $item ) {
                        echo esc_html( $item->creation_string );
                })
        ];

        $table['status'] = [
            'head' => [
                 'attrs' => $attr_std
                ,'callback' => function() {
                    echo 'Status';
                }
            ]
            ,'body' => [
                'attrs' => $attr_std
                ,'callback' => function( $item ) {
                    echo wp_kses( $item->status_icon, sosidee_allowed_html() );
                }
            ]
        ];

        $table['code'] = [
            'head' => [
                 'attrs' => $attr_std
                ,'callback' => function() {
                    echo 'Key';
                }
            ]
            ,'body' => [
                'attrs' => $attr_std
                ,'callback' => function( $item ) {
                    echo esc_html( $item->code );
                }
            ]
        ];

        if ( $code_shared ) {
            $table['quid'] = [
                'head' => [
                     'attrs' => $attr_std
                    ,'callback' => function() {
                        echo 'Q-ID';
                    }
                ]
                ,'body' => [
                     'attrs' => $attr_std
                    ,'callback' => function( $item ) {
                        echo esc_html( $item->quid );
                    }
                ]
            ];
        }

        /*
        if ( $mfa_enabled ) {
            $table['mfa'] = [
                'head' => [
                    'attrs' => $attr_std
                    ,'callback' => function() {
                        echo 'My FastAPP User Key';
                    }
                ]
                ,'body' => [
                    'attrs' => $attr_std
                    ,'callback' => function( $item ) {
                        echo esc_html( $item->user_key );
                    }
                ]
            ];
        }
        */

        $table['action'] = [
            'head' => [
                 'attrs' => $attr_std
                ,'callback' => function( $context ) {
                    $form = $context['form'];
                    $logs = $context['logs'];
                    $form->htmlDownload($logs);
                }
            ]
            ,'body' => [
                 'attrs' => $attr_std
                ,'callback' => function( $item, $context ) {
                    $form = $context['form'];
                    $form->htmlCancel( $item->log_id );
                }
            ]
        ];

        return self::plugin()->applyFilter('_form_log-search_display_data', $table, $this);
    }


}