<?php
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );
/**
 *  CUSTOM PHP FUNCTIONS
 */

if ( !function_exists('sosidee_str_starts_with') ) {
    function sosidee_str_starts_with( $haystack, $needle, $case_insensitive = false ) {
        if ( !is_array($needle) ) {
            $len = strlen($needle);
            if ( $len == 0 ) {
                return true; // all strings start with an empty string...
            } else {
                if ( strlen($haystack) == 0 ) {
                    return false;
                } else {
                    if ( !$case_insensitive ) {
                        return $haystack[0] === $needle[0] && strncmp($haystack, $needle, $len) === 0;
                    } else {
                        // strcasecmp() does not work with accented vowels, e.g.strcasecmp('à','À')=32
                        $haystack = mb_strtolower( substr($haystack, 0, $len) );
                        $needle = mb_strtolower($needle);
                        return strncmp( $haystack, $needle, $len ) === 0;
                    }
                }
            }
        } else {
            $ret = false;
            for ($n=0; $n<count($needle); $n++) {
                if ( sosidee_str_starts_with($haystack, $needle[$n], $case_insensitive) ) {
                    $ret = true;
                    break;
                }
            }
            return $ret;
        }
    }
}

if ( !function_exists('sosidee_str_ends_with') && function_exists('sosidee_str_starts_with') ) {
    function sosidee_str_ends_with( $haystack, $needle, $case_insensitive = false ) {
        if ( !is_array($needle) ) {
            if ( strlen($needle) == 0 ) {
                return true; // all strings end with an empty string...
            } else {
                if ( strlen($haystack) == 0 ) {
                    return false;
                } else {
                    return sosidee_str_starts_with( strrev($haystack), strrev($needle), $case_insensitive);
                }
            }
        } else {
            $ret = false;
            for ($n=0; $n<count($needle); $n++) {
                if ( sosidee_str_ends_with($haystack, $needle[$n], $case_insensitive) ) {
                    $ret = true;
                    break;
                }
            }
            return $ret;
        }
    }
}

if ( !function_exists('sosidee_strcasecmp') ) {
    function sosidee_strcasecmp( $string1, $string2 ) {
        return strcasecmp( mb_strtolower($string1), mb_strtolower($string2) );
    }
}

if ( !function_exists('sosidee_str_remove') ) {
    function sosidee_str_remove( $search, $subject ) {
        return str_replace($search, '', $subject);
    }
}

if ( ! function_exists( 'sosidee_is_login_page' ) ) {
    function sosidee_is_login_page() {
        return in_array(
            $GLOBALS['pagenow'],
            array( 'wp-login.php', 'wp-register.php' ),
            true
        );
    }
}

if ( ! function_exists( 'sosidee_get_query_var' ) ) {
    function sosidee_get_query_var($var, $mixed = null) {
        $ret = get_query_var($var, null);
        if ( is_null($ret) && isset( $_GET[$var] ) ) {
            $ret = sanitize_text_field( $_GET[$var] );
        } else {
            $ret = $mixed;
        }
        return $ret;
    }
}

if ( ! function_exists( 'sosidee_json_decode' ) ) {
    function sosidee_json_decode($data, $def_value = null) {
        $ret = json_decode($data);
        return (json_last_error() == JSON_ERROR_NONE) ? $ret : $def_value;
    }
}

if ( ! function_exists( 'sosidee_is_base64' ) ) {
    function sosidee_is_base64($data) {
        return base64_encode(base64_decode($data, true)) === $data;
    }
}

if ( ! function_exists( 'sosidee_dirname' ) ) {
    function sosidee_dirname( $path, $levels = 1 ) {
        if ( version_compare( phpversion(), '7.0.0') >= 0 ) {
            return dirname($path, $levels);
        } else {
            if ($levels > 1){
                return dirname( sosidee_dirname( $path, --$levels ) );
            }else{
                return dirname( $path );
            }
        }
    }
}
if ( ! function_exists('sosidee_log') && function_exists( 'sosidee_dirname' ) ) {
    function sosidee_log( $log, $context = '' ) {
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            $plug = '?';
            $trace = debug_backtrace();
            if ( isset($trace[0]) ) {
                $caller = $trace[0];
                if ( isset($caller['file']) ) {
                    $path = str_replace(DIRECTORY_SEPARATOR, '/', $caller['file']);
                    $root = str_replace(DIRECTORY_SEPARATOR, '/', ABSPATH );
                    $k = strpos($path, $root);
                    if ($k !== false) {
                        $k += strlen($root);
                    } else {
                        $k = 0;
                    }
                    $plugin_dir = str_replace(DIRECTORY_SEPARATOR, '/', WP_PLUGIN_DIR ) . '/';
                    if ( strpos($path, $plugin_dir) !== false ) {
                        $k = strpos($path, $plugin_dir) + strlen($plugin_dir);
                    } else {
                        $plugin_dir = str_replace(DIRECTORY_SEPARATOR, '/', WPMU_PLUGIN_DIR ) . '/';
                        if ( strpos($path, $plugin_dir) !== false ) {
                            $k = strpos($path, $plugin_dir) + strlen($plugin_dir);
                        }
                    }
                    $plug = substr($path, $k);
                    $m = strpos($plug, '/') + $k;
                    if ( $m !== false ) {
                        $plug = substr($path, $k, $m - $k);
                    }
                }
            }
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( "[$plug] $context" . print_r( $log, true ) );
            } else {
                error_log( "[$plug] $context" . (string)$log );
            }
        }
    }
}

if ( ! function_exists( 'sosidee_is_rest' ) ) {
    function sosidee_is_rest() {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }
        if ( isset( $_GET['rest_route'] ) && !empty( $_GET['rest_route']) ) {
            return true;
        }
        return strpos( $_SERVER['REQUEST_URI'], trailingslashit( rest_get_url_prefix() ) ) !== false;
    }
}

if ( ! function_exists( 'sosidee_datetime_format' ) ) {
    function sosidee_datetime_format( $value, $format = false ) {
        if ( $format === false) {
            $df = get_option('date_format');
            $tf = get_option('time_format');
            $format = "$df $tf";
        }
        return $value->format( $format );
    }
}
if ( ! function_exists( 'sosidee_date_format' ) ) {
    function sosidee_date_format( $value, $format = false ) {
        if ( $format === false) {
            $format = get_option('date_format');
        }
        return $value->format( $format );
    }
}
if ( ! function_exists( 'sosidee_time_format' ) ) {
    function sosidee_time_format( $value, $format = false ) {
        if ( $format === false) {
            $format = get_option('time_format');
        }
        return $value->format( $format );
    }
}
if ( ! function_exists( 'sosidee_current_datetime' ) ) {
    function sosidee_current_datetime() {
        return \DateTime::createFromImmutable( current_datetime() );
    }
}
if ( ! function_exists( 'sosidee_server_datetime' ) ) {
    /// DEPRECATED ///
    function sosidee_server_datetime() {
        sosidee_log('sosidee_server_datetime() is deprecated: use sosidee_current_datetime().');
        if ( function_exists( 'wp_timezone' ) ) {
            return new \DateTime('now', wp_timezone());
        } else {
            return new \DateTime('now', new \DateTimeZone( date_default_timezone_get() ) );
        }
    }
}

/**
 * wp_kses_allowed_html:
 * a
 *      href
 *      title
 * abbr
 *      title
 * acronym
 *      title
 * b
 * blockquote
 *      cite
 * cite
 * code
 * del
 *      datetime
 * em
 * i
 * q
 *      cite
 * s
 * strike
 * strong
 */
if ( ! function_exists( 'sosidee_kses' ) ) {
    function sosidee_kses( $value ) {

        $tags = [
              'a', 'b', 'br', 'button', 'caption', 'code', 'col', 'colgroup'
            , 'data', 'div', 'em', 'form'
            , 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
            , 'hr', 'i', 'img', 'input'
            , 'label', 'legend', 'li', 'nav'
            , 'ol', 'optgroup', 'option'
            , 'p', 'pre', 'script', 'select', 'span', 'strong'
            , 'table', 'tbody', 'td', 'textarea', 'th', 'thead', 'title', 'tr'
            , 'ul'
        ];

        $attrs = [
              'accept', 'action', 'alt', 'checked', 'class', 'cols', 'colspan'
            , 'disabled', 'download', 'enctype'
            , 'for', 'form', 'height', 'href', 'id'
            , 'label', 'max', 'maxlength', 'method', 'min', 'name'
            , 'onblur', 'onchange', 'onclick', 'onfocus', 'onload', 'onsubmit'
            , 'readonly', 'rows', 'rowspan', 'scope', 'selected', 'size', 'span', 'src', 'step', 'style'
            , 'target', 'title', 'type', 'value', 'width', 'wrap'
        ];

        $allowed_htmls = wp_kses_allowed_html();

        for ( $i=0; $i<count($tags); $i++ ) {
            $tag = $tags[$i];
            if ( !key_exists($tag, $allowed_htmls) ) {
                $allowed_htmls[$tag] = [];
            }
            for ( $j=0; $j<count($attrs); $j++ ) {
                $attr = $attrs[$j];
                if ( !key_exists($attr, $allowed_htmls[$tag]) ) {
                    $allowed_htmls[$tag][$attr] = [];
                }
            }
        }

        $ret = wp_kses( $value , $allowed_htmls );
        if ( strpos( $ret, 'href="void(0);"' ) !== false ) {
            $ret = str_replace( 'href="void(0);"', 'href="javascript:void(0);"', $ret); // otherwise, it does NOT work
        }
        return $ret;
    }
}

