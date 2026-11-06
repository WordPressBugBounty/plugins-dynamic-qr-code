<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic\Status;


trait TAddon
{
    use TBase;


    protected $main = null; // wrapper del plugin base (usato nel plugin addon)

    protected $license = null; // gestisce la licenza (usato nel plugin addon)


    //public $versionMin = '';    // nella classe del plugin addon definisce la versione minima richiesta per il plugin base
                                // nella classe del plugin base definisce la versione minima richiesta per il plugin addon

    //protected $mainKey = ''; // usato nel plugin pro
    public $compatible = false; // usato nel plugin pro


    /*
    protected function setAddon1( $class = Addon::class ) {
        if ( ! class_exists( $class ) ) {
            sosidee_log("TAddon::setAddon() - class '{$class}' des not exist.");
            if ( is_admin() && sosidee_is_dev() ) {
                self::msgErr("TAddon::setAddon() - class '{$class}' des not exist.");
            }
        }
        $this->addon = new $class();
        if ( !has_action('plugins_loaded', [$this, 'checkPlugin']) ) {
            add_action( 'plugins_loaded', [$this->addon, 'checkPlugin'] );
        }
    }
    */

    /*
    public function _registerCheckAddonPluginFlag() {
        // just to prevent a double registration
    }
    */

    public function isCompatible() {
        return $this->compatible;
    }

    /*
    public function addonPluginChecked() {
        // sovrascrivere nel plugin base per eseguire azioni dopo il controllo dell'oggetto addon
        sosidee_log("TAddon::addonPluginChecked() - this method should be overridden.");
    }

    public function checkPluginPro1() {
        $addonVersion = $this->infoData['SosAddonVersion'] ?? '';
        if ( empty( $addonVersion ) ) {
            //problema!
            //message
            return;
        }
        $infos = [
            'class' => $this::class,
            'addon_version_min' => $addonVersion,
        ];
        $result = $this->applyFilter( '_check_addon_pro', false, $infos );
        if ( $result === false ) {
            return; // addon did not answer
        }
        if ( !is_array($result) || empty($result) ) {
            sosidee_log("TAddon::checkPluginPro() - '_check_addon_pro' filter returned an invalid value.");
            return;
        }

        $message = $result['message'] ?? '';
        $error = $result['error'] ?? true;

        if ( !empty( $message ) ) {
            if ( $error ) {
                self::msgErr( $message );
            } else {
                self::msgWarn( $message );
            }
        } else {
            if ( $error ) {
                sosidee_log("TAddon::checkPluginPro() - '_check_addon_pro' filter returned error=true without a message.");
                self::msgErr( 'The addon plugin did not behave as expected.' );
            }
        }

        //$compatible = $result['compatible'] ?? false;

        return;
    }
    */

    // ===================================================================================================
    // chiamate dal plugin addon

    public function getMainMinVersion() {
        return $this->infoData['SosMainVersion'] ?? '';
    }

    private function lookForMainPlugin( $code, $class = Main::class ) {
        if ( ! class_exists( $class ) ) {
            sosidee_log("TAddon::lookForMainPlugin() - class '{$class}' does not exist.");
            if ( sosidee_is_dev() && is_admin() ) {
                self::msgErr("TAddon::lookForMainPlugin() - class '{$class}' does not exist.");
            }
            return false;
        }

        $key = $this->checkKey( $code );
        $hook = $this->getSpecificHook('_check_addon_pro', $key);
        if ( !has_filter($hook, [$this, 'checkVersionCompatibility']) ) {
            add_filter( $hook, [$this, 'checkVersionCompatibility'], 10, 2 );
        }
        if ( !has_action('plugins_loaded', [$this, 'versionChecked']) ) {
            add_action('plugins_loaded', [$this, 'versionChecked'], 11);
        }
        $hook = $this->getSpecificHook('_get_addon_info_data', $key);
        if ( !has_filter($hook, [$this, 'getInfoData']) ) {
            add_filter( $hook, [$this, 'getInfoData'] );
        }

        $this->main = new $class();
    }

    public function checkVersionCompatibility( $_, $infos ) {
        $compatible = false;
        $result = [
            'error' => true,
            'compatible' => $compatible,
            'message' => '',
        ];

        $native_class = $infos['class'] ?? '';
        if ( !class_exists($native_class) ) {
            $message = "The main plugin did not behave as expected.";
            self::msgErr($message);
            self::pageErr($message);
            sosidee_log("TAddon::checkVersionCompatibility() - Invalid parameter(s): native_class='$native_class'.");
            return $result;
        }
        $this->main->setNative( $native_class );

        $addon_version_requested = $infos['addon_version_min'] ?? '';
        if ( empty($addon_version_requested) ) {
            $message = "The main plugin did not behave as expected.";
            self::msgErr($message);
            self::pageErr($message);
            sosidee_log("TAddon::checkVersionCompatibility() - Invalid parameter: addon_version_requested='$addon_version_requested'.");
            return $result;
        }

        $main_version_requested = $this->getMainMinVersion();
        if ( empty($main_version_requested) ) {
            $message = "This plugin did not behave as expected.";
            self::msgErr($message);
            self::pageErr($message);
            sosidee_log("TAddon::checkVersionCompatibility() - Invalid parameter: main_version_requested='$main_version_requested'.");
            return $result;
        }

        $result['error'] = false;

        $addon_compatible = version_compare($this->version, $addon_version_requested, '>=');
        if ( !$addon_compatible ) {
            $message = sprintf(
                "The %s version (%s) is outdated: version %s (or higher) is required by %s",
                $this->name,
                $this->version,
                $addon_version_requested,
                $this->main->getName(),
            );
            self::msgWarn($message); // qualsiasi pagina del plugin main che contiene msgHtml()
            self::pageWarn($message); // pagina dei plugins, in alto
            self::plugWarn($message, $this->basename); // pagina dei plugins, sotto la riga dell'add-on
        }

        $main_compatible = version_compare($this->main->getVersion(), $main_version_requested, '>=');
        if ( !$main_compatible ) {
            $message = sprintf(
                "The %s version (%s) is outdated: version %s (or higher) is required by %s",
                $this->main->getName(),
                $this->main->getVersion(),
                $main_version_requested,
                $this->name,
            );
            $result['message'] = $message;
        }

        $compatible = $addon_compatible && $main_compatible;

        $this->compatible = $compatible;
        $this->main->setCompatible( $compatible );

        return $result;
    }

    public function getInfoData( $_ ) {
        return [ 'addon_version' => $this->version, 'main_version_min' => $this->getMainMinVersion() ];
    }


    // usato nelle pagine admin e altrove
    public function getMain() {
        if ( $this->main instanceof Main ) {
            return $this->main;
        } else {
            return null;
        }
    }

    /*
    protected function setMain1( $code, $class = Main::class ) {
        if ( ! class_exists( $class ) ) {
            sosidee_log("TAddon::setMain() - class '{$class}' does not exist.");
            if ( is_admin() && sosidee_is_dev() ) {
                self::msgErr("TAddon::setMain() - class '{$class}' does not exist.");
            }
            return false;
        }
        $this->main = new $class();

        $hook = "{$code}/{$code}.php/first_contact";
        add_filter( $hook, [$this->main, 'checkPlugin1'], 10, 2 );
        add_action('plugins_loaded', [$this, 'mainPluginChecked1'], 11);
    }
    */

    /*
    public function _registerCheckMainPluginFlag() {
        // just to prevent a double registration
    }
    */

    public function versionChecked() {
        // sovrascrivere nel plugin addon per eseguire azioni dopo il controllo dell'oggetto main
        sosidee_log("TAddon::versionChecked() - this method should be overridden.");
    }

    // ===================================================================================================

    // chiamata dal plugin pro per impostare il pid della licenza ed il controllo remoto via cron
    protected function initializeLicense( $pid ) {
        $this->license = new Addon\License( $pid );

        $hook = 'check_remote_license_' . self::plugin()->key;
        if ( ! has_action( $hook, [ $this->license, 'verify' ] ) ) {
            add_action( $hook, [ $this->license, 'verify' ] );
        }
        if ( ! wp_next_scheduled( $hook ) ) {
            wp_schedule_event( time(), 'daily', $hook );
        }
    }


    //CONTROLLARE SE USATI:
    /*
    public function hasLicense() {
        return $this->license instanceof Addon\License;
    }

    public function getLicense() {
        return $this->license;
    }

    public function isLicenseValid() {
        if ( $this->hasLicense() && $this->license->isValid() ) {
            return true;
        }
        return false;
    }
    */


}