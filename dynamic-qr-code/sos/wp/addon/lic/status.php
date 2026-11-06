<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Status
{
    //native: active | inactive | expired | not-activated | cancelled

    const UNKNOWN = 0;
    const ACTIVE = 1;
    const UNUSED = 2;
    const INACTIVE = -1;
    const EXPIRED = -2;
    const CANCELLED = -3;

    const INVALID = -4;
    const EXHAUSTED = -5;
    const ASSIGNED = -6;

    public static function getList() {
        return [
            self::UNKNOWN,
            self::ACTIVE,
            self::EXPIRED,
            self::UNUSED,
            self::INVALID,
            self::INACTIVE,
            self::CANCELLED,
            self::EXHAUSTED,
            self::ASSIGNED,
        ];
    }

    public static function validate($value) {
        if ( in_array($value, self::getList()) ) {
            return intval($value);
        } else {
            return self::UNKNOWN;
        }
    }

    public static function getByTag( $value ) {

        switch ( $value ) {
            case 'active':
                return self::ACTIVE;

            case 'inactive':
                return self::INACTIVE;

            case 'expired':
                return self::EXPIRED;

            case 'not-activated':
                return self::UNUSED;

            case 'cancelled':
                return self::CANCELLED;

            default:
                return self::UNKNOWN;
        }
    }

    public static function getByCode( $value ) {
        switch ( $value ) {
            // Licenza attivata o valida per il dominio
            case 's100':
            case 's101':
            case 's205':
            case 's215':
                return self::ACTIVE;

            // Licenza disassociata, quindi disponibile per una nuova attivazione
            case 's201':
            case 's203':
                return self::UNUSED;

            // Licenza esistente, ma non valida per il dominio corrente
            case 'e204':
                return self::ASSIGNED;


            // Limite massimo di attivazioni raggiunto
            case 'e112':
                return self::EXHAUSTED;

            // Chiave eliminata
            case 's610':
                return self::CANCELLED;

            // Richiesta, chiave, ordine o prodotto non validi
            case 'e001':
            case 'e002':
            case 'e003':
            case 'e110':
            case 'e111':
            case 'e113':
            case 'e301':
            case 'e304':
            case 'e316':
            case 'e419':
                return self::INVALID;

            default:
                return self::UNKNOWN;
        }
    }

    public static function getTag( $value ) {
        switch ( $value ) {
            case self::ACTIVE:
                return 'active';

            case self::UNUSED:
                return 'not used';

            case self::ASSIGNED:
                return 'assigned elsewhere';

            case self::INACTIVE:
                return 'not active';

            case self::EXHAUSTED:
                return 'exhausted';

            case self::EXPIRED:
                return 'expired';

            case self::CANCELLED:
                return 'cancelled';

            case self::INVALID:
                return 'invalid';

            default:
                return 'unknown';
        }
    }

    public static function getIcon( $value ) {
        $title = self::getTag( $value );

        $ret = self::icon( 'question_mark', 'brown', $title );

        switch ( $value ) {
            case self::ACTIVE:
                $ret = self::icon( 'check_circle', 'green', $title );
                break;

            case self::UNUSED:
                $ret = self::icon( 'pending', 'darkblue', $title );
                break;

            case self::ASSIGNED:
                $ret = self::icon( 'link', 'darkorange', $title );
                break;

            case self::INACTIVE:
                $ret = self::icon( 'do_not_disturb', 'black', $title );
                break;

            case self::EXHAUSTED:
                $ret = self::icon( 'bolt', 'brown', $title );
                break;

            case self::EXPIRED:
                $ret = self::icon( 'schedule', 'brown', $title );
                break;

            case self::CANCELLED:
                $ret = self::icon( 'delete_forever', 'darkred', $title );
                break;

            case self::INVALID:
                $ret = self::icon( 'cancel', 'red', $title );
                break;
        }

        return $ret;
    }


    private static function icon( $label, $color = "", $title = "" ) {
        $color = $color != "" ? " color:$color;" : "";
        return '<i title="' . esc_attr($title) .'" class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit;' . esc_attr($color) . '">' . esc_textarea($label) .'</i>';
    }

}