<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Lic\Status;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TBase;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\ActionResult;

class License
{
    use TBase;
    use SOS_WP\TMessage;

    private $pid;
    private $data;

    public function __construct( $pid ) {
        $this->pid = trim($pid);
        $this->load();
    }

    public function getPid() {
        return $this->pid;
    }

    private static function getDbKey() {
        return self::plugin()->key . '_license';
    }

    private function load() {
        $dbKey = self::getDbKey();
        $data = get_option( $dbKey );
        if ( $data instanceof Lic\Data ) {
            if ( strcmp(mb_strtolower($data->pid), mb_strtolower($this->pid))  === 0 ) {
                $data->pid = $this->pid; // a ogni buon conto...
                $this->data = $data;
                return true;
            } else {
                sosidee_log("License::load() loaded pid does not match the license pid.");
                $this->clear();
                return false;
            }
        } else {
            $this->clear();
            return false;
        }
    }

    public function save() {
        $dbKey = self::getDbKey();
        $data = get_option( $dbKey, null );
        if ( $data == $this->data ) {
            return true;
        }
        $result = update_option( $dbKey, $this->data, false );
        if ( $result ) {
            return true;
        } else {
            $data = get_option($dbKey);
            return ($data == $this->data);
        }
    }

    private function delete() {
        $dbKey = self::getDbKey();
        if ( delete_option($dbKey) ) {
            $this->clear();
            return true;
        } else {
            return false;
        }
    }


    // per la form utilizzata dall'amministratore
    public function check() {
        if ( ! $this->load() || empty( $this->data->key ) ) {
            return ActionResult::error('License key is empty.');
        }

        $result = Lic\Api::check( $this->data->key, $this->pid );

        if ( ! $result instanceof Lic\Response ) {
            return ActionResult::error('Invalid server response.');
        }

        if ( $result->error ) {
            return ActionResult::error($result->message ?? 'Unknown error.');
        }

        $status = Status::validate( $result->status );
        $this->data->status = $status;
        $this->data->lastcheck = current_time( 'timestamp' );

        if ( $status != Status::UNKNOWN ) {
            if ( in_array( $status, [Status::ACTIVE, Status::UNUSED] ) ) {
                $ret = ActionResult::success($result->message ?? 'License key has been checked.');
            } elseif ( in_array( $status, [Status::ASSIGNED, Status::EXHAUSTED] ) ) {
                $ret = ActionResult::info($result->message ?? 'License key has been checked.');
            } else {
                $ret = ActionResult::warning($result->message ?? 'License key has been checked.');
            }
        } else {
            $ret = ActionResult::error($result->message ?? 'A problem occurred while checking your license.');
        }

        if ( $this->save() ) {
            return $ret; //->addSuccess('License key has been saved.');
        } else {
            sosidee_log("Addon\License::check() - A problem occurred while saving license data to database.");
            return $ret->addError('A problem occurred while saving your license here.');
        }

    }

    // per il cron
    public function verify() {
        sosidee_log("Addon\License::verify() - function called.");
        $result = $this->check();
        if ( $result instanceof ActionResult ) {
            if ( ! $result->error ) {
                sosidee_log("Addon\License::verify() - no error occurred.");
                return true;
            } else {
                if ( $result->hasMessages() ) {
                    $msg = implode('; ', array_column($result->getMessages(), 'text'));
                } else {
                    $msg = 'Unknown error.';
                }
                sosidee_log("Addon\License::verify() - A problem occurred: $msg");
                $now = current_time( 'timestamp' );
                if ( $now - $this->data->lastcheck > YEAR_IN_SECONDS) {
                    $this->data->status = Status::UNKNOWN;
                    $this->data->lastcheck = $now;
                    $this->save();
                    sosidee_log("Addon\License::verify() - One year has passed since the last license check.");
                }
            }
        } else {
            sosidee_log("Addon\License::verify() - Unknown problem occurred.");
        }
        return false;
    }

    public function activate( $key ) {
        $key = trim($key);
        if ( empty( $key ) ) {
            return ActionResult::error('License key is empty.');
        }

        $result = Lic\Api::activate( $key, $this->pid );

        if ( ! $result instanceof Lic\Response ) {
            return ActionResult::error('Invalid server response.');
        }
        if ( $result->error ) {
            return ActionResult::error($result->message ?? 'Unknown error.');
        }

        $status = Status::validate( $result->status );
        $this->setData( $key, $status );
        $this->data->lastcheck = current_time( 'timestamp' );

        if ( $status == Status::ACTIVE ) {
            $ret = ActionResult::success($result->message ?? 'License has been processed.');
        } else {
            $ret = ActionResult::error($result->message ?? 'A problem occurred while activating your license.');
        }

        if ( $this->save() ) {
            return $ret; //->addSuccess($result->message ?? 'License key has been saved.');
        } else {
            sosidee_log("Addon\License::activate() - A problem occurred while saving license data to database.");
            return $ret->addError('A problem occurred while saving your license here.');
        }
    }

    public function remove() {
        if ( $this->load() && !empty( $this->data->key ) ) {

            $result = Lic\Api::deactivate( $this->data->key, $this->pid );

            if ( ! $result instanceof Lic\Response ) {
                return ActionResult::error('Invalid server response.');
            }
            if ( $result->error ) {
                return ActionResult::error($result->message ?? 'Unknown error.');
            }

            $status = Status::validate( $result->status );

            if ( $status != Status::UNKNOWN ) {
                if ( in_array( $status, [Status::UNUSED, Status::ASSIGNED, Status::EXHAUSTED] ) ) {
                    $ret = ActionResult::success($result->message ?? 'The license is not assigned to this website.');
                } else {
                    $ret = ActionResult::warning($result->message ?? 'The license is not assigned to this website.');
                }
            } else {
                $ret = ActionResult::error($result->message ?? 'A problem occurred while removing your license.');
            }

            /*
            $success = !empty($result->status) && in_array($result->status, [Status::UNUSED, Status::INACTIVE]);
            //$ret = new ActionResult($success);
            if ( $success ) {
                $ret->addSuccess($result->message ?? 'The license has been removed from this website.');
                //self::msgOk($result->message ?? 'The license has been removed from this website.');
            } else {
                $ret->addWarning($result->message ?? 'The license removal could not be confirmed.');
                //self::msgWarn($result->message ?? 'The license removal could not be confirmed.');
            }
            */

            if ( $this->delete() ) {
                return $ret->addSuccess('The license has been removed from the website database.');
            } else {
                sosidee_log("Addon\License::remove() - A problem occurred while deleting the license data from the database.");
                //$msg = $result->message ?? 'License has not been deleted from the database.';
                return $ret->addError('The license has not been removed from the website database.');
            }

        } else {
            return ActionResult::error('License key is empty.');
        }

    }

    public function getKey() {
        return $this->data->key;
    }
    public function getStatus() {
        return $this->data->status;
    }

    public function isEmpty() {
        return empty($this->data->key);
    }

    public function isValid() {
        return !$this->isEmpty() && $this->getStatus() === Status::ACTIVE;
    }

    public function setData( $key = '', $status = Status::UNKNOWN ) {
        $this->data = new Lic\Data($this->pid);
        $this->data->key = $key;
        $this->data->status = $status;
    }

    public function clear() {
        $this->setData();
    }

}