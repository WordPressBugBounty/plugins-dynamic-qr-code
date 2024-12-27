<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TAddon
{
    protected $plmVersionMin;

    protected $addonClass; // path in '\SOSIDEE_DYNAMIC_QRCODE\Addons\path' namespace

    protected $services;
    protected $checkAddon;

    public $addon;

    protected function resetAddons() {
        $this->addon = false;
        $this->checkAddon = false;
        $this->services = array();
        $this->addonClass = false;
    }

    // do not remove the line below
    /*** WPSAR:NOSUB:START ***/
    public function initializeAddons() { // viene chiamata in plugins_loaded
        if ( $this->checkAddon ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
            if ( $this->isPLMinstalled() ) {
                if ( $this->isPLMactive() ) {
                    $plmVersion = $this->getPLMversion();
                    if ( version_compare( $plmVersion, $this->plmVersionMin, '>=' ) ) {
                        if ( class_exists( '\SOSIDEE_PROLICMAN\SosPlugin') ) {
                            $plugin = \SOSIDEE_PROLICMAN\SosPlugin::instance();
                            $this->services = $plugin->getServices( $this->key );
                            if ( $this->services !== false ) {
                                $this->createAddon();
                            }
                        } else {
                            if ( is_admin() ) {
                                self::msgErr('A problem occurred while accessing the Plugin Pro License Manager.');
                            }
                            sosidee_log("TAddon.initializeAddons(): class SOSIDEE_PROLICMAN\SosPlugin not found.");
                        }
                    } else {
                        $msg = "Plugin Pro License Manager version ({$plmVersion}) is not the minimum required ({$this->plmVersionMin}).";
                        if ( is_admin() ) {
                            self::msgWarn($msg, true);
                        }
                        sosidee_log("TAddon.initializeAddons(): $msg");
                    }
                } else {
                    $msg ='Plugin Pro License Manager is not active.';
                    if ( is_admin() ) {
                        self::msgWarn($msg, true);
                    }
                    sosidee_log("TAddon.initializeAddons(): $msg");
                }
            }
        }
    }
    protected function createAddon() {
        if ( $this->addonClass !== false ) {
            $className = "\SOSIDEE_PROLICMAN\Addons\\{$this->addonClass}\Handler";
            if ( class_exists( $className ) ) {
                $this->addon = new $className();
                $this->isPro = true;
            } else {
                sosidee_log("TAddon.createAddon(): addon class not found ($className).");
            }
        }
    }

    // do not remove the line below
    /*** WPSAR:NOSUB:END ***/

    protected function isPLMinstalled() {
        $all_plugins = get_plugins();
        return isset($all_plugins['sos-prolicman/sos-prolicman.php']);
    }

    protected function isPLMactive() {
        return is_plugin_active('sos-prolicman/sos-prolicman.php');
    }

    protected function getPLMversion() {
        $all_plugins = get_plugins();
        $plugin_data = $all_plugins[ 'sos-prolicman/sos-prolicman.php' ];
        return $plugin_data['Version'] ?? '0.0.0';
    }

    public function hasAddon( $code = '' ) {
        $ret = $this->addon !== false && is_array( $this->services );
        if ( $ret && $code != '') {
            $ret = in_array( $code, $this->services );
        }
        return $ret;
    }

}