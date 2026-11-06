<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP_ROOT;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );


class WpDatabase
{
    use SOS_WP_ROOT\TBase;

    protected $prefix; // plugin's tables prefix
    protected $charset;

    protected $tables;
    public $lastErrors;

    public function __construct( $prefix ) {
        global $wpdb;
        $prefix = trim($prefix, '_');
        $prefix = preg_replace('/^(_?sos_?)/i', '', $prefix);
        $prefix = ltrim($prefix, '_');
        $prefix_wp = rtrim($wpdb->prefix, '_');
        $this->prefix = "{$prefix_wp}_sos_{$prefix}_";

        $this->charset = $wpdb->get_charset_collate();

        $this->tables = array();
        $this->lastErrors = array();
    }

    public function addTable($name) {
        $ret = new WpTable($this->prefix . $name);
        $ret->charset = $this->charset;
        $this->tables[] = $ret;
        return $ret;
    }

    protected function createTables() {
        $ret = false;
        $this->lastErrors = array();
        $updated = false;
        $hashes = [];
        $key = $this->plugin()->key . '_db-tables';
        $count = count($this->tables);
        if ( $count > 0 ) {
            if ( ! function_exists('dbDelta') ) {
                require_once( sosidee_check_path_separator( ABSPATH . 'wp-admin/includes/upgrade.php' ) );
            }
            if ( function_exists('dbDelta') ) {
                global $wpdb;
                $ret = true;
                $saved_hashes = get_option($key, [] );
                for ( $n=0; $n<$count; $n++ ) {
                    $updateTable = false;
                    $table = $this->tables[$n];
                    $current_hash = $table->getSchemaHash();
                    if ( isset($saved_hashes[$table->name]) ) {
                        if ( $saved_hashes[$table->name] != $current_hash ) {
                            $updateTable = true;
                        }
                    } else {
                        $updateTable = true;
                    }
                    if ( $updateTable ) {
                        $sql = $table->getCommandSql();
                        \dbDelta( $sql );
                        $err = $wpdb->last_error;
                        if ( empty($err) ) {
                            $updated = true;
                            $hashes[$table->name] = $current_hash;
                        } else {
                            $ret = false;
                            sosidee_log("WpDatabase::createTables() - function dbDelta($sql) generated this error: $err");
                            $this->lastErrors[] = $err;
                            if( isset($saved_hashes[$table->name]) ) {
                                $hashes[$table->name] = $saved_hashes[$table->name];
                            }
                        }
                    } else {
                        $hashes[$table->name] = $current_hash;
                    }

                }
                if ( $updated ) {
                    update_option($key, $hashes );
                }
            } else {
                $this->lastErrors[] = 'Unable to load the WP function dbDelta().';
                sosidee_log("WpDatabase::createTables() was unable to load the WP function dbDelta()");
            }
        } else {
            sosidee_log("WpDatabase::createTables() no custom table has been defined.");
        }
        return $ret;
    }

    public function getTable( $name ) {
        $ret = null;
        foreach ( $this->tables as $table ) {
            if ( $table->name == $this->prefix . $name ) {
                $ret = $table;
                break;
            }
        }
        return $ret;
    }

    public function create() {
        add_action( 'plugins_loaded', function() {
            $current = $this->plugin()->version;
            if ( empty($current) ) {
                $msg = "Plugin version is empty. Check if initialize() function contains the line 'parent::initialize();'";
                if ( is_admin() ) {
                    $this->plugin()::msgErr($msg, true);
                }
                sosidee_log("WpDatabase::create() - " . $msg);
                return;
            }
            $key = $this->plugin()->key . '_db-version';
            $installed = get_option($key, '0' );
            $new_version = version_compare($installed, $current) < 0;
            if ( $new_version || sosidee_is_dev() ) {
                if ( $this->createTables() ) {
                    if ( $new_version ) {
                        update_option($key, $current );
                    }
                    $this->plugin()->onDatabaseUpdate($installed, $current);
                } else {
                    if ( is_admin() ) {
                        for ( $n=0; $n<count($this->lastErrors); $n++ ) {
                            $this->plugin()::msgErr( $this->lastErrors[$n], true );
                        }
                    }
                }
            }
        } );
    }

    /**
     * @param string $formatted_sql sql query with formats (%d, %s, %f)
     * @param array $values values associated with the formats --> look out: order is important!
     * @return array|object|false
     *
     * Example: obj->select( "SELECT <columns> FROM <table> WHERE <field1>=%d AND <field2>=s%", 123, 'foobar' );
     */
    public function select( $formatted_sql, ...$values ) {
        global $wpdb;
        // if the first argument is an array, unpack it
        if ( count( $values ) == 1 && is_array( $values[0] ) ) {
            $values = $values[0];
        }
        if ( count($values) > 0) {
            $query = $wpdb->prepare($formatted_sql, ...$values);
        } else {
            $query = $wpdb->prepare($formatted_sql);
        }
        $ret = $wpdb->get_results($query); // ARRAY_A | ARRAY_N | OBJECT (default) | OBJECT_K
        if ( is_null($ret) ) {
            $ret = false;
            sosidee_log("WpDatabase.select() :: wpdb.get_results($query) returned null.");
        }
        return $ret;
    }

    /**
     * @param \string $formatted_sql sql query with formats (%d, %s, %f)
     * @param \array $values values associated with the formats --> look out: order is important!
     * @return bool|int|\mysqli_result|null
     *
     * Example: obj->query( "INSERT INTO <table> (foo, bar, baz) VALUES (%s, %d, %f)", 'pippo', 123, 1.23 );
     */
    public function query( $formatted_sql, ...$values ) {
        global $wpdb;
        if ( count( $values ) == 1 && is_array( $values[0] ) ) {
            $values = $values[0];
        }
        if ( count($values) > 0) {
            $sql = $wpdb->prepare($formatted_sql, ...$values);
        } else {
            $sql = $formatted_sql;
        }
        $ret = $wpdb->query($sql);
        if ( is_null($ret) ) {
            $ret = false;
            sosidee_log("WpDatabase.query() :: wpdb.query($sql) returned null.");
        }
        return $ret;
    }

    public function tableExists( $name, $without_sos_prefix = false ) {
        global $wpdb;
        if ( ! $without_sos_prefix ) {
            $name = $this->prefix . $name;
        } else {
            $name = $wpdb->prefix . $name;
        }
        $name = $wpdb->esc_like($name);
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $name );
        $result = $wpdb->get_var( $query );
        return ! is_null( $result );
    }

    public function transaction() {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
    }
    public function commit() {
        global $wpdb;
        $wpdb->query('COMMIT');
    }
    public function rollback() {
        global $wpdb;
        $wpdb->query('ROLLBACK');
    }

}