<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class FormTag extends \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\HtmlTag
{

    public static function getStyle( $parameter, $default = null ) {
        if ( !is_null($default) ) {
            if ( !is_null($parameter) ) {
                $results = [];
                $defs = explode(';', $default);
                for ($n=0; $n<count($defs); $n++) {
                    $kvs = explode(':', $defs[$n]);
                    if (count($kvs) == 2) {
                        $results[$kvs[0]] = rtrim( $kvs[1], ';');
                    }
                }
                $pars = explode(';', $parameter);
                for ($n=0; $n<count($pars); $n++) {
                    $kvs = explode(':', $pars[$n]);
                    if (count($kvs) == 2) {
                        $results[$kvs[0]] = rtrim( $kvs[1], ';');
                    }
                }
                $ret = "";
                foreach ($results as $key => $value ) {
                    $ret .= "$key:$value;";
                }
                return $ret;
            } else {
                return $default;
            }
        } else {
            return $parameter;
        }
    }

}