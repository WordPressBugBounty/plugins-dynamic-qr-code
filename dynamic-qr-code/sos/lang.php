<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

/***
 * da verificare se mantenere o smantellare...
 */
class Lang
{
    private static $codes = array();
    private static $texts = array();
    private static $default = null;
    private static $current = '';
    
    public static $MONTHS = array(
         'en' => array('Month','January','February','March','April','May','June','July','August','September','October','November','December')
        ,'it' => array('Mese','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre')
    );
    public static $DAYS = array(
          'en' => array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
         ,'it' => array('Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato')
    );

    public static function getMonth( $index, $length = 0 ) {
        global $SOS_USR;
        $lang = ( isset($SOS_USR) ) ? $SOS_USR->lang : 'en';
        $ret = self::$MONTHS[$lang][$index];
        if ( $length > 0 ) {
            $ret = substr($ret, 0, $length);
        }
        return $ret;
    }

    public static function getDow( $index, $length = 0 ) {
        global $SOS_USR;
        $lang = (isset($SOS_USR)) ? $SOS_USR->lang : 'en';
        $ret = self::$DAYS[$lang][$index];
        if ( $length > 0 ) {
            $ret = substr($ret, 0, $length);
        }
        return $ret;
    }

    public static function load( $list ) {
        global $SOS_LOG;
        
        self::$codes = array();
        self::$texts = array();

        if ( !is_array($list) ) {
            $list = array($list);
        }

        foreach ( $list as $item ) {
            $arr = explode('/', $item);
            $code = strtolower(end($arr));
            self::$codes[] = $code;
            $file = $item . '.ini';
            if ( file_exists($file) ) {
                self::$texts[$code] = parse_ini_file($file); 
                if ( self::$texts[$code] === false ) {
                    self::$texts[$code] = array();
                    if ( isset($SOS_LOG) ) {
                        $SOS_LOG->warning('SOS\Lang.init() failed to load ' . $item . '.');
                    }
                }
            } else {
                self::$texts[$code] = array();
                if ( isset($SOS_LOG) ) {
                    $SOS_LOG->warning('SOS\Lang.init() file not found: ' . $file);
                }
            }
        }
    }    
    
    public static function read() {
        $list = func_get_args();
        return $list[self::$current->index];
    }

    public static function get( $key, $code = null ) {
        $ret = $key;
        if ( is_null($code) ) {
            $code = self::$current->value;
        }
        $list = self::$texts[$code];
        if ( array_key_exists($key, $list) ) {
            $ret = $list[$key];
        } else {
            $code = self::$default->value;
            $list = self::$texts[$code];
            if ( array_key_exists($key, $list) ) {
                $ret = $list[$key];
            }
        }
        return $ret;
    } 

    private static function _getIndex( $code ) {
        $ret = false;
        for ( $n=0; $n<count(self::$codes); $n++ ) {
            if ( self::$codes[$n] == $code ) {
                $ret = $n;
                break;
            }
        }
        return $ret;
    }

    public static function setDefault( $code ) {
        $value = self::check($code);
        $index = self::_getIndex($value);
        self::$default = new LangItem($index, $value);
    }

    public static function setCurrent( $code ) {
        $value = self::check($code);
        $index = self::_getIndex($value);
        self::$current = new LangItem($index, $value);
    }

    public static function check( $code ) {
        return (in_array($code, self::$codes)) ? $code : self::$default->value;
    }

    /**
     * ricava la lingua preferita dal browser
    */
    public static function getMain() {
        $ret = false;
        if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
            $langs = array();
            $items = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            for ( $n=0; $n<count($items); $n++ ) {
                $code = false;
                $parts = explode(';', trim($items[$n]));
                if ( isset($parts[0]) ) {
                    $code = substr(trim($parts[0]), 0, 2);
                }
                if ( isset($parts[1]) ) {
                    $weight = floatval(substr(trim($parts[1]),2));
                } else {
                    $weight = 1.0;
                }
                if ( $code !== false && (!isset($langs[$code]) || $langs[$code] < $weight) ) {
                    $langs[$code] = $weight;
                }
            }
            if ( count($langs) > 0 ) {
                arsort($langs);
                reset($langs);
                $ret = key($langs);
            }
        }
        return $ret;
    }

    /**
     * ricava la miglior opzione di lingua tra quelle consentite e quelle preferite dal browser
    */
    public static function getBest( $availables = [], $default = '' ) {
        if ( !is_array($availables) ) {
            $availables = array($availables);
        }
        if ( count($availables) == 0 ) {
            $availables = self::$codes;
        }
        if ( $default == '' ) {
            $default = self::$default;
        }
        if ( !in_array($default, $availables) ) {
            $default = $availables[0];
        }
        $ret = $default;
        if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
            $langs = array();
            $items = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            for ( $n=0; $n<count($items); $n++ ) {
                $code = false;
                $parts = explode(';', trim($items[$n]));
                if ( isset($parts[0]) ) {
                    $code = substr(trim($parts[0]), 0, 2);
                }
                if ( isset($parts[1]) ) {
                    $weight = floatval(substr(trim($parts[1]),2));
                } else {
                    $weight = 1.0;
                }
                if ( $code !== false && (!isset($langs[$code]) || $langs[$code] < $weight) ) {
                    $langs[$code] = $weight;
                }
            }
            arsort($langs);
            if ( count($availables) > 0 ) {
                foreach ( $langs as $code => $value ) {
                    if ( in_array($code, $availables) ) {
                        $ret = $code;
                        break;
                    }
                }
            } else {
                reset($langs);
                $ret = key($langs);
            }
        }
        return $ret;
    }

    public static function init() {
        self::$default = new LangItem(0, '');
    }
}

class LangItem
{
    public $value;
    public $index;
    function __construct( $index, $value ) {
        $this->index = $index;
        $this->value = $value;
    }
}

Lang::init();