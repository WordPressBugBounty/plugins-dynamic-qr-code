<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TLicense
{
    protected static $license = null;

    public static function getLicense() {
        return self::$license;
    }
    public static function setLicense( $license ) {
        self::$license = $license;
    }

    protected static function getLicenseKey() {
        return self::$license->getKey();
    }

    protected static function isLicenseValid() {
        return self::$license->isValid();
    }
    protected static function isLicenseEmpty() {
        return self::$license->isEmpty();
    }

    protected static function getLicenseStatus() {
        return self::$license->getStatus();
    }

    protected static function getLicStatusDesc() {
        if ( self::isLicenseEmpty() ) {
            return 'empty';
        } else {
            return Lic\Status::getTag( self::$license->getStatus() );
        }
    }


}