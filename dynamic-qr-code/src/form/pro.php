<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC\FORM;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SRC as SRC;

class Pro extends Base
{

    private const LAST_ACTION_ADDON_INSTALLED = 1;
    private const LAST_ACTION_ADDON_ACTIVATED = 2;
    private const LAST_ACTION_PLM_DISACTIVATED = 3;

    private $installer;
    private $addonData;
    private $plmData;

    public function __construct( $name = '', $callback = null ) {
        if ( empty( $name ) ) {
            $name = 'pro';
        }
        if ( is_null( $callback ) ) {
            $callback = [$this, 'onSubmit'];
        }
        parent::__construct( $name, $callback );

        $this->installer = new SOS_WP\Addon\Installer();
        $this->addonData = null;
        $this->plmData = null;

        //$this->loadAddon(); //TEST
    }

    private function loadPLM() {
        $this->plmData = SOS_WP\Addon\Repository::getProLicManPlugin();
        $status = $this->installer->getStatus( $this->plmData->basename );
        if ( ! is_array( $status ) || !isset($status['installed']) || !isset($status['activated']) ) {
            self::msgErr("A problem occurred while retrieving the status of {$this->plmData->name}.");
            return false;
        }
        $this->plmData->installed = $status['installed'];
        $this->plmData->activated = $status['activated'];
    }

    private function loadAddon() {
        $this->addonData = SRC\Repository::getAddon();
        $status = $this->installer->getStatus( $this->addonData->basename );
        if ( ! is_array( $status ) || !isset($status['installed']) || !isset($status['activated']) ) {
            self::msgErr("A problem occurred while retrieving the status of {$this->addonData->name}.");
            return false;
        }
        $this->addonData->installed = $status['installed'];
        $this->addonData->activated = $status['activated'];
    }

    protected function initialize() {
        if ( $this->isPosted() ) {
            return true;
        }

        $this->loadAddon();
        $this->loadPLM();

        $last_action = intval( sosidee_get_query_var(self::LAST_ACTION_QS, 0) );
        if ( $last_action > 0 ) {
            if ( $last_action === self::LAST_ACTION_ADDON_INSTALLED ) {
                sosidee_log("FORM/Pro::initialize() - Addon installed.");
            } elseif ( $last_action === self::LAST_ACTION_ADDON_ACTIVATED ) {
                sosidee_log("FORM/Pro::initialize() - Addon activated.");
            } elseif ( $last_action === self::LAST_ACTION_PLM_DISACTIVATED ) {
                sosidee_log("FORM/Pro::initialize() - PLM plugin deactivated.");
            }
        }
    }


    public function onSubmit() {
        $this->loadAddon();
        $this->loadPLM();

        if ( in_array($this->_action, [ 'install-addon', 'activate-addon', 'migrate-license' ]) ) {
            $plugin = $this->addonData;

            if ( $this->_action == 'install-addon' ) {
                if ( !$plugin->installed ) {
                    $result = $this->installer->install($plugin);
                    if ( is_array($result) ) {
                        if ( isset($result['success']) && $result['success'] === true ) {
                            self::msgOk("{$plugin->name} has been installed successfully.");
                            $res = $this->reload(self::LAST_ACTION_ADDON_INSTALLED);
                            if ( $res === false ) {
                                self::msgErr("A technical error occurred while retrieving the current page.");
                            }
                        } else {
                            self::msgErr("A problem occurred while installing {$plugin->name}.");
                        }
                        if (isset($result['messages']) && is_array($result['messages']) && count($result['messages']) > 0) {
                            self::msgWarn($result['messages']);
                        }
                    } else {
                        self::msgErr("A problem occurred while installing {$plugin->name}.");
                    }
                } else {
                    self::msgWarn("{$plugin->name} is already installed.");
                }
            } elseif ( $this->_action == 'activate-addon' ) {
                if ( !$plugin->installed ) {
                    self::msgErr("{$plugin->name} is not installed.");
                    return false;
                }

                if ( !$plugin->activated ) {
                    $result = $this->installer->activate($plugin);
                    if ( is_array($result) ) {
                        if ( isset($result['success']) && $result['success'] === true ) {
                            self::msgOk("{$plugin->name} has been activated successfully.");
                            $res = $this->reload(self::LAST_ACTION_ADDON_ACTIVATED);
                            if ($res === false) {
                                self::msgErr("A technical error occurred while retrieving the current page.");
                            }
                        } else {
                            self::msgErr("A problem occurred while activating {$plugin->name}.");
                        }
                        if (isset($result['messages']) && is_array($result['messages']) && count($result['messages']) > 0) {
                            self::msgWarn($result['messages']);
                        }
                    } else {
                        self::msgErr("A problem occurred while activating {$plugin->name}.");
                    }
                } else {
                    self::msgWarn("{$plugin->name} is already activated.");
                }

            }

        } elseif ( $this->_action == 'deactivate-plm' ) {

            $plugin = $this->plmData;
            if ( !$plugin->installed ) {
                self::msgInfo("{$plugin->name} is not installed.");
                return false;
            }
            if ( !$plugin->activated ) {
                self::msgInfo("{$plugin->name} is not activated.");
                return false;
            }

            $result = $this->installer->deactivate($plugin);
            if ( is_array($result) ) {
                if ( $result['success'] ) {
                    self::msgOk("{$plugin->name} has been deactivated successfully: now it can be uninstalled.");
                    $this->reload(self::LAST_ACTION_PLM_DISACTIVATED);
                } else {
                    $msg = count($result['messages']) > 0 ? '<br>' : '';
                    $msg .= implode('<br>', $result['messages']);
                    self::msgErr("An issue occurred while deactivating {$plugin->name}: $msg.");
                }
            } else {
                self::msgErr("An unhandled problem occurred while deactivating {$plugin->name}.");
            }

        } else {
            self::msgErr( "This action is not supported: '{$this->_action}'." );
            return false;
        }
    }


    public function htmlInstallAddon() {
        $plugin = $this->addonData;
        if ( !$plugin->installed ) {
            $value = 'install ' . ($plugin->name ?? '?');
            $this->htmlButton('install-addon', $value);
        }
    }

    public function htmlActivateAddon() {
        $plugin = $this->addonData;
        if ( $plugin->installed && !$plugin->activated ) {
            $value = 'activate ' . ($plugin->name) ?? '?';
            $this->htmlButton('activate-addon', $value, SOS_WP\DATA\FormButton::STYLE_SUCCESS);
        }
    }

    public function htmlAddonName() {
        $plugin = $this->addonData;
        echo ($plugin->name ? esc_html($plugin->name) : '?');
    }

    public function htmlAddonStatus() {
        $text = 'The plugin';
        $plugin = $this->addonData;
        if ( $plugin->installed ) {
            $text .= ' is installed';
            if ( $plugin->activated ) {
                $text .= ' and activated';
            } else {
                $text .= ' but <span style="font-weight: bold;">not activated</span>';
            }
            $text .= '.';

            $infos = self::plugin()->applyFilter('_get_addon_info_data', false);
            if ( $infos !== false ) {
                $min = self::plugin()->getAddonMinVersion();
                if ( version_compare($infos['addon_version'], $min, '<') || version_compare(self::plugin()->version, $infos['main_version_min'], '<') ) {
                    $text .= '<br><span style="font-weight: bold; color: red;">WARNING: the version is not compatible!</span>';
                }
            }
        } else {
            $text .= ' is <span style="font-weight: bold;">not installed</span>.';
        }
        echo wp_kses_post( $text, sosidee_allowed_html() );
    }

    public function htmlLinkToLicensePage() {
        self::plugin()->doAction('_add_link_page_license', $this);
        //if ( $this->addon->isReady() ) {
        //}
    }

    public function htmlDeactivateProLicman() {
        if ( $this->plmData->activated ) {
            echo '<hr>';
            echo '<p>';
                echo '<i title="The Pro License Manager plugin is no longer needed" class="material-icons" style="vertical-align: bottom; max-width: 1em; font-size: inherit; line-height: inherit; color: red;">warning</i>';
                echo '<strong> Please note that the Pro License Manager plugin is no longer required. We recommend deactivating it, since it may interfere with the Addon.</strong>';
            echo '</p>';
            $value = 'deactivate ' . $this->plmData->name;
            $this->htmlButton('deactivate-plm', $value, SOS_WP\DATA\FormButton::STYLE_WARNING);
            echo '<br>';
        }
    }

}