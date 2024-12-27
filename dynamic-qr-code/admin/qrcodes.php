<?php
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SRC\QrCode;
use SOSIDEE_DYNAMIC_QRCODE\SRC\QrCodeSearchStatus;
use SOSIDEE_DYNAMIC_QRCODE\SRC\Copy2CB;

$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
$form = $plugin->formSearchQrCode;
$qrcodes = $form->qrcodes;
$plugin->config->load(); // load the current configuration
$code_shared = $plugin->config->sharedCodeEnabled->value;


if ( $code_shared ) {
    $sw_desc = '45%';
} else {
    $sw_desc = '50%';
}

echo $plugin->help('qr-codes-list');

$plugin->htmlAdminPageTitle('QR-Code List');
?>

<div class="wrap">

<?php $plugin::msgHtml(); ?>

<?php $form->htmlOpen(); ?>

    <table class="form-table sqc" style="width: inherit;" role="presentation">
        <tbody>
        <tr>
            <td class="centered middled" style="font-weight: bold;">Enablement</td>
            <td class="centered middled">
                <?php $form->htmlStatus(); ?>
            </td>
            <td class="centered middled">
                <?php $form->htmlButton( '', 'search' ); ?>
            </td>
        </tr>
        </tbody>
    </table>

    <table class="form-table sqc bordered pad2p" role="presentation">
        <thead>
        <tr>
            <th scope="col" class="bordered middled centered" style="width: 5%;">QR-URL</th>
            <th scope="col" class="bordered middled centered" style="width: <?php echo esc_attr( $sw_desc ); ?>;">Description</th>
            <th scope="col" class="bordered middled centered" style="width: 15%;">Key</th>
            <?php if ( $code_shared ) { ?>
                <th scope="col" class="bordered middled centered" style="width: 5%">Q-ID</th>
            <?php } ?>
            <th scope="col" class="bordered middled centered" style="width: 5%;">State</th>
            <th scope="col" class="bordered middled centered" style="width: 15%;">Creation</th>
            <th scope="col" class="bordered middled centered" style="width: 10%;">
                <?php $form->htmlButtonLink(); ?>
            </th>
        </tr>
        </thead>
        <tbody>
<?php
if ( is_array($qrcodes) && count($qrcodes) > 0 ) {
    for ( $n=0; $n<count($qrcodes); $n++ ) {
        $item = $qrcodes[$n];
        $description = $item->description;
        $code = $item->code;
        $status_icon = $item->status_icon;
        $creation = $item->creation_string;
        $id = $item->qrcode_id;
        $url = $item->url_api;
        $quid = QrCode::getQID( $item->qrcode_id );
        //$copy = $plugin->getCopyApiUrl2CBIcon( $id, $code );
        $copy = Copy2CB::getApiUrlIcon( $id, $code );
    ?>
            <tr>
                <td class="bordered middled centered"><?php echo sosidee_kses( $copy ); ?></td>
                <td class="bordered middled centered"><?php echo esc_html( $description ); ?></td>
                <td class="bordered middled centered"><?php echo esc_html( $code ); ?></td>
                <?php if ( $code_shared ) { ?>
                    <td class="bordered middled centered"><?php echo esc_html( $quid ); ?></td>
                <?php } ?>
                <td class="bordered middled centered"><?php echo sosidee_kses( $status_icon ); ?></td>
                <td class="bordered middled centered"><?php echo esc_html( $creation ); ?></td>
                <td class="bordered middled centered"><?php $form->htmlButtonLink( $id ); ?></td>
            </tr>
<?php
    }
} ?>
        </tbody>
    </table>

<?php $form->htmlClose(); ?>

    <p style="font-style:italic;">
        <?php
        if ( is_array($qrcodes) && count($qrcodes) > 0 ) {
            echo 'Legend<br>State:';
            $states = QrCodeSearchStatus::getList();
            foreach ( $states as $key => $value ) {
                echo ' &nbsp; ';
                $icon = QrCodeSearchStatus::getStatusIcon( $key );
                echo sosidee_kses( $icon . ' ' . $value );
            }
        }
        ?>
    </p>

</div>
