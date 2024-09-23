<?php
$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
$form = $plugin->formEditQrCode;
$cypher = $form->showCypher;

$plugin->config->load(); // load current configuration
$code_shared = $plugin->config->sharedCodeEnabled->value;
$mfa_enabled = $plugin->config->mfaEnabled->value;
$anyQrHideEnabled = $plugin->config->anyQrHideEnabled->value;

echo $plugin->help('edit');

$title = 'QR-Code Edit';
if ( $form->id->value == 0 ) {
    $title .= ' (new)';
}

echo '<h1>'.  esc_html( $title ) . '</h1>';

?>

<div class="wrap">

    <?php $plugin::msgHtml(); ?>

    <?php $form->htmlOpen(); ?>
    <table class="form-table sqc" role="presentation">
        <tbody>
        <tr>
            <th scope="row" class="">Description</th>
            <td class="middled">
                <?php $form->htmlDescription(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="">Key</th>
            <td class="middled">
                <?php $form->htmlCode(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="">Redirect URL</th>
            <td class="middled">
                <?php $form->htmlUrlRedirect(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Active from date</th>
            <td class="middled">
                <?php $form->htmlDateStart(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Active to date</th>
            <td class="middled">
                <?php $form->htmlDateEnd(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Active from time</th>
            <td class="middled">
                <?php $form->htmlTimeStart(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Active to time</th>
            <td class="middled">
                <?php $form->htmlTimeEnd(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">URL before date/time activation</th>
            <td class="middled">
                <?php $form->htmlUrlInactive(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">URL after date/time expiration</th>
            <td class="middled">
                <?php $form->htmlUrlExpired(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Enabled only on</th>
            <td class="middled">
                <?php $form->htmlDotW(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Device operating system</th>
            <td class="middled">
                <?php $form->htmlDeviceOS(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Device language</th>
            <td class="middled">
                <?php $form->htmlDeviceLang(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Priority</th>
            <td class="middled">
                <?php $form->htmlPriority(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">Max total scans</th>
            <td class="middled">
                <?php $form->htmlMaxScanTot(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">URL for total scans limit</th>
            <td class="middled">
                <?php $form->htmlUrlFinished(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">QR image foreground color</th>
            <td class="middled">
                <?php $form->htmlImgForeColor(); ?>
            </td>
        </tr>
        <tr>
            <th scope="row" class="middled">QR image background color</th>
            <td class="middled">
                <?php $form->htmlImgBackColor(); ?>
            </td>
        </tr>
    <?php if ( $anyQrHideEnabled ) { ?>
        <tr>
            <th scope="row" class="middled">URL for unauthorized access</th>
            <td class="middled">
                <?php $form->htmlUrlCypher(); ?>
            </td>
        </tr>
    <?php } ?>
    <?php if ( $mfa_enabled ) { ?>
        <tr>
            <th scope="row" class="middled">Only MyFast APP</th>
            <td class="middled">
                <?php $form->htmlOnlyMFA(); ?>
            </td>
        </tr>
    <?php } ?>
        <tr>
            <th scope="row" class="middled">Disabled</th>
            <td class="middled">
                <?php $form->htmlDisabled(); ?>
            </td>
        </tr>
        </tbody>
    </table>

    <table role="presentation" style="margin-top: 1em;">
        <tbody>
        <tr>
            <td style="width: 120px;">
                <?php $form->htmlDelete( 'delete', 'Are you sure to delete it?' ); ?>
            </td>
            <td style="width: 120px;">
                <?php //$plugin->formSearchQrCode->htmlLinkButton(0, 'new'); ?>
                <?php $plugin->formEditQrCode->htmlButtonLink(0); ?>
            </td>
            <td style="width: 120px;">
                <?php $form->htmlSave('save'); ?>
            </td>
        </tr>
        </tbody>
    </table>

    <table class="form-table sqc" role="presentation">
        <tbody>
        <tr>
            <th scope="row" class="middled">Shortcode for content hiding</th>
            <td class="middled">
                <?php $form->htmlQRShortcode1(); ?>
            </td>
        </tr>
    <?php if ( $code_shared ) { ?>
        <tr>
            <th scope="row" class="middled">Q-ID</th>
            <td class="middled">
                <?php $form->htmlQid(); ?>
            </td>
        </tr>
    <?php } ?>
    <?php if ( $anyQrHideEnabled ) { ?>
        <tr>
            <th scope="row" class="middled">Form hidden field</th>
            <td class="middled">
                <?php $form->htmlFormHiddenField($anyQrHideEnabled); ?>
            </td>
        </tr>
    <?php } ?>
        </tbody>
    </table>

    <p>&nbsp;</p>
    <nav class="nav-tab-wrapper">
        <a id="a-std" href="javascript:void(0);" class="nav-tab<?php echo !$cypher ? ' nav-tab-active' : ''; ?>">Standard QR code</a>
        <a id="a-enh" href="javascript:void(0);" class="nav-tab<?php echo $cypher ? ' nav-tab-active' : ''; ?>">Enhanced QR code</a>
        <?php echo $plugin->help('std-enh-img', 'vertical-align: text-bottom; margin: 4px; margin-left: 1em;'); ?>
    </nav>
    <div id="div-std" style="display: <?php echo !$cypher ? 'block' : 'none'; ?>;">
        <table class="form-table sqc" role="presentation">
            <tbody>
            <tr>
                <th scope="row" class="middled">QR-URL</th>
                <td class="middled">
                    <?php $form->htmlQRUrl(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <?php $form->htmlImgQR(); ?>
    </div>
    <div id="div-enh" style="display: <?php echo $cypher ? 'block' : 'none'; ?>;">
        <table class="form-table sqc" role="presentation">
            <tbody>
            <tr>
                <th scope="row" class="middled">QR-URL</th>
                <td class="middled">
                    <?php $form->htmlQRUrl( true ); ?>
                </td>
            </tr>
            <?php if ( !$anyQrHideEnabled ) { ?>
                <tr>
                    <th scope="row" class="middled">URL for unauthorized access</th>
                    <td class="middled">
                        <?php $form->htmlUrlCypher(); ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
        if ( $form->cypher->value != '' ) {
            $form->htmlImgQR( true );
        }
        ?>
        <table role="presentation" style="margin-top: 1em;">
            <tbody>
            <tr>
                <td style="width: 120px;">
                    <?php $form->htmlCancelCypher(); ?>
                </td>
                <td style="width: 120px;">
                    <?php $form->htmlGenerateCypher(); ?>
                </td>
                <?php if ( !$anyQrHideEnabled ) { ?>
                    <td style="width: 120px;">
                        <?php $form->htmlSaveCypher(); ?>
                    </td>
                <?php } ?>
            </tr>
            </tbody>
        </table>

        <?php if ( !$anyQrHideEnabled ) { ?>
        <table class="form-table sqc" role="presentation">
            <tbody>
            <tr>
                <th scope="row" class="middled">Form hidden field</th>
                <td class="middled">
                    <?php $form->htmlFormHiddenField($anyQrHideEnabled); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <?php } ?>
    </div>

    <table class="form-table sqc" role="presentation">
        <tbody>
        <tr>
            <th scope="row" class="middled">Shortcode for image displaying</th>
            <td class="middled">
                <?php $form->htmlQRShortcode2(); ?>
            </td>
        </tr>
        </tbody>
    </table>

<?php
    $form->htmlId();
    $form->htmlCypher();
    $form->htmlClose();
?>


</div>
