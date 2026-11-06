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
            for ( $n=0; $n<count($needle); $n++ ) {
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
        $a = (string) $string1;
        $b = (string) $string2;

        if ( class_exists('Collator') ) {
            static $c = null;
            if ($c === null) {
                $c = new \Collator('root');
                $c->setStrength( \Collator::SECONDARY ); // case-insensitive, but considers accents and symbols
            }
            $r = $c->compare($a, $b);
            if ($r !== false) return $r;
        }

        if ( function_exists('mb_strtolower') ) {
            return strcmp(mb_strtolower($a, 'UTF-8'), mb_strtolower($b, 'UTF-8'));
        }

        return strcasecmp($a, $b); // last fallback, ASCII-ish
    }
}

if ( !function_exists('sosidee_str_remove') ) {
    function sosidee_str_remove( $search, $subject ) {
        if ( is_array($search) ) {
            $blank = array_fill(0, count($search), '');
        } else {
            $blank = '';
        }
        return str_replace($search, $blank, $subject);
    }
}

if ( ! function_exists( 'sosidee_check_path_separator' ) ) {
    // Normalizes path separators, removes duplicates, and adapts the path to the current operating system.
    function sosidee_check_path_separator( $path ) {
        $ret = preg_replace('/\/+/', '/', str_replace('\\', '/', $path));
        if ( DIRECTORY_SEPARATOR !== '/' ) {
            return str_replace('/', DIRECTORY_SEPARATOR, $ret);
        }
        return $ret;
    }
}

if ( ! function_exists( 'sosidee_check_folder_separator' ) ) {
    // Normalizes a path and ensures it always ends with the correct directory separator.
    function sosidee_check_folder_separator( $path ) {
        return rtrim( sosidee_check_path_separator($path) , DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}

if ( ! function_exists( 'sosidee_append_url_separator' ) ) {
    function sosidee_append_url_separator( $url ) {
        return rtrim( $url , '/') . '/';
    }
}

if ( ! function_exists( 'sosidee_upload_dir' ) ) {
    function sosidee_upload_dir() {
        $ret = false;
        $root = wp_upload_dir();
        if ( $root['error'] === false && isset($root['baseurl']) && isset($root['basedir']) ) {
            $ret = [
                 'path' => sosidee_check_folder_separator( $root['basedir'] )
                ,'url' => sosidee_append_url_separator( $root['baseurl'] )
            ];
        }
        return $ret;
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

if ( ! function_exists( 'sosidee_get_host' ) ) {
    // returns the domain in lowercase, including any subdomain except “www”, and removing any WordPress installation path
    function sosidee_get_host() {
        $url = home_url();
        $host = wp_parse_url( $url, PHP_URL_HOST );

        if ( empty( $host ) ) {
            return '';
        }

        $host = strtolower( $host );
        if ( strpos( $host, 'www.' ) === 0 ) {
            $host = substr( $host, 4 );
        }

        return $host;
    }
}


if ( ! function_exists( 'sosidee_get_query_var' ) ) {
    // retrieves query variables with fallback to URL parameters, default values, and automatic sanitization.
    function sosidee_get_query_var($var, $def_value = null) {
        $ret = get_query_var($var, null);
        if ( is_null($ret) && isset( $_GET[$var] ) ) {
            $ret = wp_unslash($_GET[$var]);
        }
        if ( is_null($ret) ) {
            $ret = $def_value;
        }
        if ( is_string($ret) ) {
            $ret = sanitize_text_field($ret);
        }
        return $ret;
    }
}

if ( ! function_exists( 'sosidee_add_query_arg' ) ) {
    /**
     * Adds query parameters to the given URL. The URL is preserved as-is.
     * Only the new query parameters are encoded using urlencode_deep().
     */
    function sosidee_add_query_arg( ...$args ) {
        // forma: sosidee_add_query_arg( array $params, string $url )
        if ( is_array($args[0]) ) {
            $params = $args[0];
            $url    = $args[1] ?? '';
        } else {
            // forma: sosidee_add_query_arg( string $key, string $value, string $url )
            $key   = $args[0] ?? null;
            $value = $args[1] ?? null;
            $url   = $args[2] ?? '';

            $params = [];
            if ( $key !== null ) {
                $params[$key] = $value;
            }
        }

        $params = urlencode_deep($params);

        foreach ( $params as $key => $value ) {
            $sep = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $sep . $key . '=' . $value;
        }

        return $url;
    }
}


if ( ! function_exists( 'sosidee_json_decode' ) ) {
    function sosidee_json_decode($data, $def_value = null, $as_array = false) {
        $ret = json_decode($data,  $as_array);
        return (json_last_error() == JSON_ERROR_NONE) ? $ret : $def_value;
    }
}

if ( ! function_exists( 'sosidee_is_base64' ) ) {
    function sosidee_is_base64($data) {
        if (!is_string($data) || $data === '') {
            return false;
        }
        $s = preg_replace('/\s+/', '', $data); // remove whitespace
        if ($s === '') {
            return false;
        }
        $s = strtr($s, '-_', '+/'); // normalize base64url -> base64 standard

        $eq = strpos($s, '=');
        if ($eq !== false && $eq < strlen($s) - 2) {
            return false; // symbol = only at the end
        }

        $pad = strlen($s) % 4;
        if ($pad === 1) { // impossible in a valid base64
            return false;
        }
        if ($pad !== 0) {
            $s .= str_repeat('=', 4 - $pad); // normalize padding
        }

        $decoded = base64_decode($s, true);
        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $s;
    }
}


if ( ! function_exists( 'sosidee_check_id' ) ) {
    function sosidee_check_id($value) {
        return \str_replace(' ', '-', strtolower(trim($value)));
    }
}

if ( ! function_exists( 'sosidee_dirname' ) ) {
    function sosidee_dirname( $path, $levels = 1 ) {
        $ret = '';
        if ( version_compare( phpversion(), '7.0.0') >= 0 ) {
            $ret = dirname($path, $levels);
        } else {
            if ($levels > 1){
                $ret = dirname( sosidee_dirname( $path, --$levels ) );
            }else{
                $ret = dirname( $path );
            }
        }
        return $ret; //str_replace('/', DIRECTORY_SEPARATOR, $ret);
    }
}
if ( ! function_exists('sosidee_log') ) {
    /***
     * A lightweight debug logger that writes sanitized messages to the WordPress debug log,
     * automatically prefixing entries with the calling plugin or module name.
     * */
    function sosidee_log( $log, $context = '' ) {
        if ( ! ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) ) {
            return;
        }

        $plug = '?';
        $context = $context !== '' ? rtrim((string)$context) . ' ' : '';

        $trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? $trace[0] ?? null;

        if ( isset($caller['file']) ) {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $caller['file']);

            $plugin_dir = str_replace(DIRECTORY_SEPARATOR, '/', WP_PLUGIN_DIR) . '/';
            $mu_dir = defined('WPMU_PLUGIN_DIR')
                ? str_replace(DIRECTORY_SEPARATOR, '/', WPMU_PLUGIN_DIR) . '/'
                : null;
            $root = str_replace(DIRECTORY_SEPARATOR, '/', ABSPATH);

            $path_l = strtolower($path);
            $root_n = rtrim($root, '/') . '/';
            $root_l = strtolower($root_n);

            $k = 0;

            if ( strpos($path, $plugin_dir) !== false ) {
                $k = strpos($path, $plugin_dir) + strlen($plugin_dir);
            } elseif ( $mu_dir && strpos($path, $mu_dir) !== false ) {
                $k = strpos($path, $mu_dir) + strlen($mu_dir);
            } elseif ( str_starts_with($path_l, $root_l) ) {
                $k = strlen($root_n);
            }

            $rel = substr($path, $k);
            $m = strpos($rel, '/');
            $plug = ($m !== false) ? substr($rel, 0, $m) : $rel;
        }

        $title = '[' . $plug . '] ' . $context;

        if ( $log instanceof \Throwable ) {
            error_log(
                $title .
                $log->getMessage() .
                ' in ' . $log->getFile() .
                ':' . $log->getLine() . PHP_EOL .
                $log->getTraceAsString()
            );
        } elseif ( is_array($log) || is_object($log) ) {
            error_log( $title . print_r($log, true) );
        } else {
            error_log( $title . (string)$log );
        }
    }

}

if ( ! function_exists( 'sosidee_is_rest' ) ) {
    // Determines whether the current request is a WordPress REST API call, using multiple fallback checks to handle different routing scenarios.
    function sosidee_is_rest() {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }
        if ( !empty( $_GET['rest_route']) ) {
            return true;
        }
        if ( isset($_SERVER['REQUEST_URI']) ) {
            return strpos( wp_unslash($_SERVER['REQUEST_URI']), trailingslashit( rest_get_url_prefix() ) ) !== false;
        } else {
            return false;
        }
    }
}

if ( ! function_exists( 'sosidee_is_local' ) ) {
    function sosidee_is_local() {
        $ret = false;
        if ( ! isset($_SERVER['REMOTE_ADDR']) ) {
            return false;
        }
        $remote_addr = wp_unslash($_SERVER['REMOTE_ADDR']);
        return substr($remote_addr, 0, 4) === '127.' || $remote_addr === '::1';
    }
}

if ( ! function_exists( 'sosidee_is_dev' ) ) {
    function sosidee_is_dev() {
        $file = realpath(ABSPATH) . DIRECTORY_SEPARATOR . 'sos_identifier.txt';
        return file_exists($file) && trim(file_get_contents($file)) === 'SOS-DEV';
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
        return new \DateTimeImmutable( 'now', wp_timezone() );
    }
}
if ( ! function_exists( 'sosidee_server_datetime' ) ) {
    /**
     * @deprecated use sosidee_current_datetime();
     */
    function sosidee_server_datetime() {
        sosidee_log('sosidee_server_datetime() is deprecated: use sosidee_current_datetime().');
        if ( function_exists( 'wp_timezone' ) ) {
            return new \DateTime('now', wp_timezone());
        } else {
            return new \DateTime('now', new \DateTimeZone( date_default_timezone_get() ) );
        }
    }
}

if ( ! function_exists( 'sosidee_time' ) ) {
    // Returns the number of seconds elapsed since January 1 of a given year, optionally encoded as a base-36 string.
    function sosidee_time($year = 2000, $base36 = false) {
        if ( !$base36 ) {
            return current_time( 'timestamp' ) - strtotime("$year-01-01 00:00:00");
        } else {
            return base_convert( current_time( 'timestamp' ) - strtotime("$year-01-01 00:00:00"), 10, 36);
        }
    }
}
if ( ! function_exists( 'sosidee_sanitize_query_key' ) ) {
    // allows only letters, numbers, underscores and hyphens
    function sosidee_sanitize_query_key( $key ) {
        $key = sanitize_text_field( wp_unslash( $key ) );
        $key = trim( $key );
        if ( $key !== ''
            && preg_match( '/^[A-Za-z0-9_-]+$/', $key )
            && preg_match( '/[A-Za-z0-9]/', $key ) ) {
            return $key;
        }
        return '';
    }
}

if ( ! function_exists( 'sosidee_sanitize_recursive' ) ) {
    // Recursively sanitizes arrays, objects, and JSON strings, cleaning all string values while preserving the original data structure.
    function sosidee_sanitize_recursive( $data ) {
        if ( is_array($data) ) {
            // if array then sanitize each element recursively
            return array_map('sosidee_sanitize_recursive', $data);
        }

        if ( is_object($data) ) {
            // if object then sanitize each property recursively
            foreach ( $data as $key => $value ) {
                $data->$key = sosidee_sanitize_recursive($value);
            }
            return $data;
        }

        if ( is_string($data) ) {
            $decoded_data = json_decode($data, true);
            if ( is_array($decoded_data) || is_object($decoded_data) ) {
                $sanitized_data = sosidee_sanitize_recursive($decoded_data);
                return wp_json_encode($sanitized_data);  // re-encoded to JSON
            }
            return sanitize_text_field($data);
        }

        // return original value for numbers, booleans, etc.
        return $data;
    }
}

if ( ! function_exists( 'sosidee_allowed_html' ) ) {
    function sosidee_allowed_html() {
        $ret = wp_kses_allowed_html();

        $tags = [
            'a', 'b', 'br', 'button', 'caption', 'code', 'col', 'colgroup'
            , 'data', 'datalist', 'div', 'em', 'form'
            , 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
            , 'hr', 'i', 'img', 'input'
            , 'label', 'legend', 'li', 'nav'
            , 'ol', 'optgroup', 'option'
            , 'p', 'pre', 'section', 'select', 'span', 'strong'
            //, 'script' // to be removed
            , 'table', 'tbody', 'td', 'textarea', 'th', 'thead', 'title', 'tr'
            , 'ul', 'video'
        ];

        $attrs = [
            'accept', 'action', 'alt', 'checked', 'class', 'cols', 'colspan'
            , 'disabled', 'download', 'enctype'
            , 'for', 'form', 'height', 'href', 'id'
            , 'label', 'lang', 'max', 'maxlength', 'method', 'min', 'name'
            , 'onblur', 'onchange', 'onclick', 'onfocus', 'onload', 'onsubmit'
            , 'readonly', 'rel', 'rows', 'rowspan'
            , 'scope', 'selected', 'size', 'span', 'src', 'step', 'style'
            , 'usemap', 'target', 'title', 'type', 'value', 'width', 'wrap'
        ];


        foreach ( $tags as $tag ) {
            if ( !isset($ret[$tag]) ) {
                $ret[$tag] = [];
            }
            foreach ( $attrs as $attr ) {
                if ( !isset($ret[$tag][$attr]) ) {
                    $ret[$tag][$attr] = true;
                }
            }
        }
        return $ret;
    }
}

if ( ! function_exists( 'sosidee_kses' ) ) {
    function sosidee_kses( $value ) {

        $allowed = sosidee_allowed_html();

        // estende la whitelist delle proprietà CSS consentite
        $cb = function( $styles ) {
            if ( ! in_array( 'display', $styles, true ) ) {
                $styles[] = 'display';
            }
            return $styles;
        };

        add_filter( 'safe_style_css', $cb, 10, 1 );

        try {
            return wp_kses( $value, $allowed );
        } finally {
            remove_filter( 'safe_style_css', $cb, 10 );
        }
    }
}

if ( ! function_exists( 'sosidee_echo_kses' ) ) {
    function sosidee_echo_kses( $value ) {
        echo sosidee_kses( $value );
    }
}
