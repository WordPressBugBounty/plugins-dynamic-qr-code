<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TAddon
{
    private static $ADDON_KEY = 'sos_dqc_001';

    public function hasFacebook() {
        return $this->hasAddon(self::$ADDON_KEY);
    }
    public function hasInstagram() {
        return $this->hasAddon(self::$ADDON_KEY);
    }
    public function hasLinkedIn() {
        return $this->hasAddon(self::$ADDON_KEY);
    }
    public function hasWhatsApp() {
        return $this->hasAddon(self::$ADDON_KEY);
    }
    public function hasYouTube() {
        return $this->hasAddon(self::$ADDON_KEY);
    }

    public function hasGeo() {
        return $this->hasAddon(self::$ADDON_KEY);
    }
    public function hasDuplicate() {
        return $this->hasAddon(self::$ADDON_KEY);
    }
    public function hasStats() {
        return $this->hasAddon(self::$ADDON_KEY);
    }

    public function hasSocial() {
        return $this->hasFacebook()
            || $this->hasInstagram()
            || $this->hasLinkedIn()
            || $this->hasWhatsApp()
            || $this->hasYouTube()
            ;
    }

    public function hasAnyAddon() {
        return $this->hasSocial()
            || $this->hasGeo()
            || $this->hasDuplicate()
            || $this->hasStats()
        ;
    }
}