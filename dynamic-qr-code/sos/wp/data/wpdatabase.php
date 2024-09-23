<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP_ROOT;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );


class WpDatabase
{
    use SOS_WP_ROOT\Property;

    protected $prefix; //tables prefix

    protected $tables;
    public $lastErrors;

    public function __construct( $prefix = null ) {
        global $wpdb;
        if ( is_null($prefix) ) {
            $prefix = 'sos_';
        }
        if ( !sosidee_str_ends_with($wpdb->prefix, '_') && !sosidee_str_starts_with($prefix, '_') ) {
            $prefix = '_' . $prefix;
        }
        if ( !sosidee_str_ends_with($prefix, '_') ) {
            $prefix .= '_';
        }
        $this->prefix = $wpdb->prefix . $prefix;

        $this->tables = array();
        $this->lastErrors = array();
    }

    public function addTable($name) {
        $ret = new WpTable($this->prefix . $name);
        $this->tables[] = $ret;
        $this->_addProperty($name, $ret);
        return $ret;
    }

    protected function createTables() {
        global $wpdb;
        $ret = false;
        $this->lastErrors = array();
        $count = count($this->tables);
        if ( $count > 0 ) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            if ( function_exists('dbDelta') ) {
                $ret = true;
                $charset = $wpdb->get_charset_collate();
                for ( $n=0; $n<count($this->tables); $n++ ) {
                    $sql = $this->tables[$n]->getCommandSql() . " {$charset};";
                    \dbDelta( $sql );
                    $err = $wpdb->last_error;
                    if ( !empty($err) ) {
                        $ret = false;
                        sosidee_log("WpDatabase.createTables() :: function dbDelta($sql) generated this error: $err");
                        $this->lastErrors[] = $err;
                    }
                }
            } else {
                $this->lastErrors[] = 'Unable to load the WP function dbDelta().';
                sosidee_log("WpDatabase.createTables() was unable to load the WP function dbDelta()");
            }
        }
        return $ret;
    }

    public function getTable( $name ) {
        $ret = false;
        for ($n=0; $n<count($this->tables); $n++) {
            if ( $this->tables[$n]->name == $this->prefix . $name ) {
                $ret = $this->tables[$n];
                break;
            }
        }
        return $ret;
    }

    public function create()
    {
        add_action( 'plugins_loaded', function() {
            $plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
            $key = $plugin->key . '_db-version';
            $installed = get_option($key, '0' );
            $current = $plugin->version;
            if ( version_compare($installed, $current) < 0 ) {
                if ( $this->createTables() ) {
                    update_option($key, $current );
                } else {
                    if ( is_admin() ) {
                        for ( $n=0; $n<count($this->lastErrors); $n++ ) {
                            $plugin::msgErr( $this->lastErrors[$n] );
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
        if ( count( $values ) == 1 && is_array( $values[0] ) ) {
            $values = $values[0];
        }
        if ( count($values) > 0) {
            $query = $wpdb->prepare($formatted_sql, $values);
        } else {
            $query = $formatted_sql;
        }
        $ret = $wpdb->get_results($query); // ARRAY_A | ARRAY_N | OBJECT (default) | OBJECT_K
        if ( is_null($ret) ) {
            $ret = false;
            sosidee_log("WpDatabase.select() :: wpdb.get_results($query) returned null.");
        }
        return $ret;
    }

    /**
     * @param string $formatted_sql sql query with formats (%d, %s, %f)
     * @param array $values values associated with the formats --> look out: order is important!
     * @return wpdb object|false
     *
     * Example: obj->query( "INSERT INTO <table> (foo, bar, baz) VALUES (%s, %d, %f)", 'pippo', 123, 1.23 );
     */
    public function query( $formatted_sql, ...$values ) {
        global $wpdb;
        if ( count( $values ) == 1 && is_array( $values[0] ) ) {
            $values = $values[0];
        }
        if ( count($values) > 0) {
            $sql = $wpdb->prepare($formatted_sql, $values);
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