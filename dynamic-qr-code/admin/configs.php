<?php
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA\FormTag;
use SOSIDEE_DYNAMIC_QRCODE\SRC\Copy2CB;

$plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();

echo $plugin->help('settings');
?>
<h1><?php echo esc_html( $plugin->name ); ?></h1>
    <div class="wrap">
        <?php $plugin::msgHtml(); ?>
        <form method="post" action="options.php">
            <?php $plugin->config->html(); ?>
        </form>
<?php
    echo '<table class="form-table" role="presentation">';
    if ( $plugin->config->mfaEnabled->value ) {
        echo '<tr>';
            echo '<th scope="row">URL for My FastAPP</th>';
            echo '<td>';
                //echo $plugin->getCopyApiRoot2CBIcon();
                echo Copy2CB::getApiRootIcon();
                echo ' &nbsp; ';
                echo $plugin->getApiUrl();
            echo '</td>';
        echo '</tr>';
    }
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
    $now = sosidee_current_datetime();
    $month = $now->format('m') - 1;
    $js = "jsDynSetServerDateTime( new Date( {$now->format('Y')}, {$month}, {$now->format('d')}, {$now->format('H')}, {$now->format('i')}, {$now->format('s')}) );";
    FormTag::html( 'script', [
        'type' => 'application/javascript'
        ,'content' => $js
    ]);

    if ( !$plugin->hasAnyAddon() ) {
        echo '<hr style="margin-left:0;width:50%;">';
        echo $plugin->pro() . ' available in the PRO version';
    }

?>
    </div>
