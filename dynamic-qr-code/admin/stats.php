<?php
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
$form = $plugin->formStatLog;
$logs = $form->logs;
$data = $form->data;

$chart = false;
if ( $plugin->hasStats() ) {
    $chart = $plugin->addon->chart;
}

echo $plugin->help('scan-statistics');
$plugin->htmlAdminPageTitle('Scan stats');
?>

<div class="wrap">

    <?php $plugin::msgHtml(); ?>

    <?php $form->htmlOpen(); ?>

    <table class="form-table topped xbordered" role="presentation" style="width:inherit;">
        <tbody>
        <tr>
            <td class="">
                <strong>Filter data by</strong>
                <br>
                <?php
                   $form->htmlMode();
                ?>
            </td>
            <td class="">
                <?php
                    $form->htmlId();
                    $form->htmlKey();
                    $form->htmlScript();
                ?>
            </td>
        </tr>
        </tbody>
    </table>

    <table class="form-table topped xbordered" role="presentation" style="width:inherit;">
        <tbody>
        <tr>
            <td class="">

                <table class="form-table topped xbordered" role="presentation" style="width:100%;">
                    <tbody>
                    <tr>
                        <td class="">
                            <strong>From date (h <?php echo sosidee_time_format( \DateTime::createFromFormat('YmdHis', "20000001000000") ); ?>)</strong>
                            <br>
                            <?php $form->htmlFrom(); ?>
                        </td>
                        <td class="">
                            <strong>To date (h <?php echo sosidee_time_format( \DateTime::createFromFormat('YmdHis', "20000001235959") ); ?>)</strong>
                            <br>
                            <?php $form->htmlTo(); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="">
                            <strong>Log status</strong>
                            <br>
                            <?php $form->htmlStates(); ?>
                        </td>
                        <td class="">
                            <strong>Operating system</strong>
                            <br>
                            <?php $form->htmlOpSys(); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="">
                            <strong>Type of devices</strong>
                            <br>
                            <?php $form->htmlMobile(); ?>
                        </td>
                        <td class="">
                        </td>
                    </tr>
                    </tbody>
                </table>

            </td>
            <td class="">
                &nbsp; &nbsp;
            </td>
            <td class="">

                <table class="form-table topped xbordered" role="presentation" style="width:100%;">
                    <tbody>
                    <tr>
                        <td class="">
                            <strong>Charts</strong>
                            <br><br>
                            <?php
                            $form->htmlDTList();
                            echo '&nbsp;';
                            $form->htmlDTMode();
                            echo '<br><br>';
                            $form->htmlGroupDotwChk();
                            echo '&nbsp;';
                            $form->htmlGroupDotwSel();
                            echo '<br><br>';
                            $form->htmlGroupHourChk();
                            echo '&nbsp;';
                            $form->htmlGroupHourSel();
                            echo '<br><br>';
                            $form->htmlGroupDevTypeChk();
                            echo '&nbsp;';
                            $form->htmlGroupDevTypeSel();
                            echo '<br><br>';
                            $form->htmlGroupDevOSChk();
                            echo '&nbsp;';
                            $form->htmlGroupDevOSSel();
                            echo '<br><br>';
                            $form->htmlGroupCountryChk();
                            echo '&nbsp;';
                            $form->htmlGroupCountrySel();
                            echo '<br><br>';
                            $form->htmlGroupLanguageChk();
                            echo '&nbsp;';
                            $form->htmlGroupLanguageSel();
                            echo '<br><br>';
                            $form->htmlGroupStatusChk();
                            echo '&nbsp;';
                            $form->htmlGroupStatusSel();

                            echo '<br><br>';
                            echo '<strong>Files</strong>';
                            echo '<br><br>';
                            $form->htmlEnableDownload();
                            ?>

                        </td>
                    </tr>
                    </tbody>
                </table>

            </td>
        </tr>
       <tr>
           <td class="centered bottomed" colspan="3">
           <?php
               $form->htmlButton( 'search', 'ok' );
           ?>
           </td>
       </tr>
        </tbody>
    </table>

    <?php
        if ( $data->count() > 0 ) {
    ?>
    <br>
    <table class="form-table xbordered" style="background-color: #ffffff; width:inherit;" role="presentation">
        <tbody>
        <tr>
            <th class="pad1">Record(s) found:</th>
            <td class="centered pad1"><?php echo $data->count(); ?></td>
        </tr>
        <tr>
            <th class="pad1">Mimimum date:</th>
            <td class="centered pad1"><?php echo sosidee_datetime_format( $data->getDatetimeMin() ); ?></td>
        </tr>
        <tr>
            <th class="pad1">Maximum date:</th>
            <td class="centered pad1"><?php echo sosidee_datetime_format( $data->getDatetimeMax() ); ?></td>
        </tr>
        </tbody>
    </table>
    <?php
        }
    ?>

    <?php $form->htmlClose(); ?>

<?php

    if ( $data->used() && !$data->empty() ) {

        if ( $chart !== false ) {

            $chart::initialize($data);

            $chart::htmlDT();
            $form->htmlDownloadCount();

            $chart::htmlDotw();
            $form->htmlDownloadDotw();

            $chart::htmlHour();
            $form->htmlDownloadHour();

            $chart::htmlType();
            $form->htmlDownloadType();

            $chart::htmlOS();
            $form->htmlDownloadOS();

            $chart::htmlCountry();
            $form->htmlDownloadCountry();

            $chart::htmlLang();
            $form->htmlDownloadLang();

            $chart::htmlStatus();
            $form->htmlDownloadStatus();

            $chart::load();

        }

    }

?>

</div>