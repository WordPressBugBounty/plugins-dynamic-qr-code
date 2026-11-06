<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\vendor\DeviceDetector\ClientHints;
use SOSIDEE_DYNAMIC_QRCODE\vendor\DeviceDetector\DeviceDetector;
use SOSIDEE_DYNAMIC_QRCODE\vendor\DeviceDetector\Yaml\Symfony as SymfonyYaml;

class Mobile
{

    private static $native = null;

    private static function instance() {
        if (is_null(self::$native)) {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ch = ClientHints::factory($_SERVER);
            $dd = new DeviceDetector($ua, $ch);
            $dd->setYamlParser(new SymfonyYaml());
            $dd->parse();
            self::$native = $dd;
        }
        return self::$native;
    }

    private static function deviceName(): string {
        $name = self::instance()->getDeviceName();
        return is_string($name) ? strtolower($name) : '';
    }
    private static function client(): array {
        return self::instance()->getClient() ?: [];
    }
    private static function osInfo(): array {
        return self::instance()->getOs() ?: [];
    }

    public static function is() {
        return self::instance()->isMobile() || self::instance()->isTablet();
    }

    public static function smartphone() {
        return self::instance()->isSmartphone();
    }

    public static function phone() {
        return self::instance()->isFeaturePhone();
    }

    public static function tablet() {
        return self::instance()->isTablet();
    }

    public static function ios() {
        return strcasecmp(self::os(), 'iOS') === 0;
    }
    public static function android() {
        return strcasecmp(self::os(), 'Android') === 0;
    }
    public static function os() {
        return self::osInfo()['name'] ?? '';
    }

    public static function browser() {
        return self::instance()->isBrowser();
    }

    public static function app() {
        return self::instance()->isMobileApp();
    }

    public static function bot() {
        return self::instance()->isBot();
    }

    public static function browserName() {
        $client = self::instance()->getClient();
        return $client['name'] ?? null;
    }

    public static function realBrowser( $threshold = RealBrowserThreshold::BALANCED ) {
        $sfm = (string) ($_SERVER['HTTP_SEC_FETCH_MODE'] ?? '');
        $sfd = (string) ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '');
        $sfmL    = strtolower($sfm);
        $sfdL    = strtolower($sfd);

        if ( $sfmL !== '' && $sfdL !== '' ) {
            if ( $sfmL !== 'navigate' ) { return false; }
            if ( $sfdL !== 'document' ) { return false; }
        }

        $ua             = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $accept         = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $acceptLanguage = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $uir            = (string) ($_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ?? '');
        $cookie         = (string) ($_SERVER['HTTP_COOKIE'] ?? '');
        $acceptL = strtolower($accept);

        $score = 0;

        // segnali tipici di navigazione "document"
        if ( $uir === '1' ) { $score += 3; }
        if ( $acceptLanguage !== '' ) { $score += 1; }
        if ( $cookie !== '' ) { $score += 0; }

        if ( $accept !== '' ) {
            if ( strpos($accept, ';q=') !== false ) { $score += 1; }
            if ( strpos($acceptL, 'image/') !== false ) { $score += 1; }
            if ( strpos($acceptL, 'text/html') !== false ) { $score += 2; }
            else { $score -= 3; }
        } else {
            $score -= 1;
        }

        // UA e classificazione device detector (via Mobile)
        if ( $ua !== '' ) {

            // coerente con la tua logica precedente: bot non è "preview"
            if ( self::bot() ) { $score -= 3; }

            // se non è browser, abbassa fiducia
            if ( !self::browser() ) { $score -= 2; }

            // whitelist leggera, aumenta fiducia se è un browser noto
            $name = strtolower((string) self::browserName());
            if ( $name !== '' ) {
                $known = ['chrome','mobile safari','safari','firefox','edge','samsung browser','opera','chromium'];
                foreach ( $known as $k ) {
                    if ( str_contains($name, $k) ) { $score += 1; break; }
                }
            }
        } else {
            // senza UA è quasi sempre roba automatica
            $score -= 3;
        }

        // $score >= $threshold consideriamo navigazione reale
        return $score >= $threshold;
    }


    public static function toString() {
        $os   = self::osInfo();
        $cli  = self::client();
        $dev  = self::deviceName();
        $parts = array_filter([
            trim(($cli['name'] ?? '') . ' ' . ($cli['version'] ?? '')),
            trim(($os['name'] ?? '') . (isset($os['version']) ? ' ' . $os['version'] : '')),
            $dev ? "[$dev]" : null,
        ]);
        return implode(' on ', array_slice($parts, 0, 2)) . (isset($parts[2]) ? ' ' . $parts[2] : '');
    }

}