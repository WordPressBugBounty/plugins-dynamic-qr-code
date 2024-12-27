<?php

namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;

/***
 * @property $key : property of the plugin
 */
trait TTransient
{
    use TBase;

    private function getTransientKey($key) {
        $key = trim($key);
        if ( !sosidee_str_starts_with($key, '_') ) {
            $key = '_' . $key;
        }
        if ( sosidee_str_ends_with( get_class($this), '\SosPlugin') ) {
            return $this->key . $key;
        } else {
            return self::plugin()->key . $key;
        }
    }
    public function getTransient($key) {
        $_key = $this->getTransientKey($key);
        return get_transient($_key);
    }
    public function setTransient($key, $value, $expiration = 0) {
        $_key = $this->getTransientKey($key);
        return set_transient($_key, $value, $expiration);
    }
}