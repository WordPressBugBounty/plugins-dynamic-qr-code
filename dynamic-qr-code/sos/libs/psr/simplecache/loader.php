<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\LIBS\Psr\SimpleCache;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Loader {
    public static function execute() {
        // Ottieni la lista dei file PHP nella directory
        $files = glob(__DIR__ . '/*.php');

        // Ottieni il file corrente (questo file)
        $currentFile = basename(__FILE__);

        // Includi ogni file tranne quello corrente
        foreach ($files as $file) {
            if (basename($file) !== $currentFile) {
                require_once $file;
            }
        }
    }
}
