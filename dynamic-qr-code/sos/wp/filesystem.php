<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class FileSystem
{
    private static function getWpNative() {
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . sosidee_check_path_separator('wp-admin/includes/file.php');
            WP_Filesystem();
        }
        return $wp_filesystem;
    }

    public static function read( $filepath, $removeBOM = false ) {
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::read() - cannot load wp_filesystem object.");
            return false;
        }
        $filepath = sosidee_check_path_separator($filepath);
        if ( !$removeBOM ) {
            return $fs->get_contents( $filepath );
        } else {
            $content = $fs->get_contents( $filepath );
            if ( is_string($content) ) {
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            }
            return $content;
        }
    }

    public static function lines( $filepath, $skip_empty = false, $removeBOM = false ) {
        $content = self::read( $filepath, $removeBOM );
        if ( $content !== false ) {
            if ( $skip_empty ) {
                $lines = preg_split('/\R/', $content, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $lines = preg_split('/\R/', $content);
            }
            return $lines;
        } else {
            return false;
        }
    }

    public static function write( $filepath, $content ) {
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::write() - cannot load wp_filesystem object.");
            return false;
        }
        $filepath = sosidee_check_path_separator($filepath);
        return $fs->put_contents( $filepath, $content, FS_CHMOD_FILE );
    }

    public static function append( $filepath, $line ) {
        $content = self::read($filepath);
        if ( $content !== false ) {
            $content .= PHP_EOL . $line;
        } else {
            $content = $line;
        }
        return self::write( $filepath, $content );
    }

    public static function move( $source, $target, $overwrite = true ) {
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::move() - cannot load wp_filesystem object.");
            return false;
        }
        $source = sosidee_check_path_separator($source);
        $target = sosidee_check_path_separator($target);
        if ( self::isFolder($target) ) {
            $target = sosidee_check_folder_separator($target) . basename($source);
        }
        return $fs->move( $source, $target, $overwrite );
    }

    public static function exists( $path ) {
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::exists() - cannot load wp_filesystem object.");
            return false;
        }
        $path = sosidee_check_path_separator($path);
        return $fs->exists( $path );
    }

    public static function createFolder( $path, $recursive = false ) {
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::createFolder() - cannot load wp_filesystem object.");
            return false;
        }
        $path = sosidee_check_path_separator($path);
        if ( !$recursive ) {
            return $fs->mkdir( $path );
        }

        $parts = explode( DIRECTORY_SEPARATOR, $path );
        $current = '';
        foreach ( $parts as $part ) {
            if ( $part === '' ) {
                $current .= DIRECTORY_SEPARATOR;
                continue;
            }
            $current .= $part . DIRECTORY_SEPARATOR;
            if ( ! $fs->is_dir( $current ) ) {
                if ( ! $fs->mkdir( $current ) ) {
                    sosidee_log("FileSystem::createFolder() - cannot create folder $current");
                    return false;
                }
            }
        }

        return true;
    }

    public static function isFolder( $path ) {
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::isFolder() - cannot load wp_filesystem object.");
            return false;
        }
        $path = sosidee_check_path_separator($path);
        return $fs->is_dir( $path );
    }

    public static function isFile( $path ) {
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::isFile() - cannot load wp_filesystem object.");
            return false;
        }
        $path = sosidee_check_path_separator($path);
        return $fs->is_file( $path );
    }

    public static function getFiles( $directory, $filters = [], $recursive = false ) {
        if ( empty( $directory ) || ! is_string( $directory ) ) {
            return false;
        }
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::getFiles() - cannot load wp_filesystem object.");
            return false;
        }
        $directory = sosidee_check_path_separator($directory);
        if ( ! $fs->is_dir( $directory ) ) {
            return false;
        }
        $items = $fs->dirlist( $directory, $recursive );
        if ( empty( $items ) || ! is_array( $items ) ) {
            return [];
        }
        if ( is_string( $filters ) && ! empty( $filters ) ) {
            $filters = [$filters];
        }
        $patterns = [];
        if ( is_array( $filters ) ) {
            foreach ( $filters as $wildcard ) {
                $regex = str_replace(
                    ['.', '*', '?'],
                    ['\\.', '.*', '.'],
                    $wildcard
                );
                $patterns[] = '/^' . $regex . '$/i';
            }
        }
        $ret = [];
        foreach ( $items as $name => $info ) {
            if ( ! $recursive && 'd' === $info['type'] ) {
                // ignore subfolders
            } elseif ( 'f' === $info['type'] ) {
                $path = sosidee_check_folder_separator( $directory ) . $name;

                if ( empty( $patterns ) ) {
                    $ret[] = $path;
                    continue;
                }

                foreach ( $patterns as $pattern ) {
                    if ( preg_match( $pattern, $name ) ) {
                        $ret[] = $path;
                        break;
                    }
                }
            }
        }
        return $ret;
    }

    public static function deleteFile( $path ) {
        $fs = self::getWpNative();
        if ( ! $fs ) {
            sosidee_log("FileSystem::deleteFile() - cannot load wp_filesystem object.");
            return false;
        }
        if ( self::isFile( $path ) ) {
            $path = sosidee_check_path_separator($path);
            return $fs->delete( $path, false, 'f' );
        } else {
            return true;
        }
    }

    public static function saveCsv( $path, $lines, $parameters = [] ) {
        if ( empty( $path ) || ! is_array( $lines ) ) {
            return false;
        }

        $delimiter     = isset( $parameters['delimiter'] ) ? $parameters['delimiter'] : ',';
        $enclosure     = isset( $parameters['enclosure'] ) ? $parameters['enclosure'] : '"';
        $escape        = isset( $parameters['escape'] ) ? $parameters['escape'] : '\\';
        $out_charset   = isset( $parameters['out_charset'] ) ? $parameters['out_charset'] : 'Windows-1252';
        $in_charset    = isset( $parameters['in_charset'] ) ? $parameters['in_charset'] : 'UTF-8';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        // fopen() is used on a temporary memory stream (php://temp), not a physical file: this is
        // a standard and safe practice for processing data in memory before writing it to disk.
        // The WP_Filesystem API does not provide an alternative for this specific use case.
        $tmp_handle = fopen( 'php://temp', 'r+' );

        if ( false === $tmp_handle ) {
            return false;
        }

        $ret = true;

        $use_iconv = function_exists('iconv');
        foreach ( $lines as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }

            if ( $use_iconv ) {
                foreach ( $row as $i => $cell ) {
                    $converted = @iconv( $in_charset, $out_charset . '//TRANSLIT', $cell );
                    if ( $converted !== false) {
                        $row[$i] = $converted;
                    } else {
                        $converted = @iconv( $in_charset, $out_charset, $cell );
                        if ( $converted !== false) {
                            $row[$i] = $converted;
                        }
                    }
                }
            }

            if ( false === fputcsv( $tmp_handle, $row, $delimiter, $enclosure, $escape ) ) {
                $ret = false;
            }
        }

        rewind( $tmp_handle );
        $csv_data = stream_get_contents( $tmp_handle );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        // fclose() is used on a temporary memory stream (php://temp), not a physical file.
        fclose( $tmp_handle );

        if ( !self::write($path, $csv_data) ) {
            $ret = false;
        }

        return $ret;
    }

    public static function loadCsv( $path, $parameters = [] ) {
        if ( empty( $path ) ) {
            return false;
        }

        if ( !self::isFile($path) ) {
            return false;
        }

        $contents = self::read( $path );
        if ( false === $contents ) {
            return false;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        // fopen() is used on a temporary memory stream (php://temp), not a physical file: this is
        // a standard and safe practice for processing data in memory before writing it to disk.
        // The WP_Filesystem API does not provide an alternative for this specific use case.
        $handle = fopen( 'php://temp', 'r+' );
        if ( false === $handle ) {
            return false;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
        // fwrite() is used on a temporary memory stream (php://temp), not a physical file.
        fwrite( $handle, $contents );
        rewind( $handle );

        $skip_first_row = isset( $parameters['skip_first_row'] ) ? (bool) $parameters['skip_first_row'] : false;
        $delimiter      = isset( $parameters['delimiter'] ) ? $parameters['delimiter'] : ',';
        $enclosure      = isset( $parameters['enclosure'] ) ? $parameters['enclosure'] : '"';
        $escape         = isset( $parameters['escape'] ) ? $parameters['escape'] : '\\';
        $length         = isset( $parameters['length'] ) ? (int) $parameters['length'] : 0;
        $row_max        = isset( $parameters['row_max'] ) ? (int) $parameters['row_max'] : 0;
        $out_charset    = isset( $parameters['out_charset'] ) ? $parameters['out_charset'] : 'UTF-8';

        $in_charset = isset( $parameters['in_charset'] ) && is_string( $parameters['in_charset'] )
            ? $parameters['in_charset']
            : self::detectCharset( $contents );


        $results  = [];
        $skipped = ! $skip_first_row;

        $use_iconv = function_exists('iconv');
        while ( ( $data = fgetcsv( $handle, $length, $delimiter, $enclosure, $escape ) ) !== false ) {
            if ( $use_iconv ) {
                foreach ( $data as $i => $value ) {
                    $converted = @iconv( $in_charset, $out_charset . '//TRANSLIT', $value );
                    if ( $converted !== false) {
                        $data[$i] = $converted;
                    } else {
                        $converted = @iconv( $in_charset, $out_charset, $value );
                        if ( $converted !== false) {
                            $data[$i] = $converted;
                        }
                    }
                }
            }

            if ( $skipped ) {
                $results[] = $data;
            } else {
                $skipped = true;
            }

            if ( $row_max > 0 && count( $results ) >= $row_max ) {
                break;
            }
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        // fclose() is used on a temporary memory stream (php://temp), not a physical file.
        fclose( $handle );

        return $results;
    }

    /**
     * Rileva il charset di un contenuto testuale, con fallback sicuro.
     *
     * @param string $content   Il contenuto su cui rilevare la codifica.
     * @param array  $charsets  (Opzionale) Charset da provare in ordine di priorità.
     * @param string $fallback  (Opzionale) Charset di fallback se il rilevamento fallisce.
     *
     * @return string  Charset rilevato o fallback.
     */
    public static function detectCharset( $content, $charsets = [ 'UTF-8', 'Windows-1252', 'ISO-8859-1' ], $fallback = 'Windows-1252' ) {
        if ( ! is_string( $content ) || trim( $content ) === '' ) {
            return $fallback;
        }

        $detected = mb_detect_encoding( $content, $charsets, true );

        return ( false !== $detected ) ? $detected : $fallback;
    }



}