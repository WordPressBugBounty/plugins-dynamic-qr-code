<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;

class QrCodeSearch extends Base
{
    public $status;
    public $qrcodes;
    public $importFile;
    public $importMode;
    public $exportUrl;

    public function __construct( $name = '', $callback = null ) {
        if ( empty( $name ) ) {
            $name = 'qrcodeSearch';
        }
        if ( is_null( $callback ) ) {
            $callback = [$this, 'onSubmit'];
        }
        parent::__construct( $name, $callback );

        $this->status = $this->addSelect('status', SRC\QrCodeSearchStatus::ENABLED);
        $this->status->cached = true;

        $this->qrcodes = [];

        $this->exportUrl = null;
        $this->importFile = null;
        $this->importMode = null;

        add_action('plugins_loaded', function() {
            self::plugin()->doAction('_form_qrcode-list_constructor', $this );
        }, 12); // deve avvenire dopo che il plugin pro ha aggiunto filtri e azioni [TAddon::mainPluginChecked() ha priorità 11]

    }

    public function getAction() {
        return $this->_action;
    }

    public function htmlStatus() {
        $options = SRC\QrCodeSearchStatus::getList('- any -');
        $this->status->html( ['options' => $options] );
    }

    public function htmlButtonLink( $id = 0 ) {
        self::plugin()->formEditQrCode->htmlButtonLink( $id );
    }

    public function htmlExImport() {
        echo '<br>';
        $controls = self::plugin()->applyFilter('_form_qrcode-list_ctrl_ex-im-port', false, $this);
        if ( $controls === false ) {
            echo '<p>';
            echo self::plugin()->pro();
            echo ' Import and export QR-Code list is available in the PRO version';
            echo '</p>';
        } else {
            if ( is_array($controls) && isset($controls['export']) && isset($controls['import'])
                    && isset($controls['file']) && isset($controls['mode']) ) {
                    $btnExport = $controls['export'];
                    $btnImport = $controls['import'];
                    $ctrlFile = $controls['file'];
                    $ctrlMode = $controls['mode'];
                ?>
                <fieldset class="panel">
                    <legend>Import / Export <span id="icoToggleExIm" class="dashicons dashicons-arrow-down-alt2" style="cursor: pointer;" title="show"></span></legend>
                <table id="tabExIm" class="form-table sqc" role="presentation" style="display: none;">
                    <tbody>
                    <tr>
                        <td style="text-align: left; vertical-align: bottom;">
                            <?php
                            echo wp_kses($ctrlFile, sosidee_allowed_html() );
                            echo '<br><br>';
                            echo wp_kses($ctrlMode, sosidee_allowed_html() );
                            echo '<br><br>';
                            echo wp_kses($btnImport, sosidee_allowed_html() );
                            ?>
                        </td>
                        <td style="text-align: right; vertical-align: bottom;"><?php echo wp_kses($btnExport, sosidee_allowed_html() ); ?></td>
                    </tr>
                   </tbody>
                </table>
                </fieldset>
                <?php

                if ( !empty($this->exportUrl) ) {
                    echo '<p style="text-align: center;">';
                    SOS_WP\HtmlTag::html('a', [
                             'href' => $this->exportUrl
                            ,'download' => true
                            ,'content' => 'click here to download the data file'
                    ]);
                    echo '</p>';
                }

            }
        }
    }



    protected function initialize() {
        if ( !$this->_posted ) {
            $this->loadQrCodes();
        }
    }

    public function onSubmit() {
        $this->loadQrCodes();

        if ( $this->_action != 'search' ) {
            $result = self::plugin()->applyFilter('_form_qrcode-list_on-submit', false, $this);
        } else {
            $result = false;
        }

        if ( is_array($result) ) {
            $success = $result['success'] ?? false;
            $action = $result['action'] ?? '';
            if ( $success === true ) {
                if ( $action === 'export' ) {
                    $this->exportUrl = $result['url'] ?? null;
                    $link = SOS_WP\HtmlTag::get('a', [
                            'href'  => $this->exportUrl
                        ,'download' => true
                        ,'content'  => 'here'
                    ]);
                    self::msgOk("Data has been exported: click $link to download the file");
                }
            }
        }

        $this->saveCache();

    }

    public function loadQrCodes() {

        $this->qrcodes = [];

        $filters = [
            SRC\QrCodeSearchStatus::QUERY_NAME => intval( $this->status->value )
        ];

        $results = self::database()->qrcodes->loadList( $filters );

        if ( is_array($results) ) {
            if ( count($results) > 0 ) {
                for ( $n=0; $n<count($results); $n++ ) {
                    $results[$n]->creation_string = $results[$n]->creation->format( "Y/m/d H:i:s" );
                    $results[$n]->url_api = self::plugin()->getApiUrl( $results[$n]->code );
                    $results[$n]->status_icon = SRC\QrCodeSearchStatus::getStatusIcon( !$results[$n]->disabled );
                }
            } else {
                if ( $this->status->value != 0 ) {
                    self::msgInfo( 'No results match the search.' );
                } else {
                    self::msgInfo( "There's no data in the database." );
                }
            }
            $this->qrcodes = $results;
        } else {
            self::msgErr( 'A problem occurred while reading the database.' );
        }
    }

}