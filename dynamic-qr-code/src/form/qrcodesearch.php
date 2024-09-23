<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;

class QrCodeSearch extends Base
{
    public $status;
    public $qrcodes;

    public function __construct() {
        parent::__construct( 'qrcodeSearch', [$this, 'onSubmit'] );

        $this->status = $this->addSelect('status', SRC\QrCodeSearchStatus::ENABLED);
        $this->status->cached = true;

        $this->qrcodes = [];
    }

    public function htmlStatus() {
        $options = SRC\QrCodeSearchStatus::getList('- any -');
        $this->status->html( ['options' => $options] );
    }

    public function htmlButtonLink( $id = 0 ) {
        $this->_plugin->formEditQrCode->htmlButtonLink( $id );
    }

    protected function initialize() {
        if ( !$this->_posted ) {
            $this->loadQrCodes();
        }
    }

    public function onSubmit() {
        $this->loadQrCodes();
        $this->saveCache();
    }

    public function loadQrCodes() {
        $this->qrcodes = [];

        $filters = [
            'status' => intval( $this->status->value )
        ];

        $results = $this->_database->loadQrCodes( $filters );

        if ( is_array($results) ) {
            if ( count($results) > 0 ) {
                for ( $n=0; $n<count($results); $n++ ) {
                    $results[$n]->creation_string = $results[$n]->creation->format( "Y/m/d H:i:s" );
                    $results[$n]->url_api = $this->_plugin->getApiUrl( $results[$n]->code );
                    $results[$n]->status_icon = SRC\QrCodeSearchStatus::getStatusIcon( !$results[$n]->disabled );
                }
            } else {
                if ( $this->status->value > 0 ) {
                    self::msgInfo( 'No results match the search.' );
                } else {
                    self::msgInfo( "There's no data in the database." );
                }
            }
            $this->qrcodes = $results;
        } else {
            self::msgErr( 'A problem occurred.' );
        }
    }

}