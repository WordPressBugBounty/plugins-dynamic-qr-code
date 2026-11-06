<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TMain
{
    use TBase;

    public function isPro() {
        if ( is_callable( [ $this, 'applyFilter' ] ) ) {
            return $this->applyFilter('_is_plugin_pro', false);
        } else {
            return false;
        }
    }

    public function checkAddon() {
        if ( !has_action( 'plugins_loaded', [$this, 'checkPluginPro'] ) ) {
            add_action('plugins_loaded', [$this, 'checkPluginPro']);
        }
    }


    public function getAddonMinVersion() {
        return $this->infoData['SosAddonVersion'] ?? '';
    }

    public function checkPluginPro() {
        $addonVersion = $this->getAddonMinVersion();
        if ( empty( $addonVersion ) ) {
            sosidee_log("TAddon::checkPluginPro() - getAddonMinVersion() returned an empty value.");
            if ( is_callable( [ static::class, 'msgErr' ] ) ) {
                self::msgErr( 'The addon plugin did not behave as expected.' );
            }
            return;
        }
        $infos = [
            'class' => $this::class,
            'addon_version_min' => $addonVersion,
        ];
        if ( is_callable( [ $this, 'applyFilter' ] ) ) {
            $result = $this->applyFilter( '_check_addon_pro', false, $infos );
            if ( $result === false ) {
                return; // addon did not answer
            }
        } else {
            sosidee_log("TAddon::checkPluginPro() - method applyFilter() not callable.");
            return;
        }

        if ( !is_array($result) || empty($result) ) {
            sosidee_log("TAddon::checkPluginPro() - '_check_addon_pro' filter returned an invalid value.");
            return;
        }

        $message = $result['message'] ?? '';
        $error = $result['error'] ?? true;

        if ( !empty( $message ) ) {
            if ( $error ) {
                if ( is_callable( [ static::class, 'msgErr' ] ) ) {
                    self::msgErr( $message ); // qualsiasi pagina del plugin main che contiene msgHtml()
                }
                if ( is_callable( [ static::class, 'pageErr' ] ) ) {
                    self::pageErr( $message );  // pagina dei plugins, in alto
                }
                if ( is_callable( [ static::class, 'plugErr' ] ) ) {
                    self::plugErr($message, $this->basename); // pagina dei plugins, sotto la riga dell'add-on
                }
            } else {
                if ( is_callable( [ static::class, 'msgWarn' ] ) ) {
                    self::msgWarn( $message ); // qualsiasi pagina del plugin main che contiene msgHtml()
                }
                if ( is_callable( [ static::class, 'pageWarn' ] ) ) {
                    self::pageWarn( $message );  // pagina dei plugins, in alto
                }
                if ( is_callable( [ static::class, 'plugWarn' ] ) ) {
                    self::plugWarn($message, $this->basename); // pagina dei plugins, sotto la riga dell'add-on
                }

            }
        } else {
            if ( $error ) {
                sosidee_log("TAddon::checkPluginPro() - '_check_addon_pro' filter returned error=true without a message.");
                if ( is_callable( [ static::class, 'msgErr' ] ) ) {
                    self::msgErr( 'The addon plugin did not behave as expected.' );
                }
            }
        }

    }

}