<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Request
{
    /**
     * Parses the HTTP_ACCEPT_LANGUAGE header to get a sorted list of preferred languages
     * @return array|string an array of 2-letter language codes sorted by preference, or '*' if not available
     */
    public static function getBrowserLanguages() {
        if ( empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
            return '*';
        }

        $header = sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE']));
        $languages = [];

        // parsing dell'header - Esempio: "fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5"
        $accepted_languages = preg_split('/,\s*/', $header);

        foreach ($accepted_languages as $lang_entry) {
            // separa il codice lingua dai parametri di qualità
            $parts = preg_split('/;\s*/', $lang_entry);

            if ( empty($parts[0]) ) {
                continue;
            }

            // estrae il codice base della lingua (es. 'en' da 'en-US')
            $lang_code_parts = explode('-', $parts[0]);
            $lang_code = strtolower($lang_code_parts[0]);

            // ignora codici non validi o il jolly.
            $lang_code = preg_replace('/[^a-z]/', '', $lang_code);
            if ( strlen($lang_code) !== 2 || $lang_code === '*' ) {
                continue;
            }

            // gestisce il fattore di qualità q (default 1.0)
            $quality = 1.0;
            if ( isset($parts[1]) && str_starts_with($parts[1], 'q=') ) {
                $q_value = substr($parts[1], 2);
                if ( is_numeric($q_value) ) {
                    $quality = (float) $q_value;
                }
            }

            // memorizza la lingua con la sua qualità, evitando duplicati con qualità inferiore
            if ( !isset($languages[$lang_code]) || $languages[$lang_code] < $quality ) {
                $languages[$lang_code] = $quality;
            }
        }

        // ordina le lingue in base alla qualità (preferenza), in ordine decrescente
        arsort($languages, SORT_NUMERIC);

        // estrae solo i codici di lingua ordinati
        $sorted_codes = array_keys($languages);

        return !empty($sorted_codes) ? $sorted_codes : '*';
    }

    public static function getBrowserMainLanguage() {
        $languages = self::getBrowserLanguages();
        if ($languages === '*') {
            return '';
        }
        return $languages[0] ?? '';
    }

    /*
    public static function getServerIp() {
        $cache_key = 'sosidee_server_ip';

        $cached_ip = get_transient($cache_key);
        if ( false !== $cached_ip ) {
            return $cached_ip;
        }

        $url = 'https://api.ipify.org?format=json';

        $response = wp_remote_get($url, [
            'timeout' => 10, // Imposta un timeout di 10 secondi
        ]);

        if ( is_wp_error($response) ) {
            sosidee_log('Request::getServerIp() - API IPify error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = Json::decode($body);
        if ( is_null($data) ) {
            sosidee_log('Request::getServerIp() - invalid response body: ' . (string)$body);
            return false;
        }
        if ( empty($data->ip) ) {
            sosidee_log('Request::getServerIp() - IP string is empty.');
            return false;
        }

        if ( filter_var($data->ip, FILTER_VALIDATE_IP) ) {
            set_transient($cache_key, $data->ip, 12 * HOUR_IN_SECONDS);
            return $data->ip;
        } else {
            sosidee_log('Request::getServerIp() - invalid IP string: ' . (string)$data->ip);
            return false;
        }

    }
    */

    public static function getClientIp() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : false; // fallback

        $proxy_headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_REAL_IP',           // Nginx, Varnish, etc.
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        foreach ( $proxy_headers as $header ) {
            if ( isset($_SERVER[$header]) ) {
                $header_value = sanitize_text_field(wp_unslash($_SERVER[$header]));
                $ips = explode(',', $header_value);
                $candidate_ip = trim($ips[0]); //il primo della lista è tipicamente l'IP del client originale
                if (filter_var($candidate_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $ip = $candidate_ip;
                    break;
                }
            }
        }

        if ( $ip === '::1' || $ip === '127.0.0.1' ) {
            $ip = Http::getServerIp();
        }

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        } else {
            return false;
        }
    }


    public static function getPreferredServerPrivateIp(): string
    {
        /*
         * Se la richiesta arriva già tramite un IP LAN,
         * quello è sicuramente l'indirizzo corretto.
         */
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);

            if (Http::isPrivateIpv4($host)) {
                return $host;
            }
        }

        /*
         * Apache potrebbe avere già individuato l'interfaccia corretta.
         */
        if (
            !empty($_SERVER['SERVER_ADDR']) &&
            Http::isPrivateIpv4($_SERVER['SERVER_ADDR'])
        ) {
            return $_SERVER['SERVER_ADDR'];
        }

        if (!function_exists('net_get_interfaces')) {
            return '';
        }

        $interfaces = @net_get_interfaces();

        if (!is_array($interfaces)) {
            return '';
        }

        $candidates = [];

        foreach ( $interfaces as $name => $interface ) {
            if (
                empty($interface['up']) ||
                empty($interface['unicast']) ||
                !is_array($interface['unicast'])
            ) {
                continue;
            }

            $description = $interface['description'] ?? '';
            $label = strtolower($name . ' ' . $description);

            /*
             * Gli adattatori virtuali non devono essere usati
             * per costruire un URL raggiungibile dal telefono.
             */
            if (
                preg_match(
                    '/loopback|virtual|vethernet|hyper-v|wsl|docker|' .
                    'vmware|virtualbox|vbox|tailscale|zerotier|' .
                    'vpn|tunnel|tap|tun/i',
                    $label
                )
            ) {
                continue;
            }

            $score = 0;

            if (preg_match('/wi-?fi|wireless|wlan/i', $label)) {
                $score = 100;
            } elseif (preg_match('/ethernet|lan/i', $label)) {
                $score = 50;
            }

            foreach ($interface['unicast'] as $address) {
                $ip = $address['address'] ?? '';

                if (!Http::isPrivateIpv4($ip)) {
                    continue;
                }

                $candidates[] = [
                    'ip'    => $ip,
                    'score' => $score,
                ];
            }
        }

        if (!$candidates) {
            return '';
        }

        usort(
            $candidates,
            static fn(array $a, array $b): int =>
                $b['score'] <=> $a['score']
        );

        return $candidates[0]['ip'];
    }

    /*
    public static function isPrivateIpv4($ip): bool
    {
        if (
            !is_string($ip) ||
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ) {
            return false;
        }

        $value = ip2long($ip);

        if ($value === false) {
            return false;
        }

        return
            ($value >= ip2long('10.0.0.0') &&
                $value <= ip2long('10.255.255.255')) ||

            ($value >= ip2long('172.16.0.0') &&
                $value <= ip2long('172.31.255.255')) ||

            ($value >= ip2long('192.168.0.0') &&
                $value <= ip2long('192.168.255.255'));
    }
    */

    /*
    public static function getServerLocalIp_old() {
        if (
            !empty($_SERVER['SERVER_ADDR']) &&
            $_SERVER['SERVER_ADDR'] !== '127.0.0.1' &&
            $_SERVER['SERVER_ADDR'] !== '::1'
        ) {
            return $_SERVER['SERVER_ADDR'];
        }

        $host = gethostname();
        if ( $host ) {
            $ip = gethostbyname($host);
            if ( filter_var($ip, FILTER_VALIDATE_IP) && $ip !== '127.0.0.1' ) {
                return $ip;
            }
        }

        if ( !empty($_SERVER['HTTP_HOST']) ) {
            $host = $_SERVER['HTTP_HOST'];
            $host = preg_replace('/:\d+$/', '', $host); // rimuove la porta
            if ( filter_var($host, FILTER_VALIDATE_IP) ) {
                return $host;
            }
        }

        return '';
    }
    */

}