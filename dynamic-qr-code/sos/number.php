<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

class Number
{

    // $min <= return < $max
    public static function getRandomInteger( $min, $max ) {
        $range = ($max - $min);
    
        if ( $range <= 0 ) {
            // Not so random...
            return $min;
        }
    
        $log = log($range, 2);
    
        // Length in bytes.
        $bytes = (int) ($log / 8) + 1;
    
        // Length in bits.
        $bits = (int) $log + 1;
    
        // Set all lower bits to 1.
        $filter = (int) (1 << $bits) - 1;
    
        do {
            $rnd = hexdec( bin2hex( openssl_random_pseudo_bytes($bytes) ) );
    
            // Discard irrelevant bits.
            $rnd = $rnd & $filter;
    
        } while ($rnd >= $range);
    
        return ($min + $rnd);
    }
    
    public static function round( $value, $decimal = 0 ) {
        if ( $decimal == 0 ) {
            return intval(round($value));
        } else {
            return round($value, $decimal);
        }
    }

    /***
     * divides a quantity in chunks of fixed length (the last one can differ)
     * USAGE:
        $ranges = getRanges($total, $length);
        for ($i=0; $i<count($ranges); $i++) {
            $range = $ranges[$i];
            for ($j=$range->min; $j<$range->max; $j++) {
                //
            }
        }
    */
    public static function getRanges( $total, $length ) {
        $ret = array();
        $main = intval($total / $length);
        $rem = $total % $length;
        if ( $rem > 0 ) {
            $main++;
        }
        $min = 0;
        $max = 0;
        for ( $n=0; $n<$main; $n++ ) {
            $min = $max;
            $max = $min + $length;
            $ai = new Range($min, $max);
            $ret[] = $ai;
        }
        if ( $rem > 0 ) {
            $max = $min + $rem;
            $ret[count($ret) - 1] = new Range($min, $max); //modifica l'ultimo intervallo
        }
        return $ret;
    }
}

class Range
{
    public $min;
    public $max;
    public $count;

    public function __construct( $min, $max ) {
       $this->min = $min;
       $this->max = $max;
       $this->count = $max - $min;
    }
}