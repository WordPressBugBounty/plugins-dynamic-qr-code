<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
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

    public function __construct( $data ) {
        $this->name = $data['name']; //The original file name (WITH EXTENSION) on the client machine
        $this->type = $data['type']; //The mime type of the file, if the browser provided this information
        $this->size = $data['size']; // The size in bytes
        $this->temp = $data['tmp_name']; // The temporary file name on the server
        $this->ext = pathinfo($this->name, PATHINFO_EXTENSION);
        if ($this->ext != '') {
            $this->ext = '.' . $this->ext;
        }
        $this->error = $data['error']; // The error code
    }

    public function moveTo( $path, $filename = '' ) {
        $ret = false;
        if ( is_uploaded_file($this->temp) ) {
            if ( !sosidee_str_ends_with($path, DIRECTORY_SEPARATOR) ) {
                $path .= DIRECTORY_SEPARATOR;
            }
            if ( $filename == '' ) {
                $filename = $this->name;
            }
            $target = $path . $filename;
            if ( move_uploaded_file( $this->temp, $target ) !== false ) {
                $ret = $target;
            } else {
                sosidee_log("move_uploaded_file() failed to move file {$this->temp} to {$path}{$filename}");
            }
        }
        return $ret;
    }

    public static function getErrorDescription( $index ) {
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


}