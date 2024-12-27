<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Copy2CB
{
    use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TBase;

    public static function getIcon( $text, $title ) {
        return '<a href="javascript:void(0);" onclick="jsSosCopy2Clipboard(\'' . esc_js($text) . '\')" title="' . esc_attr($title) . '" style="width: inherit;"><i class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit;">content_copy</i></a>';
    }

    public static function getAlert( $text, $title ) {
        return '<a href="javascript:void(0);" onclick="alert(\'' . esc_js($text) . '\')" title="' . esc_attr($title) . '" style="width: inherit;"><i class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit;">content_copy</i></a>';
    }

    public static function getApiRootIcon() {
        $title = "copy URL for MyFast App to clipboard";
        return self::getIcon(  self::plugin()->getApiUrl(), $title );
    }

    public static function getApiUrlIcon( $id, $code, $cypher = false ) {
        $title = "copy QR-Code URL to clipboard";
        if ( $id > 0 && $code != '' ) {
            return self::getIcon(  self::plugin()->getApiUrl($code, $cypher), $title );
        } else {
            if ( $id <= 0 ) {
                return self::getAlert( "Please save the QR-Code before copying the URL to clipboard.", $title );
            } else {
                if ( !$cypher ) {
                    return self::getAlert( "Attention: key is empty.", $title );
                } else {
                    return self::getAlert( "Please generate the enhanced QR-Code image before copying the URL to clipboard.", $title );
                }
            }
        }
    }

    public static function getShortcodeIcon( $id, $index = 1, $standard = false ) {
        $title = "copy shortcode to clipboard";
        if ( $id > 0 ) {
            if ( $index == 1 ) {
                $text = Shortcode::getTemplate1( $id );
            } else if ( $index == 2 ) {
                $text = Shortcode::getTemplate2( $id, $standard );
            } else {
                $text = 'a problem occurred';
            }
            return self::getIcon( $text , $title );
        } else {
            return self::getAlert( "Please save the QR-Code before copying the shortcode to clipboard.", $title );
        }
    }

    public static function getHiddenFieldIcon( $id ) {
        $title = "copy hidden field to clipboard";

        if ( $id > 0 ) {
            $text = FORM\Base::getHiddenFieldTemplate( $id );
            return self::getIcon(  $text , $title );
        } else {
            return self::getAlert( "Please generate the enhanced QR-Code image before copying the hidden field to clipboard.", $title );
        }
    }


}