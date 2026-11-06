<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

//use Plugin_Upgrader_Skin;

class Installer
{
    private $plugins;

    public function __construct() {
        $this->plugins = array();
    }

    private function getBasename( $name ) {
        return Repository::getBasename( $name );
    }

    private function getPlugins() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( empty($this->plugins) ) {
            $this->plugins = get_plugins();
        }
        return $this->plugins;
    }

    private function isPluginActive($plugin_basename) {
        if (! function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active($plugin_basename);
    }

    public function isInstalled( $plugin ) {
        $plugins = $this->getPlugins();
        $plugin_basename = $this->getBasename( $plugin );
        return isset( $plugins[ $plugin_basename ] );
    }

    public function isActivated( $plugin ) {
        $plugin_basename = $this->getBasename( $plugin );
        return $this->isPluginActive( $plugin_basename );
    }

    public function getStatus( $plugin ) {
        if ( empty( $plugin ) ) {
            return false;
        }

        $plugin_basename = $this->getBasename( $plugin );

        $is_installed = $this->isInstalled( $plugin_basename );

        $is_active = $is_installed && $this->isPluginActive($plugin_basename);

        return [
            'installed' => $is_installed,
            'activated' => $is_active,
            'basename'  => $plugin_basename,
        ];
    }

    private function installByUrl( $url ) {
        if ( ! current_user_can( 'install_plugins' ) ) {
            return new \WP_Error( 'user_unauthorized', 'You are not authorized to perform this action.' );
        }
        if ( empty( $url ) ) {
            return new \WP_Error( 'invalid_url', 'Zip file URL is empty.' );
        }
        if ( ! class_exists( 'Plugin_Upgrader' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        if ( ! class_exists( 'Automatic_Upgrader_Skin' ) ) { // InstallerSkin estende Automatic_Upgrader_Skin
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php';
        }
        if ( ! function_exists( 'download_url' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $response = SOS_WP\Addon\Repository::check( $url );
        if ( ! is_array($response) ) {
            sosidee_log("Installer::installByUrl() - Repository::check($url) did not return an array.");
            return [
                'success' => false,
                'messages' => [ 'The plugin repository could not been reached because of an internal issue.' ],
            ];
        }

        if ( !isset($response['success']) || $response['success'] !== true ) {
            $log = implode(' ', array_map(
                fn($k, $v) => sprintf('%s=%s', $k, $v),
                array_keys($response),
                $response
            ));
            sosidee_log("Installer::installByUrl() - Repository::check($url) returned $log");
            $message = $response['messages'] ?? 'Unknown error.';
            return [
                'success' => false,
                'messages' => [ $message ],
            ];
        }

        $skin = new InstallerSkin();
        $handler = new \Plugin_Upgrader( $skin );

        if ( ! sosidee_is_dev() ) {
            $result = $handler->install( $url );
        } else {
            $result = new \WP_Error( 'install_forbidden', "Installation is forbidden at the development stage." );
        }

        if ( is_wp_error( $result ) ) {
            $skin_errors = $skin->get_captured_errors();
            if ( $skin_errors && $skin_errors->has_errors() ) {
                foreach ( $skin_errors->get_error_codes() as $code ) {
                    if ( ! $result->get_error_message( $code ) ) {
                        $result->add( $code, $skin_errors->get_error_message( $code ), $skin_errors->get_error_data( $code ) );
                    }
                }
            }
            return $result;
        }

        if ( $result !== true ) {
            $error = new \WP_Error();
            $skin_errors = $skin->get_captured_errors();

            if ( $skin_errors && $skin_errors->has_errors() ) {
                foreach ( $skin_errors->get_error_codes() as $code ) {
                    $error->add( $code, $skin_errors->get_error_message( $code ), $skin_errors->get_error_data( $code ) );
                }
            }

            if ( $result === null ) {
                $error->add( 'install_returned_null', 'Package download failed or package is invalid.' );
            } elseif ( $result === false ) {
                $error->add( 'install_returned_false', 'Installation failed (generic false result).' );
            } elseif ( ! $error->has_errors() ) {
                $error->add( 'install_unknown_failure', 'Installation failed for an unknown reason (unexpected result).' );
            }

            return $error;
        }

        $plugin_basename = $handler->plugin_info();

        if ( empty( $plugin_basename ) ) {
            $error = new \WP_Error();
            $skin_errors = $skin->get_captured_errors();
            $message = 'Plugin installed successfully, but unable to retrieve plugin information (basename). Check the ZIP structure.';

            if ( $skin_errors && $skin_errors->has_errors() ) {
                foreach ( $skin_errors->get_error_codes() as $code ) {
                    $error->add( $code, $skin_errors->get_error_message( $code ), $skin_errors->get_error_data( $code ) );
                }
                $error->add( 'install_success_no_basename_info', $message);
                return $error;
            }
            return new \WP_Error( 'install_success_no_basename', $message );
        }

        $messages = [];
         $skin_messages_for_success = $skin->get_captured_errors(); // potrebbe contenere avvisi non bloccanti
         if ( $skin_messages_for_success && $skin_messages_for_success->has_errors() ) {
             $messages = $skin_messages_for_success->get_error_messages();
         }

        return [
            'success' => true,
            //'basename' => $plugin_basename,
            'messages' => $messages,
        ];
    }

    public function install( $plugin )  {
        $ret = [ 'success' => false ];
        if ( ! $plugin instanceof Repo\Data ) {
            $ret['messages'] = 'Invalid plugin object given.';
            return $ret;
        }
        $result = $this->installByUrl( $plugin->getZipUrl() );
        if ( $result instanceof \WP_Error ) {
            $ret['messages'] = $result->get_error_messages();
        } else {
            if ( is_array($result) ){
                if ( isset($result['success']) && $result['success'] === true ) {
                    $ret['success'] = true;
                    $ret['messages'] = "The add-on has been successfully installed.";
                } else {
                    $ret['messages'] = "A problem occurred while installing the add-on.";
                }
                if ( isset($result['messages']) && is_array($result['messages']) && count($result['messages']) > 0 ) {
                    $ret['messages'] = array_merge([$ret['messages']], $result['messages']);
                }
            } else {
                $ret['messages'] = "A problem occurred while installing the add-on.";
            }
        }
        return $ret;
    }

    private function activateByBasename( $basename ) {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return new \WP_Error( 'user_unauthorized', 'You are not authorized to perform this action.' );
        }
        if ( empty( $basename ) ) {
            return new \WP_Error( 'invalid_basename', 'Plugin basename is empty.' );
        }
        if ( !$this->isInstalled( $basename ) ) {
            return new \WP_Error( 'invalid_plugin', 'Plugin not found.' );
        }

        $result = activate_plugin( $basename );

        if ( is_wp_Error( $result ) ) {
            return $result;
        }

        return true;
    }

    public function activate( $plugin ) {
        $ret = [ 'success' => false ];
        if ( ! $plugin instanceof Repo\Data ) {
            $ret['messages'] = 'Invalid plugin object given.';
            return $ret;
        }

        $result = $this->activateByBasename( $plugin->basename );

        if ( is_wp_Error( $result ) ) {
            $ret['messages'] = $result->get_error_messages();
        } else {
            $ret['success'] = true;
            $ret['messages'] = "";
        }

        return $ret;
    }

    private function deactivateByBasename( $basename ) {
        if ( empty( $basename ) ) {
            return new \WP_Error( 'invalid_basename', 'Plugin basename is empty.' );
        }
        if ( !$this->isInstalled( $basename ) ) {
            return true;
        }
        if ( !$this->isActivated( $basename ) ) {
            return true;
        }
        if ( ! current_user_can( 'deactivate_plugins' ) ) {
            return new \WP_Error( 'user_unauthorized', 'You are not authorized to perform this action.' );
        }

        deactivate_plugins( $basename );

        return !$this->isPluginActive( $basename );
    }

    public function deactivate( $plugin ) {
        $ret = [ 'success' => false ];
        if ( ! $plugin instanceof Repo\Data ) {
            $ret['messages'] = 'Invalid plugin object given.';
            return $ret;
        }

        $result = $this->deactivateByBasename( $plugin->basename );

        if ( is_wp_Error( $result ) ) {
            $ret['messages'] = $result->get_error_messages();
        } else {
            $ret['success'] = $result;
            $ret['messages'] = !$result ? "Plugin deactivation failed for an unknown reason." : "";
        }

        return $ret;
    }

    public function getVersion( $plugin ) {
       if ( $plugin instanceof Repo\Data ) {
            $basename = $plugin->basename;
        } elseif ( is_string( $plugin ) ) {
            $basename = "{$plugin}/{$plugin}.php";
        } else {
            return '';
        }
        if ( empty( $basename ) ) {
            return '';
        }
        $filepath = sosidee_check_path_separator( WP_PLUGIN_DIR . '/' . $basename );
        if ( !SOS_WP\FileSystem::exists( $filepath ) ) {
            return '';
        }
        $infos = get_plugin_data( $filepath, false, false );
        if ( empty($infos['Version']) ) {
            return '';
        }
        return $infos['Version'];
    }

}