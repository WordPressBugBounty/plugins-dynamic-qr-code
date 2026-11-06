<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Addon\Repo;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Data
{
    use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TProperty;

    public $name;
    public $description;
    public $folder; // corresponds to the plugin's key
    public $basename;
    public $url;
    public $zip;
    public $version;

    public function __construct() {
        $this->folder = '';
        $this->basename = '';
        $this->url = '';
        $this->zip = '';
        $this->name = '';
        $this->description = '';
        $this->version = '';
    }

    public function getZipUrl() {
        return $this->url . $this->zip;
    }

}