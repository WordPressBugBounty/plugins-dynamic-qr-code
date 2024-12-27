<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA\FormTag;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA\FormButton;
use SOSIDEE_DYNAMIC_QRCODE\SRC\LogStatus;
use SOSIDEE_DYNAMIC_QRCODE\SRC\OS;
use SOSIDEE_DYNAMIC_QRCODE\SRC\DeviceType;
use SOSIDEE_DYNAMIC_QRCODE\SRC\QrCode;
use SOSIDEE_DYNAMIC_QRCODE\SRC\Stats\DTMode;
use SOSIDEE_DYNAMIC_QRCODE\SRC\Stats\GraphType;
use SOSIDEE_DYNAMIC_QRCODE\SRC\Stats\Data;

class LogStat extends Log
{

    const MODE_ID = 1;
    const MODE_KEY = 2;

    private $selMode;
    private $selId;
    private $selKey;
    private $states;
    private $selDevType;
    private $opsys;

    private $chkDTList;
    private $selDTMode;

    private $chkGrpDotw;
    private $selGrpDotw;
    private $chkGrpHour;
    private $selGrpHour;
    private $chkGrpDevType;
    private $selGrpDevType;
    private $chkGrpDevOS;
    private $selGrpDevOS;
    private $chkGrpUserCountry;
    private $selGrpUserCountry;
    private $chkGrpUserLang;
    private $selGrpUserLang;
    private $chkGrpDevStatus;
    private $selGrpDevStatus;

    private $chkDownload;

    public $data;

    public function __construct() {
        parent::__construct('logStat');

        $this->selMode = $this->addRadio('qr_mode', self::MODE_ID);
        $this->selMode->cached = true;
        $this->selKey = $this->addSelect('qr_key', '');
        $this->selKey->cached = true;
        $this->selId = $this->addSelect('qr_id', '');
        $this->selId->cached = true;
        $this->states = $this->addCheckList('states', []);
        $this->states->cached = true;
        $this->selDevType = $this->addSelect('device-type', DeviceType::UNKNOWN);
        $this->selDevType->cached = true;
        $this->opsys = $this->addCheckList('opsys', []);
        $this->opsys->cached = true;

        $this->chkDTList = $this->addCheckBox('dist-dt-list', true);
        $this->chkDTList->cached = true;
        $this->selDTMode = $this->addSelect('dist-dt-mode', DTMode::DAY);
        $this->selDTMode->cached = true;

        $this->chkGrpDotw = $this->addCheckBox('group-dotw-chk', false);
        $this->chkGrpDotw->cached = true;
        $this->selGrpDotw = $this->addSelect('group-dotw-sel', GraphType::PIE);
        $this->selGrpDotw->cached = true;

        $this->chkGrpHour = $this->addCheckBox('group-hour-chk', false);
        $this->chkGrpHour->cached = true;
        $this->selGrpHour = $this->addSelect('group-hour-sel', GraphType::PIE);
        $this->selGrpHour->cached = true;

        $this->chkGrpDevType = $this->addCheckBox('group-type-chk', false);
        $this->chkGrpDevType->cached = true;
        $this->selGrpDevType = $this->addSelect('group-type-sel', GraphType::PIE);
        $this->selGrpDevType->cached = true;

        $this->chkGrpDevOS = $this->addCheckBox('group-os-chk', false);
        $this->chkGrpDevOS->cached = true;
        $this->selGrpDevOS = $this->addSelect('group-os-sel', GraphType::PIE);
        $this->selGrpDevOS->cached = true;

        $this->chkGrpUserCountry = $this->addCheckBox('group-country-chk', false);
        $this->chkGrpUserCountry->cached = true;
        $this->selGrpUserCountry = $this->addSelect('group-country-sel', GraphType::PIE);
        $this->selGrpUserCountry->cached = true;

        $this->chkGrpUserLang = $this->addCheckBox('group-lang-chk', false);
        $this->chkGrpUserLang->cached = true;
        $this->selGrpUserLang = $this->addSelect('group-lang-sel', GraphType::PIE);
        $this->selGrpUserLang->cached = true;

        $this->chkGrpDevStatus = $this->addCheckBox('group-status-chk', false);
        $this->chkGrpDevStatus->cached = true;
        $this->selGrpDevStatus = $this->addSelect('group-status-sel', GraphType::PIE);
        $this->selGrpDevStatus->cached = true;

        $this->chkDownload = $this->addCheckBox('enable-download', false);

        $this->data = new Data();
   }

    public function htmlMode() {
        $options = [
             self::MODE_ID => 'QR-Code'
            ,self::MODE_KEY => 'key'
        ];
        $this->selMode->html( [
             'options' => $options
            ,'onclick' => 'jsSosDynQrCode_StatMode(this.value);'
        ] );
    }

    public function htmlId() {
        //$options = $this->loadQrCodeList('- any QR-Code -');
        $options = QrCode::loadQrCodeList('- any QR-Code -');
        $display = $this->selMode->value == self::MODE_ID ? 'inline;' : 'none';
        $this->selId->html( [
            'options' => $options
            ,'style' => "display: $display;"
        ] );
    }

    public function htmlKey() {
        $options = ['' => '- any key -'];
        $list = self::database()->loadQrKeyList();
        if ( is_array($list) ) {
            for ( $n=0; $n<count($list); $n++ ) {
                $options[$list[$n]->code] = $list[$n]->code;
            }
        }
        $display = $this->selMode->value == self::MODE_KEY ? 'inline;' : 'none';
        $this->selKey->html( [
             'options' => $options
            ,'style' => "display: $display;"
        ] );
    }

    public function htmlStates() {
        $options = LogStatus::getList();
        $this->states->html( [ 'options' => $options ] );
    }

    public function htmlMobile() {
        $options = DeviceType::getList('- any -');
        $this->selDevType->html( [ 'options' => $options ] );
    }

    public function htmlOpSys() {
        $options = OS::getList();
        $this->opsys->html( [ 'options' => $options ] );
    }

    public function htmlDTList() {
        $this->chkDTList->html( ['label' => 'scans distribution by '] );
    }
    public function htmlDTMode() {
        $options = DTMode::getList();
        $this->selDTMode->html( [ 'options' => $options ] );
    }

    public function htmlGroupDotwChk() {
        $this->chkGrpDotw->html( ['label' => 'grouped by day of the week in a'] );
    }
    public function htmlGroupDotwSel() {
        $options = GraphType::getList();
        $this->selGrpDotw->html( ['options' => $options] );
    }

    public function htmlGroupHourChk() {
        $this->chkGrpHour->html( ['label' => 'grouped by hours in a'] );
    }
    public function htmlGroupHourSel() {
        $options = GraphType::getList();
        $this->selGrpHour->html( ['options' => $options] );
    }

    public function htmlGroupDevTypeChk() {
        $this->chkGrpDevType->html( ['label' => 'grouped by type of devices in a'] );
    }
    public function htmlGroupDevTypeSel() {
        $options = GraphType::getList();
        $this->selGrpDevType->html( ['options' => $options] );
    }

    public function htmlGroupDevOSChk() {
        $this->chkGrpDevOS->html( ['label' => 'grouped by operative systems in a'] );
    }
    public function htmlGroupDevOSSel() {
        $options = GraphType::getList();
        $this->selGrpDevOS->html( ['options' => $options] );
    }

    public function htmlGroupCountryChk() {
        $this->chkGrpUserCountry->html( ['label' => 'grouped by countries in a'] );
    }
    public function htmlGroupCountrySel() {
        $options = GraphType::getList();
        $this->selGrpUserCountry->html( ['options' => $options] );
    }

    public function htmlGroupLanguageChk() {
        $this->chkGrpUserLang->html( ['label' => 'grouped by languages in a'] );
    }
    public function htmlGroupLanguageSel() {
        $options = GraphType::getList();
        $this->selGrpUserLang->html( ['options' => $options] );
    }

    public function htmlGroupStatusChk() {
        $this->chkGrpDevStatus->html( ['label' => 'grouped by log states in a'] );
    }
    public function htmlGroupStatusSel() {
        $options = GraphType::getList();
        $this->selGrpDevStatus->html( ['options' => $options] );
    }

    public function htmlEnableDownload() {
        $this->chkDownload->html( ['label' => 'activate download'] );
    }

    private function htmlDownload( $items, $headers ) {

        if ( count($items) > 0 ) {
            $folder = self::plugin()->getTempFolder();
            if ($folder !== false) {
                $lines = [];
                $lines[] = $headers;
                foreach ( $items as $key => $value ) {
                    $row = [
                         $key
                        ,$value
                    ];
                    $lines[] = $row;
                }
                $file = 'stat_' . uniqid() . '.csv';
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
        } else {
            $onclick = "alert('" . htmlentities( addslashes("File is empty."), ENT_NOQUOTES ) . "')";
        }

        $button = FormTag::get( 'input', [
             'type' => 'button'
            ,'value' => 'download'
            ,'onclick' => $onclick
            ,'class' => 'button button-primary'
            ,'style' => FormButton::STYLE_SUCCESS
            ,'title' => 'click to download'
        ] );

        FormTag::html('div', [
             'html' => $button
            ,'style' => 'text-align:right;margin:4px;'
        ]);

    }

    public function htmlDownloadCount() {
        if ( $this->chkDownload->value && $this->data->hasDT ) {
            $headers = ['Date','Scans'];
            if ( $this->selDTMode->value == DTMode::HOUR ) {
                $headers = ['Date time','Scans'];
            }
            $this->htmlDownload( $this->data->datetimes, $headers );
        }
    }
    public function htmlDownloadDotw() {
        if ( $this->chkDownload->value && $this->data->dotwMode != GraphType::NONE ) {
            $this->htmlDownload( $this->data->dotws, ['Day','Scans'] );
        }
    }
    public function htmlDownloadHour() {
        if ( $this->chkDownload->value && $this->data->hourMode != GraphType::NONE ) {
            $this->htmlDownload( $this->data->hours, ['Hour','Scans'] );
        }
    }
    public function htmlDownloadType() {
        if ( $this->chkDownload->value && $this->data->typeMode != GraphType::NONE ) {
            $this->htmlDownload( $this->data->types, ['Device type','Scans'] );
        }
    }
    public function htmlDownloadOS() {
        if ( $this->chkDownload->value && $this->data->osMode != GraphType::NONE ) {
            $this->htmlDownload( $this->data->oses, ['Operative system','Scans'] );
        }
    }
    public function htmlDownloadCountry() {
        if ( $this->chkDownload->value && $this->data->countryMode != GraphType::NONE ) {
            $this->htmlDownload( $this->data->countries, ['Country','Scans'] );
        }
    }
    public function htmlDownloadLang() {
        if ( $this->chkDownload->value && $this->data->langMode != GraphType::NONE ) {
            $this->htmlDownload( $this->data->langs, ['Language','Scans'] );
        }
    }
    public function htmlDownloadStatus() {
        if ( $this->chkDownload->value && $this->data->statusMode != GraphType::NONE ) {
            $this->htmlDownload( $this->data->states, ['Log status','Scans'] );
        }
    }

    public function onSubmit() {
        if ( !self::plugin()->hasStats() ) {
            $msg = $this->getProMsg('Statistics are available only in the PRO version');
            self::msgWarn($msg);
        }
        $this->loadLogs();
    }

    public function loadLogs() {
        $filters = [
             'status' => $this->states->value
            ,'dev_type' => $this->selDevType->value
            ,'op_sys' => $this->opsys->value
        ];

        if ( $this->selMode->value == self::MODE_ID ) {
            $filters['qrcode_id'] = intval( $this->selId->value );
        } else if ( $this->selMode->value == self::MODE_KEY ) {
            $filters['code'] = $this->selKey->value;
        }

        parent::load( $filters );

        $this->data->hasDT = $this->chkDTList->value;
        if ( $this->data->hasDT ) {
            $this->data->dtMode = $this->selDTMode->value;
        }
        $this->data->dotwMode = $this->chkGrpDotw->value ? $this->selGrpDotw->value : GraphType::NONE;
        $this->data->hourMode = $this->chkGrpHour->value ? $this->selGrpHour->value : GraphType::NONE;
        $this->data->typeMode = $this->chkGrpDevType->value ? $this->selGrpDevType->value : GraphType::NONE;
        $this->data->osMode = $this->chkGrpDevOS->value ? $this->selGrpDevOS->value : GraphType::NONE;
        $this->data->countryMode = $this->chkGrpUserCountry->value ? $this->selGrpUserCountry->value : GraphType::NONE;
        $this->data->langMode = $this->chkGrpUserLang->value ? $this->selGrpUserLang->value : GraphType::NONE;
        $this->data->statusMode = $this->chkGrpDevStatus->value ? $this->selGrpDevStatus->value : GraphType::NONE;

        $this->data->load( $this->logs );

    }

    public function htmlScript() {
        $js = self::plugin()::loadAsset('stat.js');
        if ( $js !== false ) {
            $js = str_replace(
                [
                     '$CTRL_ID$'
                    ,'$CTRL_KEY$'
                    ,'$MODE_ID$'
                    ,'$MODE_KEY$'
                ]
                ,[
                     $this->selId->id
                    ,$this->selKey->id
                    ,self::MODE_ID
                    ,self::MODE_KEY
                ]
                , $js
            );
            FormTag::html( 'script',[
                'content' => $js
            ]);
        } else {
            sosidee_log("cannot load asset 'stat.js'.");
        }
    }

}