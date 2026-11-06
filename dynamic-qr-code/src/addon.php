<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA as DATA;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Json;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic\Status;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic\Api;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic\Response;

class Addon extends \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon
{
    use TAddon;

    public function __construct() {
        parent::__construct();
    }
/*
    public function migrateLicense() {
        global $wpdb;

        if ( !$this->hasLicense() ) {
            self::msgErr("A technical problem occurred while migrating data (Addon plugin could be deactivated or uninstalled).");
            return false;
        }

        $license = $this->getLicense();
        $pid = $license->getPid();
        $license_key = '';

        $database = self::database();
        //$table_name = 'sos_plm_licenses';
        if ( $database->tableExists( 'sos_plm_licenses', true ) ) {
            $full_table_name = $wpdb->prefix . 'sos_plm_licenses';
            $query = $wpdb->prepare( "SELECT * FROM `{$full_table_name}` WHERE `item_sku`=%s AND `rec_deleted`=0", $pid );
            $results = $wpdb->get_results( $query, ARRAY_A );
            if ( is_array($results) ) {
                foreach ( $results as $data ) {
                    $key = $data['lic_key'] ?? '';
                    if ( !empty(trim($key)) ) {
                        $status = $data['lic_status'] ? Status::validate($data['lic_status']) : Status::UNKNOWN;
                        if ( $status === Status::ACTIVE ) {
                            $license_key = $key;
                            break; //buona la prima!
                        }
                    }
                }
            }
        }

        if ( !empty($license_key) ) {
            $migrated = true;

            self::msgInfo("An activated license has been found.");

            $old_domain = self::getOldDomain();
            $domain_issue = strpos( strtolower($old_domain), 'www.') === 0 || strpos( $old_domain, '/') !== false;
            if ( $domain_issue ) {
                $status = $this->getOldLicenseStatus($license_key, $pid, $old_domain);
                if ( $status !== false ) {
                    if ( in_array( $status, [Status::ACTIVE, Status::EXPIRED, Status::CANCELLED] ) ) {
                        $deactivated = $this->deactivateOldLicense( $license_key, $pid, $old_domain );
                        if ( $deactivated ) {
                            //$license->setData( $license_key, $status );
                            $result = $license->activate( $license_key );
                            if ( !is_array($result) || !isset($result['action']) || $result['action'] !== true ) {
                                $migrated = false;
                            }
                        } else {
                            $migrated = false;
                        }
                    } else {
                        self::msgWarn("The license is not active.");
                        $license->setData( $license_key, $status );
                        if ( ! $license->save() ) {
                            $migrated = false;
                        }
                    }
                }
            } else {
                $license->setData( $license_key, Status::ACTIVE );
                if ( ! $license->save() ) {
                    $migrated = false;
                }
            }

            if ( $migrated ) {
                self::msgOk("License migration completed successfully.");
            } else {
                $license->clear();
                self::msgErr("A problem occurred while migrating data. Please proceed manually.");
            }

        } else {
            self::msgInfo("No active license has been found.");
        }
    }

    private static function getOldDomain() {
        $ret = site_url();
        if ( sosidee_str_starts_with( $ret, 'https://') ) {
            $ret = substr( $ret , strlen('https://') );
        } else if ( sosidee_str_starts_with( $ret, 'http://') ) {
            $ret = substr( $ret , strlen('http://') );
        }
        return rtrim($ret, '/');
    }


    private function getOldLicenseStatus( $key, $pid, $domain ) {
        $result = Api::check( $key, $pid, $domain );
        if ( ! $result instanceof Response ) {
            sosidee_log('SRC\Addon::getOldLicenseStatus() - Invalid server response.');
            return false;
        }
        if ( $result->error ) {
            $message = $result->message ?? 'Unknown error.';
            sosidee_log("SRC\Addon::getOldLicenseStatus() - $message");
            return false;
        }

        return $result->status;
    }
    private function deactivateOldLicense( $key, $pid, $domain ) {

        $result = Api::deactivate( $key, $pid, $domain );

        if ( ! $result instanceof Response ) {
            sosidee_log('SRC\Addon::deactivateOldLicense() - Invalid server response.');
            return false;
        }
        if ( $result->error ) {
            $message = $result->message ?? 'Unknown error.';
            sosidee_log("SRC\Addon::deactivateOldLicense() - $message");
            return false;
        }

        return true;
    }
*/
}