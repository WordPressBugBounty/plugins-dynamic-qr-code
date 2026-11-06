<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\Stats;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

//use SOSIDEE_DYNAMIC_QRCODE\SOS\Datetime;
//use SOSIDEE_DYNAMIC_QRCODE\SOS\Locale;
use SOSIDEE_DYNAMIC_QRCODE\SRC\DeviceType;
use SOSIDEE_DYNAMIC_QRCODE\SRC\OS;
use SOSIDEE_DYNAMIC_QRCODE\SRC\LogStatus;

class Data
{
    use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TAsset;

    private $logs;

    public $hasDT;
    public $dtMode;
    public $datetimes;
    public $dotwMode;
    public $dotws;
    public $hourMode;
    public $hours;
    public $typeMode;
    public $types;
    public $osMode;
    public $oses;
    public $countryMode;
    public $countries;
    public $langMode;
    public $langs;
    public $statusMode;
    public $states;

    public function __construct() {
        $this->logs = [];

        $this->hasDT = false;
        $this->dtMode = DTMode::DAY;
        $this->datetimes = array();
        $this->dotwMode = GraphType::NONE;
        $this->dotws = array();
        $this->hourMode = GraphType::NONE;
        $this->hours = array();
        $this->typeMode = GraphType::NONE;
        $this->types = array();
        $this->osMode = GraphType::NONE;
        $this->oses = array();
        $this->countryMode = GraphType::NONE;
        $this->countries = array();
        $this->langMode = GraphType::NONE;
        $this->langs = array();
        $this->statusMode = GraphType::NONE;
        $this->states = array();

    }

    public function count() {
        return count($this->logs);
    }

    public function getDatetimeMin() {
        if ( $this->count() == 0) {
            return false;
        }
        $ret = $this->logs[0]->creation;
        foreach ( $this->logs as $log ) {
            if ( $ret > $log->creation ) {
                $ret = $log->creation;
            }
        }
        return $ret;
    }
    public function getDatetimeMax() {
        if ( $this->count() == 0) {
            return false;
        }
        $ret = $this->logs[0]->creation;
        foreach ( $this->logs as $log ) {
            if ( $ret < $log->creation ) {
                $ret = $log->creation;
            }
        }
        return $ret;
    }

    public function load( $logs ) {
        $this->logs = $logs;

        if ( $this->used() ) {

            if ( $this->dotwMode != GraphType::NONE ) {
                $days = DateTime::getWeekDays('name');
                foreach ( $days as $day ) {
                    $this->dotws[$day] = 0;
                }
            }

            if ( $this->hourMode != GraphType::NONE ) {
                $format = DateTime::getHourFormat();
                $midnight = mktime(0, 0, 0, 1, 1, 2012); // any day would be the day
                for ( $h = 0; $h < 24; $h++ ) {
                    $current = $midnight + ($h * 3600);
                    $index = date_i18n($format, $current);
                    $this->hours[$index] = 0;
                }
            }

            if ( $this->typeMode != GraphType::NONE ) {
                $this->types[DeviceType::getDescription(DeviceType::UNKNOWN)] = 0;
                $this->types[DeviceType::getDescription(DeviceType::MOBILE)] = 0;
                $this->types[DeviceType::getDescription(DeviceType::NOT_MOBILE)] = 0;
            }

            if ( $this->osMode != GraphType::NONE ) {
                $this->oses[OS::getDescription(OS::UNKNOWN)] = 0;
                $this->oses[OS::getDescription(OS::ANDROID)] = 0;
                $this->oses[OS::getDescription(OS::IOS)] = 0;
                $this->oses[OS::getDescription(OS::OTHER)] = 0;
            }

            if ( $this->statusMode != GraphType::NONE ) {
                $items = LogStatus::getList();
                foreach ( $items as $value ) {
                    $this->states[$value] = 0;
                }
                $this->states[LogStatus::getDescription(LogStatus::NONE, 'unknown')] = 0;
            }

            foreach ( $this->logs as $log ) {
                if ( $this->hasDT ) {
                    $this->addDT( $log->creation );
                }

                if ( $this->dotwMode != GraphType::NONE ) {
                    $this->addDotw( $log->creation );
                }

                if ( $this->hourMode != GraphType::NONE ) {
                    $this->addHour( $log->creation );
                }

                if ( $this->typeMode != GraphType::NONE ) {
                    $this->addType( $log->dev_type );
                }

                if ( $this->osMode != GraphType::NONE && isset($log->op_sys) ) {
                    $this->addOS( $log->op_sys );
                }

                if ( $this->countryMode != GraphType::NONE && isset($log->country) ) {
                    $this->addCountry( $log->country );
                }

                if ( $this->langMode != GraphType::NONE && isset($log->lang) ) {
                    $this->addLang( $log->lang );
                }

                if ( $this->statusMode != GraphType::NONE ) {
                    $this->addStatus( $log->status );
                }

            }

            if ( $this->typeMode != GraphType::NONE ) {
                $unknown = DeviceType::getDescription(DeviceType::UNKNOWN);
                if ( $this->types[$unknown] == 0 ) {
                    unset($this->types[$unknown]);
                }
            }

            if ( $this->osMode != GraphType::NONE ) {
                $unknown = OS::getDescription(OS::UNKNOWN);
                if ( $this->oses[$unknown] == 0 ) {
                    unset($this->oses[$unknown]);
                }
            }

            if ( $this->statusMode != GraphType::NONE ) {
                $unknown = LogStatus::getDescription(LogStatus::NONE, 'unknown');
                if ( $this->states[$unknown] == 0 ) {
                    unset($this->states[$unknown]);
                }
            }

        }
    }

    private static function get( &$item ) {
        $item = isset($item) ? $item + 1 : 1;
    }

    public function used() {
        return $this->hasDT || $this->dotwMode != GraphType::NONE || $this->hourMode != GraphType::NONE
            || $this->typeMode != GraphType::NONE || $this->osMode != GraphType::NONE || $this->countryMode != GraphType::NONE
            || $this->langMode != GraphType::NONE || $this->statusMode != GraphType::NONE;
    }

    public function empty() {
        return count($this->datetimes) == 0 && count($this->dotws) == 0 && count($this->hours) == 0
            && count($this->types) == 0 && count($this->oses) == 0 && count($this->countries) == 0
            && count($this->langs) == 0
            && count($this->states) == 0;
    }

    public function addDT( $value ) {
        $format = get_option( 'date_format' );
        if ( $this->dtMode == DTMode::HOUR ) {
            $format .= ' ' . DateTime::getHourFormat();
        }
        $index = DateTime::getDatetimeAsString( $value, $format );
        self::get( $this->datetimes[$index] );
    }

    public function addDotw( $value ) {
        $name = DateTime::getDayNameOfTheWeek( $value );
        self::get( $this->dotws[$name] );
    }

    public function addHour( $value ) {
        $format = DateTime::getHourFormat();
        $index = DateTime::getTimeAsString( $value, $format );
        self::get( $this->hours[$index] );
    }

    public function addType( $value ) {
        $index = DeviceType::getDescription( $value );
        self::get( $this->types[$index] );
    }

    public function addOS( $value ) {
        $index = OS::getDescription( $value );
        self::get( $this->oses[$index] );
    }

    public function addCountry( $value ) {
        $index = self::getCountryDescription( $value );
        self::get( $this->countries[$index] );
    }

    public function addLang( $value ) {
        $index = self::getLanguageDescription( $value );
        self::get( $this->langs[$index] );
    }

    public function addStatus( $value ) {
        $index = LogStatus::getDescription( $value, 'unknown' );
        self::get( $this->states[$index] );
    }

}