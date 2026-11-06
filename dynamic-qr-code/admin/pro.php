<?php
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
$form = $plugin->formPro;

$plugin->htmlAdminPageTitle('Upgrade to the <i style="background-color:#0d66c2;color:#fff;">&nbsp;new&nbsp;</i> PRO version');
?>
<div class="wrap">

    <?php $plugin::msgHtml(); ?>

    <p>
        If you need more advanced customization options to align your QR codes with your brand identity and marketing materials, the PRO version is available as an <strong>Add-on</strong>.
    </p>
    <p>
        <i title="migration of your active license" class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit; color: green;">verified</i>

        <em>Your current license will work with the new PRO version: just migrate it with no additional charge.</em>
    </p>

    <h2>PRO Features</h2>
    <p>
        The PRO add-on unlocks a range of powerful design tools directly within the QR code generator:
        <ul class="ul-disc" style="margin-left: 20px;">
            <li>Statistics</li>
            <li>Geolocalization</li>
            <li>Open social resources (profile/chat/channel/video)</li>
            <li>
                QR-Code additional properties
                <ul class="ul-circle" style="margin-left: 20px;">
                    <li>Image quality</li>
                    <li>Query parameter in the redirect URL</li>
                    <li>Specific image size and border</li>
                </ul>
            </li>
        <li>Additional log data</li>
        <li>QR-Code bulk import/export</li>
    </ul>
    </p>

    <h2>Information about <?php $form->htmlAddonName(); ?> </h2>
    <p> <?php $form->htmlAddonStatus(); ?> </p>

    <?php $form->htmlOpen(); ?>

    <?php $form->htmlInstallAddon(); ?>
    <?php $form->htmlActivateAddon(); ?>

    <?php $form->htmlDeactivateProLicman(); ?>

    <?php $form->htmlClose(); ?>

    <?php $form->htmlLinkToLicensePage(); ?>

    <br>
    <hr>
    <p>
       <a href="https://sosplugin.com/eula" target="_blank" title="EULA">End User License Agreement</a>
        &nbsp;
       <a href="https://sosplugin.com/license" target="_blank" title="Software License">License terms for PRO features</a>
    </p>

</div>

