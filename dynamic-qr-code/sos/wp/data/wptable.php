<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP_ROOT;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class WpTable
{
    use SOS_WP_ROOT\Property;

    protected $columns;
    protected $primaryKey;
    public $name;

    public function __construct($name) {
        $this->primaryKey = false;

        $this->name = $name;
        $this->columns = array();
    }

    public function addColumn($name, $type, $length = '') {
        $ret = new WpColumn($this->name, $name, $type, $length);
        $this->columns[] = $ret;
        $this->_addProperty($name, $ret);
        return $ret;
    }

    public function addID($name = 'id') {
        $ret = $this->addColumn($name, WpColumnType::INTEGER);
        $ret->autoIncrement = true;
        $this->primaryKey = $name;
        return $ret;
    }

    public function addInteger($name) {
        return $this->addColumn($name, WpColumnType::INTEGER);
    }

    public function addTinyInteger($name) {
        return $this->addColumn($name, WpColumnType::TINY_INTEGER);
    }

    public function addSmallInteger($name) {
        return $this->addColumn($name, WpColumnType::SMALL_INTEGER);
    }

    public function addBoolean($name) {
        return $this->addColumn($name, WpColumnType::BOOLEAN);
    }

    public function addCurrency($name) {
        return $this->addColumn($name, WpColumnType::CURRENCY);
    }

    public function addVarChar($name, $length = '') {
        return $this->addColumn($name, WpColumnType::VARCHAR, $length);
    }

    public function addDateTime($name) {
        return $this->addColumn($name, WpColumnType::DATETIME);
    }

    public function addTime($name) {
        return $this->addColumn($name, WpColumnType::TIME);
    }

    protected function getColumnByName($name) {
        $ret = false;
        for ($n=0; $n<count($this->columns); $n++) {
            if ( strcasecmp($this->columns[$n]->name, $name) == 0 ) {
                $ret = $this->columns[$n];
                break;
            }
        }
        return $ret;
    }

    public function getCommandSql() {
        $ret = "CREATE TABLE {$this->name} (";

        $count = count($this->columns);
        if ( $count > 0 ) {
            for ($n=0; $n<$count; $n++) {
                if ( $n > 0 ) {
                    $ret .= ",";
                }
                $ret .= PHP_EOL . $this->columns[$n]->getCommandSql();
            }
            if ($this->primaryKey !== false) {
                $ret .= "," . PHP_EOL . "PRIMARY KEY  ({$this->primaryKey})";
            }
        }
        $ret .= PHP_EOL . ")";
        return $ret;
    }

    private function getDataFormat( &$data ) {
        $ret = array();
        foreach ( $data as $name => $value ) {
            $column = $this->getColumnByName($name);
            if ( $column !== false ) {
                if ( $column->type == WpColumnType::DATETIME || $column->type == WpColumnType::TIMESTAMP ) {
                    $data[$name] = $column->getDatetimeValueAsString( $value );
                } else if ( $column->type == WpColumnType::TIME ) {
                    $data[$name] = $column->getTimeValueAsString( $value );
                }
                $ret[] = $column->getQueryFormat();
            } else {
                $ret = false;
                sosidee_log("WpTable.getDataFormat() :: getColumnByName($name) returned false for table {$this->name}.");
                break;
            }
        }
        return $ret;
    }

    private function getFilterFormat( &$filters ) {
        $ret = array();
        foreach ( $filters as $name => $value ) {
            $column = $this->getColumnByName( $name );
            if ($column !== false) {
                if ( $column->type == WpColumnType::DATETIME || $column->type == WpColumnType::TIMESTAMP ) {
                    $filters[$name] = $column->getDatetimeValueAsString($value);
                } else if ( $column->type == WpColumnType::TIME  ) {
                    $filters[$name] = $column->getTimeValueAsString($value);
                }
                $ret[] = $column->getQueryFormat();
            } else {
                $ret = true;
                sosidee_log("WpTable.getFilterFormat() :: getColumnByName($name) returned false for table {$this->name}.");
                break;
            }
        }
        return $ret;
    }

    /**
     * @param string $what columns to be selected
     * @param array $filters: associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     *          'foo[>=]' => 123    means foo >= 123
     * @param array $orders mixed array [ column_name1, column_name2 => direction ] e.g. [ 'foo', 'bar' => 'DESC' ] note: foo is ASC (default)
     * @param bool $raw_result if true then results will not be converted to native data-types (using table fields)
     * @return array|false array of objects | false
     */
    private function querySelect( $what, $filters = [], $orders = [], $raw_result = false ) {
        global $wpdb;

        $ret = false;
        $error = false;
        $clauses = array();
        $values = array();
        foreach ( $filters as $key => $value ) {
            $clauses[] = array();
            $values[] = array();
            $index = count( $clauses ) - 1;

            $name = $key;
            $operator = '=';
            $p1 = strrpos($key, '[');
            if ( $p1 !== false ) {
                $name = trim( substr($key, 0, $p1) );
                $p2 = strrpos($key, ']');
                $operator = trim( substr($key, $p1+1, $p2 - $p1 - 1) );
            }
            $clauses[$index]['name'] = $name;
            $clauses[$index]['operator'] = $operator;

            $column = $this->getColumnByName($name);
            if ( $column !== false ) {
                if ( $column->type == WpColumnType::DATETIME || $column->type == WpColumnType::TIMESTAMP ) {
                    $values[$index] = $column->getDatetimeValueAsString($value);
                } else if ( $column->type == WpColumnType::TIME  ) {
                        $values[$index] = $column->getTimeValueAsString( $value );
                } else {
                    $values[$index] = $value;
                }
                $clauses[$index]['format'] = $column->getQueryFormat();
            } else {
                $error = true;
                sosidee_log("WpTable.querySelect() :: getColumnByName($name) returned false for table {$this->name}.");
                break;
            }
        }
        if( !$error ) {
            $sql = "SELECT {$what} FROM {$this->name}";
            if ( count($clauses) > 0 ) {
                $where = '';
                for ( $n=0; $n<count($clauses); $n++ ) {
                    if ($where != '') {
                        $where .= ' AND ';
                    }
                    $clause = $clauses[$n];
                    $where .= "{$clause['name']}{$clause['operator']}{$clause['format']}";
                }
                $sql .= " WHERE {$where}";
            }
            if ( count($orders) > 0 ) {
                $order_list = '';
                foreach ($orders as $key => $value) {
                    if ($order_list != '') {
                        $order_list .= ', ';
                    }
                    if ( is_int($key) ) {
                        $order_list .= $value;
                    } else {
                        $order_list .= "{$key} {$value}";
                    }
                }
                $sql .= " ORDER BY {$order_list}";
            }

            if ( count($values) > 0) {
                $query = $wpdb->prepare($sql, $values);
            } else {
                $query = $sql;
            }

            $results = $wpdb->get_results($query, ARRAY_A); // ARRAY_A | ARRAY_N | OBJECT (default) | OBJECT_K
            if ( is_array($results) ) {
                if ( $raw_result == false) {
                    if ( count($results) > 0 ) {
                        for ( $n=0; $n<count($results); $n++ ) {
                            $values = array();
                            $columns = &$results[$n]; //columns in the n^th row
                            foreach ( $columns as $name => $value ) {
                                $wpColumn = $this->getColumnByName($name);
                                if ( $wpColumn !== false ) {
                                    $values[$name] = $wpColumn->getNativeValueFromString( $value );
                                } else {
                                    sosidee_log( "WpTable.querySelect() :: {$this->name}.getColumnByName({$name}) returned false." );
                                }
                            }
                            $columns = json_decode( json_encode($columns), false );
                            // values must be assigned after the column conversion to object in order
                            // to prevent datetime values to be converted to standard objects (and then methods to be lost like tears in rain)
                            foreach ( $columns as $name => $value ) {
                                $columns->{$name} = $values[$name];
                            }
                            unset($columns);
                        }
                    }
                }
                $ret = $results;
            } else {
                sosidee_log( "WpTable.querySelect() :: wpdb.get_results($query) did not return an array:" . print_r($results, true) );
            }
        }
        return $ret;

    }

    /**
     * @param array $filters: associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     *          'foo[>=]' => 123    means foo >= 123
     * @param array $orders mixed array [ column_name1, column_name2 => direction ] e.g. [ 'foo', 'bar' => 'DESC' ] note: foo is ASC (default)
     * @return array|false: array of objects | false
     */
    public function select( $filters = [], $orders = [] ) {
        return $this->querySelect( '*', $filters, $orders );
    }

    /**
     * @param array $filters: associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     *          'foo[>=]' => 123    means foo >= 123
     * @return int|false: number of rows | false
     */
    public function count( $filters = [] ) {
        $field = $this->name .  "_count";
        $results = $this->querySelect( "COUNT(*) AS {$field}", $filters, [], true );
        if ( is_array($results) && count($results) == 1 ) {
            $row = $results[0];
            if ( isset($row[$field]) ) {
                return intval($row[$field]);
            } else {
                sosidee_log( "WpTable.count() :: querySelect() did not return the field {$field}: " . print_r($results, true) );
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param array $data : associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     * @return int|false : value of the new id or false in case of error
     */
    public function insert( $data ) {
        global $wpdb;

        $ret = false;

        for ($n=0; $n<count($this->columns); $n++) {
            $column = $this->columns[$n];
            if ( $column->currentTimestamp && !array_key_exists($column->name, $data) ) {
                $data[$column->name] = sosidee_current_datetime();
            }
        }

        $data_formats = $this->getDataFormat( $data );
        if( $data_formats !== false ) {
            if ( $wpdb->insert( $this->name, $data, $data_formats ) !== false ) {
                    $ret = $wpdb->insert_id;
            } else {
                sosidee_log("WpTable.insert() :: wpdb.insert($this->name, \$data, \$formats) returned false for \$data: " .  print_r($data, true) . " and \$data_formats:" . print_r($data_formats, true) );
            }
        } else {
            sosidee_log("WpTable.insert() :: problem with table {$this->name}, \$data: " . print_r($data, true) . " and \$data_formats:" . print_r($data_formats, true) );
        }
        return $ret;
    }

    /**
     * @param array $data : associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     * @param array $filters : associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     * @return bool
     */
    public function update( $data, $filters ) {
        global $wpdb;

        $ret = false;
        $data_formats = $this->getDataFormat( $data );
        if ( $data_formats !== false ) {
            $filter_formats = $this->getFilterFormat( $filters );
        } else {
            $filter_formats = array();
        }
        if ( $data_formats !== false && $filter_formats !== false ) {
            if ( $wpdb->update( $this->name, $data, $filters, $data_formats, $filter_formats ) !== false ) {
                $ret = true;
            } else {
                sosidee_log("WpTable.update() :: wpdb.update($this->name, \$data, \$filters, \$formats, \$filter_formats) returned false for \$data: " .  print_r($data, true) . ", \$filters:" . print_r($filters, true) . ", \$data_formats:" . print_r($data_formats, true) . " and \$filter_formats:" . print_r($filter_formats, true) );
            }
        } else {
            sosidee_log("WpTable.update() :: problem with table {$this->name}, \$data: " . print_r($data, true) . ", \$filters:" . print_r($filters, true) . ", \$data_formats:" . print_r($data_formats, true) . " and \$filter_formats:" . print_r($filter_formats, true) );
        }
        return $ret;
    }

    /**
     * @param array $filters : associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     * @return bool
     */
    public function delete( $filters ) {
        global $wpdb;

        $ret = false;
        $error = false;
        $filter_formats = $this->getFilterFormat( $filters );
        if ( $filter_formats !== false ) {
            if ( $wpdb->delete( $this->name, $filters, $filter_formats ) !== false ) {
                $ret = true;
            } else {
                sosidee_log("WpTable.delete() :: wpdb.delete($this->name, \$filters, \$filter_formats) returned false for \$filters:" . print_r($filters, true) . " and \$filter_formats:" . print_r($filter_formats, true) );
            }
        } else {
            sosidee_log("WpTable.delete() :: problem with table {$this->name}, \$filters:" . print_r($filters, true) . " and \$filter_formats:" . print_r($filter_formats, true) );
        }
        return $ret;
    }

    /***
     * multiple insert
     * @param $data : array of associative arrays - e.g. [ ['x'=>1, 'y'=>'foo'], ['x'=>6, 'y'=>'bar'] ]
     * @return false|int : number of rows affected
     ***/
    public function inserts( $rows ) {
        if (count($rows) == 0) {
            sosidee_log("WpDatabase.inserts() : rows is empty.");
            return false;
        }
        global $wpdb;

        $sql = "INSERT INTO {$this->name} (";
        $sql .= implode( ",", array_keys($rows[0]) );
        $sql .= ") VALUES";
        for ($n=0; $n<count($rows); $n++) {
            if ($n > 0) {
                $sql .= ",";
            }
            $data = $rows[$n];
            $data_formats = $this->getDataFormat( $data );
            $formatted_sql = " (" . implode(",", $data_formats) . ")" ;
            $values = array();
            foreach ( $data as $_ => $value ) {
                $values[] = $value;
            }
            $sql .= $wpdb->prepare($formatted_sql, $values);
        }
        $ret = $wpdb->query($sql);
        if ( is_null($ret) ) {
            $ret = false;
            sosidee_log("WpTable.inserts() :: wpdb.query($sql) returned null.");
        }
        return $ret;
    }


    public function get( $filters = [], $orders = [] ) {
        $results = $this->select( $filters, $orders );
        if ( is_array($results) ) {
            if ( count($results) == 1 ) {
                return $results[0];
            } else {
                if ( count($results) > 1 ) {
                    sosidee_log("WpTable.get() :: select() in table {$this->name} returned a wrong array length: " . count($results) . " (requested: 1) for \$filters: " . print_r($filters, true) );
                }
                return false;
            }
        } else {
            sosidee_log("WpTable.get() :: select() in table {$this->name} did not return an array for \$filters: " . print_r($filters, true) );
            return false;
        }
    }

    /**
     * @param array $fields: array [ column_name_1, column_name_2, ... ]
     * @param array $filters: associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     *          'foo[>=]' => 123    means foo >= 123
     * @param array $orders mixed array [ column_name1, column_name2 => direction ] e.g. [ 'foo', 'bar' => 'DESC' ] note: foo is ASC (default)
     * @return array|false: array of objects | false
     */
    public function distinct( $fields, $filters = [], $orders = [] ) {
        $what = "DISTINCT " . implode(',', $fields);
        return $this->querySelect( $what, $filters, $orders );
    }

}