<?php
$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
$form = $plugin->formSearchLog;
$logs = $form->logs;

$plugin->config->load(); // load current configuration
$code_shared = $plugin->config->sharedCodeEnabled->value;
$mfa_enabled = $plugin->config->mfaEnabled->value;

echo $plugin->help('logs');

?>
<h1>Scan logs</h1>

<div class="wrap">

    <?php $plugin::msgHtml(); ?>

    <?php $form->htmlOpen(); ?>

    <table class="form-table" role="presentation">
        <thead>
        <tr>
            <th scope="col" class="centered middled">QR-Code</th>
            <th scope="col" class="centered middled">From date<br>(h <?php echo sosidee_time_format( \DateTime::createFromFormat('YmdHis', "20000001000000") ); ?>)</th>
            <th scope="col" class="centered middled">To date<br>(h <?php echo sosidee_time_format( \DateTime::createFromFormat('YmdHis', "20000001235959") ); ?>)</th>
            <th scope="col" class="centered middled">Status</th>
            <th scope="col" class="centered middled"></th>
            <th scope="col" class="centered middled"></th>
        </tr>
        </thead>
        <tbody>
        <td class="centered middled">
            <?php $form->htmlQID(); ?>
        </td>
        <td class="centered middled">
            <?php $form->htmlFrom(); ?>
        </td>
        <td class="centered middled">
            <?php $form->htmlTo(); ?>
        </td>
        <td class="centered middled">
            <?php $form->htmlStatus(); ?>
        </td>
        <td class="centered middled">
            <?php $form->htmlButton( 'search', 'search' ); ?>
        </td>
        <td class="centered middled">
            <?php $form->htmlCancelAll(); ?>
        </td>
        </tbody>
    </table>

    <br><br>

    <?php
    if ( is_array($logs) && count($logs)>0 ) {
        echo '<p>&nbsp; Record(s) found: ' . count($logs) . '</p>';
    }

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
        //

    ?>

    <table class="form-table sqc bordered" role="presentation">
        <thead>
        <tr>
            <th scope="col" class="bordered middled centered" style="width:<?php echo esc_attr( $sw_date ); ?>">Date</th>
            <th scope="col" class="bordered middled centered" style="width:<?php echo esc_attr( $sw_code ); ?>">Key</th>
            <th scope="col" class="bordered middled centered" style="width:<?php echo esc_attr( $sw_state ); ?>">Status</th>
            <?php if ( $code_shared ) { ?>
                <th scope="col" class="bordered middled centered" style="width:<?php echo esc_attr( $sw_qid ); ?>">Q-ID</th>
            <?php } ?>
            <?php if ( $mfa_enabled ) { ?>
                <th scope="col" class="bordered middled centered" style="width:<?php echo esc_attr( $sw_uk ); ?>">My FastAPP User Key</th>
            <?php } ?>
            <th scope="col" class="centered middled" style="width:<?php echo esc_attr( $sw_btn ); ?>">
                <?php
                if ( is_array($logs) && count($logs)>0 ) {
                    $form->htmlDownload( $logs, $code_shared, $mfa_enabled );
                }
                ?>
            </th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ( is_array($logs) && count($logs)>0 ) {
            for ($n=0; $n<count($logs); $n++) {
                $item = $logs[$n];

                $id = $item->log_id;
                $creation = $item->creation_string;
                $code = $item->code;
                $quid = \SOSIDEE_DYNAMIC_QRCODE\SRC\QrCode::getQID( $item->qrcode_id );
                $status_icon = $item->status_icon;
                $mfa_user_key = $item->user_key;

                ?>
                <tr>
                    <td class="bordered middled centered"><?php echo esc_html( $creation ); ?></td>
                    <td class="bordered middled centered"><?php echo esc_html( $code ); ?></td>
                    <td class="bordered middled centered"><?php echo sosidee_kses( $status_icon ); ?></td>
                    <?php if ( $code_shared ) { ?>
                        <td class="bordered middled centered"><?php echo esc_html( $quid ); ?></td>
                    <?php } ?>
                    <?php if ( $mfa_enabled ) { ?>
                        <td class="bordered middled centered"><?php echo esc_html( $mfa_user_key ); ?></td>
                    <?php } ?>
                    <td class="bordered middled centered"><?php $form->htmlCancel( $id ); ?></td>
                </tr>
            <?php }
        } ?>
        </tbody>
    </table>

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

</div>
