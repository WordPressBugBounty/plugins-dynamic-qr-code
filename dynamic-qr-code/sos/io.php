<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS;
defined('SOSIDEE_DYNAMIC_QRCODE') or die; // No direct access.

class IO
{
    public static function checkDirSep( $path ) {
        return rtrim( self::checkSep($path) , DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }


    private static function checkRoot( &$path ) {
        $path = self::checkSep($path);
        if ( !self::fileExists($path) ) {
            $path = self::getRoot() . ltrim( $path, DIRECTORY_SEPARATOR );
        }
    }

    public static function getRoot() {
        if ( !Base::isWp() ) {
            return self::checkDirSep( realpath($_SERVER['DOCUMENT_ROOT']) );
        } else {
            return self::checkDirSep( get_home_path() );
        }
    }

    public static function checkSep( $path ) {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public static function req( $path, $once = true ) {
        self::checkRoot($path);
        return $once ? require_once $path : require $path;
    }

    public static function get( $path, $replaces = [] ) {
        $path = self::checkSep($path);
        self::checkRoot($path);
        $ret = self::loadFile($path);
        if ( $ret !== false ) {
            if (count($replaces) > 0) {
                $srcs = array_keys($replaces);
                $reps = array_values($replaces);
                $ret = str_replace($srcs, $reps, $ret);
            }
        }
        return $ret;
    }
    public static function set( $path, $replaces = [] ) {
        $path = self::checkSep($path);
        $ret = self::get($path, $replaces);
        if ($ret !== false) {
            echo $ret;
        }
        return $ret;
    }

    //folder of the main running file
    public static function getMainFileFolder() {
        return getcwd();
    }

    //folder of the file where this function is called
    public static function getCurrentFolder() {
        return __DIR__;
    }

    public static function folderExists( $path ) {
        $path = self::checkSep($path);
        return is_dir($path);
    }

    public static function createFolder( $path ) {
        $path = self::checkSep($path);
        if ( !self::folderExists($path) ) {
            return mkdir( $path, 0777, true );
        } else {
            return true;
        }
    }

    public static function fileExists( $path ) {
        $path = self::checkSep($path);
        return file_exists($path);
    }

    public static function copyFile( $source, $target ) {
        $source = self::checkSep($source);
        if (!file_exists($source)) {
            return false;
        }
        return copy($source, $target);
    }

    public static function deleteFile( $path ) {
        $path = self::checkSep($path);
        if (!file_exists($path)) {
            return true;
        }
        return unlink($path);
    }

    public static function deleteFiles( $folder ) {
        $ret = true;
        $folder = self::checkSep($folder);
        if ( strpos($folder, '*') === false ) {
            if ( !Text::endsWith($folder, '/') ) {
                $folder .= '/';
            }
            $folder .= '*';
        }
        foreach ( glob($folder) as $file ) {
            if ( is_file($file) ) {
                $ret = self::deleteFile($file) && $ret;
            }
        }
        return $ret;
    }

    public static function loadFile( $path ) {
        $ret = false;
        $path = self::checkSep($path);
        self::checkRoot($path);
        if ( file_exists($path) ) {
            $ret = file_get_contents($path);
        }
        return $ret;
    }

    public static function loadLines( $path ) {
        $ret = false;
        $path = self::checkSep($path );
        self::checkRoot($path);
        if ( file_exists($path) ) {
            $ret = file($path, FILE_IGNORE_NEW_LINES);
        }
        return $ret;
    }

    public static function saveFile( $path, $content ) {
        $path = self::checkSep($path);
        return file_put_contents($path, $content, LOCK_EX) !== false;
    }

    public static function appendFile( $path, $content ) {
        $path = self::checkSep($path);
        if ( self::fileExists($path) ) {
            $content = PHP_EOL . $content;
        }
        return file_put_contents($path, $content, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /*
        syntax:
        loadCSV( $path ) //default parameters
        loadCSV( $path, ['skip_first_row'=>true, 'delimiter'=>';'] ) // --> $skip_first_row=true and $delimiter=';'
    */
    public static function loadCSV( $path, $parameters = array() ) {
        $ret = false;
        $path = self::checkSep($path);
        if ( file_exists($path) ) {
            $skip_first_row = false;
            $delimiter = ',';
            $enclosure = '"';
            $escape = "\\";
            $length = 0;
            $row_max = 0;
            $in_charset = 'Windows-1252';
            $out_charset = 'UTF-8';
            
            extract($parameters, EXTR_IF_EXISTS);
            
            $skipped = !$skip_first_row;
            if ( ( $handle = fopen($path, "r") ) !== false ) {
                $ret = array();
                while ( ($data = fgetcsv($handle, $length, $delimiter, $enclosure, $escape) ) !== false) {
                    for ( $n=0; $n < count($data); $n++ ) {
                        $data[$n] = iconv( $in_charset, "$out_charset//TRANSLIT", $data[$n] );
                    }
                    if ( $skipped ) {
                        $ret[] = $data;
                    } else {
                        $skipped = true;
                    }
                    if ( $row_max > 0 && count($ret) >= $row_max ) {
                        break;
                    }
                }
                fclose($handle);
            }
        }
        return $ret;
    }

    public static function saveCSV( $path, $lines, $parameters = array() ) {
        $ret = false;
    
        $delimiter = ',';
        $enclosure = '"';
        $escape = "\\";
        $out_charset = 'Windows-1252';
        $in_charset = 'UTF-8';
        
        extract($parameters, EXTR_IF_EXISTS);

        $path = self::checkSep($path);
        if ( ($handle = fopen($path, "w")) !== false ) {
            $ret = true;
            for ($i=0; $i<count($lines); $i++) {
                $data = $lines[$i];
                for ($j=0; $j<count($data); $j++) {
                    $data[$j] = iconv( $in_charset, "$out_charset//TRANSLIT", $data[$j] );
                }
                $ret = (fputcsv($handle, $data, $delimiter, $enclosure, $escape) !== false) && $ret;
            }
            fclose($handle);
        }
    
        return $ret;
    }

    public static function read( $path ) {
        return new \SplFileObject($path, 'r');
    }
    
}