<?php
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA\FormTag;
use SOSIDEE_DYNAMIC_QRCODE\SRC\Copy2CB;

$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();

echo wp_kses( $plugin->help('settings'), sosidee_allowed_html() );

$plugin->htmlAdminPageTitle('Settings');
?>
    <div class="wrap">
        <?php $plugin::msgHtml(); ?>
        <form method="post" action="options.php">
            <?php $plugin->config->html(); ?>
        </form>
<?php
    echo '<table class="form-table" role="presentation">';
/*
    if ( $plugin->config->mfaEnabled->value ) {
        echo '<tr>';
            echo '<th scope="row">URL for My FastAPP</th>';
            echo '<td>';
                echo wp_kses( Copy2CB::getApiRootIcon(), sosidee_allowed_html() );
                echo ' &nbsp; ';
                echo wp_kses( $plugin->getApiUrl(), sosidee_allowed_html() );
            echo '</td>';
        echo '</tr>';
    }
*/
        echo '<tr>';
            echo '<th scope="row">Server date/time</th>';
            echo '<td>';
                echo '<span id="timer" style="font-style: italic;">';
                FormTag::html( 'img', [
                    'alt' => 'loading...'
                    ,'src' => $plugin->getLoaderSrc(24)
                    ,'width' => '12px'
                ]);
                echo '</span>';
            echo '</td>';
        echo '</tr>';
    echo '</table>';

    if (!$plugin->isPro()) {
        echo '<hr style="margin-left:0;width:50%;">';
        echo '<p>';
        echo $plugin->pro();
        echo ' available in the PRO version';
        echo '</p>';
        echo '<p>';
        echo $plugin->pro('color: #990099;', 'editable in the PRO version');
        echo ' editable in the PRO version';
        echo '</p>';
    }

?>
    </div>
