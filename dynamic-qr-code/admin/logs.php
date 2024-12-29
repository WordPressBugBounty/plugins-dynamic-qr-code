<?php
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
$form = $plugin->formSearchLog;
$show_lang = $form->showLang();
$show_geo = $form->showGeo();
$show_desc = $form->showDesc();
$show_os = $form->showOS();

$logs = $form->logs;

$plugin->config->load(); // load current configuration
$code_shared = $plugin->config->sharedCodeEnabled->value;
$mfa_enabled = $plugin->config->mfaEnabled->value;

$asterisk = $plugin->isPro ? '' : '*';

echo $plugin->help('scan-logs');

$plugin->htmlAdminPageTitle('Scan logs');
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
                <br><span class="fs90pc">(h <?php echo sosidee_time_format( \DateTime::createFromFormat('YmdHis', "20000001000000") ); ?>)</span>
            </td>
            <td class="topped" rowspan="2">
                <span class="bolded" style="margin-bottom: 4px; display:block;">Display <?php echo $asterisk; ?></span>
                <?php $form->htmlShowGeo(); ?>
                <br>
                <?php $form->htmlShowLang(); ?>
                <br>
                <?php $form->htmlShowDesc(); ?>
                <br>
                <?php $form->htmlShowOS(); ?>
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
                <br><span class="fs90pc">(h <?php echo sosidee_time_format( \DateTime::createFromFormat('YmdHis', "20000001235959") ); ?>)</span>
            </td>
            <td class="centered middled">
                <?php $form->htmlButton( 'search', 'search' ); ?>
            </td>
        </tr>
        </tbody>
    </table>

    <br><br>

    <?php
    if ( is_array($logs) && count($logs)>0 ) {
        echo '<p>&nbsp; Record(s) found: ' . count($logs) . '</p>';
    }
/*
        $sw_uk = '30%';
        if ( $mfa_enabled ) {
            $sw_date = '15%';
            if ( $code_shared ) {
                $sw_code = '25%';
            } else {
                $sw_code = '30%';
            }
            $sw_qid = '5%';
            $sw_state = '5%';
            $sw_btn = '10%';
        } else {
            $sw_date = '20%';
            $sw_qid = '10%';
            if ( $code_shared ) {
                $sw_code = '40%';
            } else {
                $sw_code = '50%';
            }
            $sw_state = '10%';
            $sw_btn = '20%';
        }
*/

    if ( is_array($logs) && count($logs)>0 ) {
    ?>

    <table class="form-table sqc bordered" role="presentation">
        <thead>
        <tr>
            <th scope="col" class="bordered middled centered">Date</th>
            <th scope="col" class="bordered middled centered">Status</th>
            <th scope="col" class="bordered middled centered">Key</th>
            <?php if ( $code_shared ) { ?>
                <th scope="col" class="bordered middled centered">Q-ID</th>
            <?php } ?>
            <?php if ( $show_desc ) { ?>
                <th scope="col" class="bordered middled centered">Description</th>
            <?php } ?>
            <?php if ( $show_lang ) { ?>
                <th scope="col" class="bordered middled centered">Language</th>
            <?php } ?>
            <?php if ( $show_geo ) { ?>
                <th scope="col" class="bordered middled centered">Country</th>
            <?php } ?>
            <?php if ( $show_os ) { ?>
                <th scope="col" class="bordered middled centered">Op. System</th>
            <?php } ?>
            <?php if ( $mfa_enabled ) { ?>
                <th scope="col" class="bordered middled centered">My FastAPP User Key</th>
            <?php } ?>
            <th scope="col" class="centered middled">
                <?php $form->htmlDownload( $logs, $mfa_enabled ); ?>
            </th>
        </tr>
        </thead>
        <tbody>
        <?php
            for ($n=0; $n<count($logs); $n++) {
                $item = $logs[$n];

                $id = $item->log_id;
                $creation = $item->creation_string;
                $code = $item->code;
                $quid = \SOSIDEE_DYNAMIC_QRCODE\SRC\QrCode::getQID( $item->qrcode_id );
                $status_icon = $item->status_icon;
                $mfa_user_key = $item->user_key;
                $lang = $show_lang ? $item->lang_desc : '';
                $geo = $show_geo ? $item->country_desc : '';
                $desc = $show_desc ? $item->qrcode_desc : '';
                $op_sys = $show_os ? $item->os_desc : '';

                ?>
                <tr>
                    <td class="bordered middled centered"><?php echo esc_html( $creation ); ?></td>
                    <td class="bordered middled centered"><?php echo sosidee_kses( $status_icon ); ?></td>
                    <td class="bordered middled centered"><?php echo esc_html( $code ); ?></td>
                    <?php if ( $code_shared ) { ?>
                        <td class="bordered middled centered"><?php echo esc_html( $quid ); ?></td>
                    <?php } ?>
                    <?php if ( $show_desc ) { ?>
                        <td class="bordered middled centered"><?php echo esc_html( $desc ); ?></td>
                    <?php } ?>
                    <?php if ( $show_lang ) { ?>
                        <td class="bordered middled centered"><?php echo esc_html( $lang ); ?></td>
                    <?php } ?>
                    <?php if ( $show_geo ) { ?>
                        <td class="bordered middled centered"><?php echo esc_html( $geo ); ?></td>
                    <?php } ?>
                    <?php if ( $show_os ) { ?>
                        <td class="bordered middled centered"><?php echo esc_html( $op_sys ); ?></td>
                    <?php } ?>
                    <?php if ( $mfa_enabled ) { ?>
                        <td class="bordered middled centered"><?php echo esc_html( $mfa_user_key ); ?></td>
                    <?php } ?>
                    <td class="bordered middled centered"><?php $form->htmlCancel( $id ); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>

    <?php
        $form->htmlLogId();
        $form->htmlClose();
    ?>

    <p style="font-style:italic;">
        <?php
        if ( is_array($logs) && count($logs)>0 ) {
            echo 'Legend<br>Status:';
            $states = \SOSIDEE_DYNAMIC_QRCODE\SRC\LogStatus::getList();
            foreach ( $states as $key => $value ) {
                echo ' &nbsp; ';
                $icon = \SOSIDEE_DYNAMIC_QRCODE\SRC\LogStatus::getStatusIcon( $key );
                echo sosidee_kses( $icon . ' ' . $value );
            }
        }
        ?>
    </p>

    <?php if ( !$plugin->isPro ) {
        echo '<hr style="margin-left:0;width:50%;">';
        echo '<p> <b>*</b> PRO version only ';
        echo $plugin->pro();
        echo '</p>';
    } ?>

</div>
