<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\Stats;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Data\TDateTime;

class DateTime
{
    use TDateTime;

    public static function getHourFormat() {
        $format = get_option('time_format');

        // estrae i componenti che interessano (ore e am/pm)
        preg_match('/([gGhH])/', $format, $hour_match);
        preg_match('/([aA])/', $format, $ampm_match);

        // costruisce il nuovo formato
        $new_format = [];

        if ( !empty($hour_match[0]) ) {
            $new_format[] = $hour_match[0];
        }

        if ( !empty($ampm_match[0]) ) {
            $new_format[] = $ampm_match[0];
        }

        // unisce i componenti con uno spazio
        return implode(' ', $new_format);
    }
}