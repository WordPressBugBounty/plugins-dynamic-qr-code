<?php
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA\Display;
use \SOSIDEE_DYNAMIC_QRCODE\SRC\LogStatus;

$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
$form = $plugin->formSearchLog;
$logs = $form->logs;


$code_shared = $form->isCodeShared();
//$plugin->config->load(); // load current configuration
//$mfa_enabled = false; // $plugin->config->mfaEnabled->value;

$icon_pro = $plugin->isPro() ? '' : $plugin->pro();

echo wp_kses( $plugin->help('scan-logs'), sosidee_allowed_html() );

$plugin->htmlAdminPageTitle('Scan logs');

$displayTable = $form->getDataDisplayTable();

?>

<div class="wrap">

    <?php $plugin::msgHtml(); ?>

    <?php $form->htmlOpen(); ?>

    <table class="form-table" role="presentation">
        <tbody>
        <tr>
            <td class="centered topped">
                <span class="bolded">QR-Code</span><br>
                <?php $form->htmlQID(); ?>
            </td>
            <td class="centered topped">
                <span class="bolded">From date</span><br>
                <?php $form->htmlFrom(); ?>
                <br><span class="fs90pc">h <?php echo wp_kses( sosidee_time_format( \DateTimeImmutable::createFromFormat('YmdHis', "20000001000000") ), sosidee_allowed_html() ); ?> </span>
            </td>
            <td class="topped" rowspan="2">
                <span class="bolded" style="margin-bottom: 4px; display:block;">Display <?php echo wp_kses($icon_pro, sosidee_allowed_html()); ?></span>
                <?php
                    $applied = $plugin->applyFilter('_form_log-search_html_fields', false, $form);
                    if ( !$applied ) { ?>
                       <ul>
                           <li><span style="cursor:not-allowed;">&#x2610;</span> Description</li>
                           <li><span style="cursor:not-allowed;">&#x2610;</span> Language</li>
                           <li><span style="cursor:not-allowed;">&#x2610;</span> Operative System</li>
                           <li><span style="cursor:not-allowed;">&#x2610;</span> Geolocation</li>
                       </ul>
                   <?php }
                ?>
            </td>
            <td class="centered middled">
                <?php $form->htmlCancelAll(); ?>
            </td>
        </tr>
        <tr>
            <td class="centered topped">
                <span class="bolded">Status</span><br>
                <?php $form->htmlStatus(); ?>
            </td>
            <td class="centered middled">
                <span class="bolded">To date</span><br>
                <?php $form->htmlTo(); ?>
                <br><span class="fs90pc">h <?php echo wp_kses( sosidee_time_format( \DateTimeImmutable::createFromFormat('YmdHis', "20000001235959") ), sosidee_allowed_html() ); ?> </span>
            </td>
            <td class="centered middled">
                <?php $form->htmlButton( 'search', 'search' ); ?>
            </td>
        </tr>
        </tbody>
    </table>

    <br><br>

    <?php
    if ( $form->isPosted() ) {
        echo '<p>&nbsp; Record(s) found: ' . count($logs) . '</p>';

    if ( is_array($logs) && !empty($logs) ) { ?>

    <table class="form-table sqc bordered" role="presentation">
        <thead>
           <tr>
               <?php
               $context = [ 'form' => $form, 'logs' => $logs ];
               Display::htmlHead($displayTable, $context );
               ?>
           </tr>
        </thead>
        <tbody>
           <?php
                $context = [ 'form' => $form ];
                foreach ( $logs as $log ) :
                    Display::htmlRow($displayTable, $log, $context );
                endforeach;
            ?>
        </tbody>
    </table>

    <?php } ?>

    <p style="font-style:italic;">
        <?php
        if ( !empty($logs) ) {
            echo 'Legend<br>Status:';
            $states = LogStatus::getList();
            foreach ( $states as $key => $value ) {
                echo ' &nbsp; ';
                $icon = LogStatus::getStatusIcon( $key );
                echo wp_kses( $icon . ' ' . $value, sosidee_allowed_html() );
            }
        }
        ?>
    </p>

    <?php } ?>

    <?php
    $form->htmlLogId();
    $form->htmlClose();
    ?>

    <?php if ( !$plugin->isPro() ) {
        echo '<hr style="margin-left:0;width:50%;">';
        echo '<p>';
        echo $plugin->pro();
        echo ' available in the PRO version';
        echo '</p>';
    } ?>



</div>
