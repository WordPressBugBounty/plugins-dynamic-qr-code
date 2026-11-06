<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\FileSystem;

defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );
/**
 *
UPLOAD_ERR_OK = 0
There is no error, the file uploaded with success.

UPLOAD_ERR_INI_SIZE = 1
The uploaded file exceeds the upload_max_filesize directive in php.ini.

UPLOAD_ERR_FORM_SIZE = 2
The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.

UPLOAD_ERR_PARTIAL = 3
The uploaded file was only partially uploaded.

UPLOAD_ERR_NO_FILE = 4
No file was uploaded.

UPLOAD_ERR_NO_TMP_DIR = 6
Missing a temporary folder. Introduced in PHP 5.0.3.

UPLOAD_ERR_CANT_WRITE = 7
Failed to write file to disk. Introduced in PHP 5.1.0.

UPLOAD_ERR_EXTENSION = 8
A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop
 *
 */

class FormFile
{

    public $name;
    public $type;
    public $size;
    public $temp;
    public $ext;
    public $error;
    public $message;

    public function __construct( $data ) {
        $this->name = $data['name'] ?? ''; // original file name (WITH EXTENSION) on the client machine
        $this->type = $data['type'] ?? ''; // mime type of the file, if the browser provided this information
        $this->size = $data['size'] ?? 0; // size in bytes
        $this->temp = $data['tmp_name'] ?? ''; // temporary file name on the server
        $this->ext = pathinfo($this->name, PATHINFO_EXTENSION); // file extension
        if ( $this->ext != '' ) {
            $this->ext = '.' . $this->ext;
        }
        $this->error = $data['error'] ?? -1; // error code
        $this->message = self::getMessage($this->error);
    }

    public function moveTo( $filename = '', $path = '') {
        $ret = false;
        if ( is_uploaded_file($this->temp) ) {
            $filename = sanitize_file_name( empty($filename) ? $this->name : $filename );
            $file = [
                'name'     => $filename,
                'type'     => $this->type,
                'tmp_name' => $this->temp,
                'error'    => $this->error,
                'size'     => $this->size
            ];
            $upload_overrides = [ 'test_form' => false ]; // bypassa il test del form HTML
            $movefile = wp_handle_upload($file, $upload_overrides);
            if ( $movefile && !isset($movefile['error']) ) {
                $uploaded_path = $movefile['file']; // percorso del file salvato
                if ( !empty($path) ) {
                    if ( !FileSystem::exists($path) && !FileSystem::createFolder($path) ) {
                        sosidee_log("Could not create folder $path");
                        return false;
                    }
                    $destination = sosidee_check_folder_separator($path) . $filename;
                    if ( FileSystem::move( $uploaded_path, $destination ) ) {
                        $ret = $destination;
                    } else {
                        sosidee_log("Could not move file from $uploaded_path to $destination");
                    }
                } else {
                    $ret = $uploaded_path;
                }
            } else {
                sosidee_log("wp_handle_upload() failed: " . (isset($movefile['error']) ? esc_html($movefile['error']) : 'Unknown error'));
            }
        } else {
            sosidee_log("is_uploaded_file() check failed for temp file: " . esc_html($this->temp));
        }
        return $ret;
    }

    public static function getMessage( $index ) {
        $ret = 'Unknown.';
        switch ($index) {
            case UPLOAD_ERR_OK:
                $ret = 'The file uploaded successfully.';
                break;
            case UPLOAD_ERR_INI_SIZE:
                $ret = 'The uploaded file exceeds the PHP size directive.';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $ret = 'The uploaded file exceeds the size directive that was specified in the HTML form.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $ret = 'The file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $ret = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $ret = 'Missing a temporary folder where to save the uploaded file.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $ret = 'Failed to write file to the web server disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $ret = "A PHP extension stopped the file upload but there's no way to ascertain which one caused the file upload to stop.";
                break;
        }
        return $ret;
    }

    public function getContent() {
        return FileSystem::read($this->temp);
    }


}