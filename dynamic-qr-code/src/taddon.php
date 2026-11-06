<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

trait TAddon
{
    public function getProPageUrl() {
        return $this->pagePro->url;
    }


    public function hasFacebook() {
        return $this->isPro();
    }
    public function hasInstagram() {
        return $this->isPro();
    }
    public function hasLinkedIn() {
        return $this->isPro();
    }
    public function hasWhatsApp() {
        return $this->isPro();
    }
    public function hasYouTube() {
        return $this->isPro();
    }

    public function hasGeo() {
        return $this->isPro();
    }
    public function hasDuplicate() {
        return $this->isPro();
    }
    public function hasStats() {
        return $this->isPro();
    }

    public function hasSocial() {
        return $this->isPro();
    }

    public function hasAnyAddon() {
        return $this->isPro();
    }

}