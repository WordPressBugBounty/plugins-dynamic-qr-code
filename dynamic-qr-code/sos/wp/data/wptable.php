<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP_ROOT;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class WpTable
{

    protected $columns;
    protected $primaryKey;
    public $name;
    public $charset;

    public function __construct($name) {
        $this->primaryKey = false;

        $this->name = $name;
        $this->columns = array();
    }

    public function addColumn( $name, $type, $length = '' ) {
        $ret = new WpColumn( $name, $type, $length );
        $this->columns[] = $ret;
        return $ret;
    }

    public function addID( $name = 'id' ) {
        $ret = $this->addColumn( $name, WpColumnType::INTEGER );
        $ret->autoIncrement = true;
        $this->primaryKey = $name;
        return $ret;
    }

    public function addInteger( $name ) {
        return $this->addColumn( $name, WpColumnType::INTEGER );
    }

    public function addTinyInteger( $name ) {
        return $this->addColumn( $name, WpColumnType::TINY_INTEGER );
    }

    public function addSmallInteger( $name ) {
        return $this->addColumn( $name, WpColumnType::SMALL_INTEGER );
    }

    public function addBoolean( $name ) {
        return $this->addColumn( $name, WpColumnType::BOOLEAN );
    }

    public function addCurrency( $name ) {
        return $this->addColumn( $name, WpColumnType::CURRENCY );
    }

    public function addVarChar( $name, $length = '' ) {
        return $this->addColumn( $name, WpColumnType::VARCHAR, $length );
    }

    public function addDateTime( $name ) {
        return $this->addColumn( $name, WpColumnType::DATETIME );
    }

    public function addTime( $name ) {
        return $this->addColumn( $name, WpColumnType::TIME );
    }


    protected function getColumnByName( $name ) {
        $ret = false;
        foreach ( $this->columns as $column ) {
            if ( strcasecmp($column->name, $name) == 0 ) {
                $ret = $column;
                break;
            }
        }
        return $ret;
    }

    public function getCommandSql() {
        $ret = "CREATE TABLE `{$this->name}` (";

        $count = count($this->columns);
        if ( $count > 0 ) {
            for ( $n=0; $n<$count; $n++ ) {
                if ( $n > 0 ) {
                    $ret .= ",";
                }
                $ret .= PHP_EOL . $this->columns[$n]->getCommandSql();
            }
            if ( !empty($this->primaryKey) ) {
                $ret .= "," . PHP_EOL . "PRIMARY KEY  (`{$this->primaryKey}`)";
            }
        }
        $ret .= PHP_EOL . ") " . $this->charset;
        return $ret;
    }

    public function getSchemaHash() {
        $sql = $this->getCommandSql();
        $sql = preg_replace('/--.*$/m', '', $sql);       // remove comments --
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // remove comments /* */
        // substitute tab and newline with a space
        $sql = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $sql);
        // substitute multiple spaces with single ones
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = trim($sql);
        return md5($sql);
    }

    private function getDataFormat( &$data ) {
        $ret = array();
        foreach ( $data as $name => $value ) {
            $column = $this->getColumnByName($name);
            if ( $column !== false ) {
                if ( in_array( $column->type, [ WpColumnType::DATETIME, WpColumnType::TIMESTAMP] ) ) {
                    $data[$name] = $column->getDatetimeValueAsSqlString( $value );
                } else if ( $column->type == WpColumnType::TIME ) {
                    $data[$name] = $column->getTimeValueAsSqlString( $value );
                }
                $ret[] = $column->getQueryFormat();
            } else {
                $ret = false;
                sosidee_log("WpTable::getDataFormat() - getColumnByName($name) returned false for table {$this->name}.");
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
                if ( in_array( $column->type, [ WpColumnType::DATETIME, WpColumnType::TIMESTAMP] ) ) {
                    $filters[$name] = $column->getDatetimeValueAsSqlString($value);
                } else if ( $column->type == WpColumnType::TIME  ) {
                    $filters[$name] = $column->getTimeValueAsSqlString($value);
                }
                $ret[] = $column->getQueryFormat();
            } else {
                $ret = false;
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
     * @param array $orders mixed array [ column_name1, column_name2 => direction ] e.g. [ 'foo', 'bar' => 'DESC' ] note: default direction is ASC
     * @param bool $raw_result if true then results will not be converted to native data-types (using table fields)
     * @return array|false array of objects | false
     */
    private function querySelect( $what, $filters = [], $orders = [], $raw_result = false ) {
        global $wpdb;

        $ret = false;
        $error = false;
        $clauses = array();
        $values = array();
        $filter_in = false;
        foreach ( $filters as $key => $value ) {
            $clauses[] = array();
            $values[] = array();
            $index = count( $clauses ) - 1;

            $name = $key;
            $operator = !is_array($value) ? '=' : ' IN ';
            $p1 = strrpos($key, '[');
            if ( $p1 !== false ) {
                $name = trim( substr($key, 0, $p1) );
                $p2 = strrpos($key, ']');
                $operator = trim( substr($key, $p1 + 1, $p2 - $p1 - 1) );
            }
            $clauses[$index]['name'] = $name;
            $clauses[$index]['operator'] = $operator;

            $column = $this->getColumnByName($name);
            if ( $column !== false ) {
                if ( in_array($column->type, [WpColumnType::DATETIME, WpColumnType::TIMESTAMP]) ) {
                    $values[$index] = $column->getDatetimeValueAsSqlString($value);
                } elseif ( $column->type == WpColumnType::TIME  ) {
                    $values[$index] = $column->getTimeValueAsSqlString( $value );
                } else {
                    if ( is_array($value) ) {
                        if ( in_array($column->type, [WpColumnType::TEXT, WpColumnType::VARCHAR]) ) {
                            for ( $i=0; $i<count($value); $i++ ) {
                                $value[$i] = esc_sql((string)$value[$i]);
                            }
                        } elseif ( $column->type == WpColumnType::BOOLEAN ) {
                            for ( $i=0; $i<count($value); $i++ ) {
                                $value[$i] = boolval($value[$i]) ? 1 : 0;
                            }
                        }
                        $filter_in = true;
                    }
                    $values[$index] = $value;
                }
                if ( !is_array($value) ) {
                    $format = $column->getQueryFormat();
                } else {
                    $placeholders = array_fill(0, count($value), $column->getQueryFormat());
                    $format = "(" . implode(',', $placeholders) . ")";
                }
                $clauses[$index]['format'] = $format;
            } else {
                $error = true;
                sosidee_log("WpTable.querySelect() :: getColumnByName($name) returned false for table {$this->name}.");
                break;
            }
        }
        if( !$error ) {
            $sql = "SELECT {$what} FROM `{$this->name}`";
            if ( count($clauses) > 0 ) {
                $where = '';
                foreach ( $clauses as $clause ) {
                    if ( $where != '' ) {
                        $where .= ' AND ';
                    }
                    $where .= "`{$clause['name']}`{$clause['operator']}{$clause['format']}";
                }
                $sql .= " WHERE {$where}";
            }
            if ( count($orders) > 0 ) {
                $order_clauses = [];
                foreach ( $orders as $key => $value ) {
                    $column_name = is_int($key) ? $value : $key;
                    $direction = is_int($key) ? 'ASC' : strtoupper($value);
                    if ( $this->getColumnByName($column_name) !== false && in_array( $direction, ['ASC', 'DESC'] ) ) {
                        $order_clauses[] = "`" . esc_sql($column_name) . "` " . $direction;
                    }
                }
                if (!empty($order_clauses)) {
                    $sql .= " ORDER BY " . implode(', ', $order_clauses);
                }
            }

            if ( count($values) > 0) {
                if ( $filter_in ) {
                    // appiattisce l'array per gestire il filtro 'IN (...)'
                    $values = array_merge(...array_map(fn($i) => is_array($i) ? $i : [$i], $values));
                }
                $query = $wpdb->prepare($sql, $values);
            } else {
                $query = $sql;
            }

            $results = $wpdb->get_results($query, ARRAY_A); // ARRAY_A | ARRAY_N | OBJECT (default) | OBJECT_K
            if ( is_array($results) ) {
                if ( $raw_result == false && count($results) > 0 ) {
                    $typed_results = array();
                    foreach ( $results as $data ) {
                        $row = new \stdClass();
                        foreach ( $data as $name => $value ) {
                            $wpColumn = $this->getColumnByName($name);
                            if ( $wpColumn !== false ) {
                                $row->{$name} = $wpColumn->getNativeValueFromString( $value );
                            } else {
                                $row->{$name} = $value; //could be a JOIN or an alias...
                                sosidee_log( "WpTable.querySelect(): returned column {$name} not found in the definition of table {$this->name}." );
                            }
                        }
                        $typed_results[] = $row;
                    }
                    $ret = $typed_results;
                } else {
                    $ret = $results;
                }
            } else {
                sosidee_log( "WpTable::querySelect() - wpdb.get_results($query) did not return an array:" . print_r($results, true) );
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
        $results = $this->querySelect( "COUNT(*) AS `{$field}`", $filters, [], true );
        if ( is_array($results) && count($results) == 1 ) {
            $row = $results[0];
            if ( isset($row[$field]) ) {
                return intval($row[$field]);
            } else {
                sosidee_log( "WpTable::count() - querySelect() did not return the field {$field}: " . print_r($results, true) );
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

        foreach ( $this->columns as $column ) {
            if ( $column->currentTimestamp && !array_key_exists($column->name, $data) ) {
                $data[$column->name] = sosidee_current_datetime();
            }
        }

        $data_formats = $this->getDataFormat( $data );
        if( $data_formats !== false ) {
            if ( $wpdb->insert( $this->name, $data, $data_formats ) !== false ) {
                    $ret = $wpdb->insert_id;
            } else {
                sosidee_log("WpTable::insert() - wpdb.insert($this->name, \$data, \$formats) returned false for \$data: " .  print_r($data, true) . " and \$data_formats:" . print_r($data_formats, true) );
            }
        } else {
            sosidee_log("WpTable::insert() - problem with table {$this->name}, \$data: " . print_r($data, true) . " and \$data_formats:" . print_r($data_formats, true) );
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
     * @param bool $return_count : if true returns int (number of updated rows), otherwise bool
     * @return bool | int
     */
    public function update( $data, $filters, $return_count = false ) {
        global $wpdb;

        $ret = false;
        $data_formats = $this->getDataFormat( $data );
        if ( $data_formats !== false ) {
            $filter_formats = $this->getFilterFormat( $filters );
        } else {
            $filter_formats = array();
        }
        if ( $data_formats !== false && $filter_formats !== false ) {
            $result = $wpdb->update( $this->name, $data, $filters, $data_formats, $filter_formats );
            if ( $result !== false ) {
                $ret = $return_count ? $result : true;
            } else {
                sosidee_log("WpTable::update() - wpdb.update($this->name, \$data, \$filters, \$formats, \$filter_formats) returned false for \$data: " .  print_r($data, true) . ", \$filters:" . print_r($filters, true) . ", \$data_formats:" . print_r($data_formats, true) . " and \$filter_formats:" . print_r($filter_formats, true) );
            }
        } else {
            sosidee_log("WpTable::update() - problem with table {$this->name}, \$data: " . print_r($data, true) . ", \$filters:" . print_r($filters, true) . ", \$data_formats:" . print_r($data_formats, true) . " and \$filter_formats:" . print_r($filter_formats, true) );
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
        $filter_formats = $this->getFilterFormat( $filters );
        if ( $filter_formats !== false ) {
            if ( $wpdb->delete( $this->name, $filters, $filter_formats ) !== false ) {
                $ret = true;
            } else {
                sosidee_log("WpTable::delete() - wpdb.delete($this->name, \$filters, \$filter_formats) returned false for \$filters:" . print_r($filters, true) . " and \$filter_formats:" . print_r($filter_formats, true) );
            }
        } else {
            sosidee_log("WpTable::delete() - problem with table {$this->name}, \$filters:" . print_r($filters, true) . " and \$filter_formats:" . print_r($filter_formats, true) );
        }
        return $ret;
    }

    /***
     * multiple insert in a table
     * @param array $data array of associative arrays - e.g. [ ['x'=>1, 'y'=>'foo'], ['x'=>6, 'y'=>'bar'] ]
     * @return false|int : number of rows affected
     ***/
    public function inserts( $rows ) {
        if ( empty($rows) ) {
            sosidee_log("WpDatabase::inserts() - array rows has no element.");
            return false;
        }
        global $wpdb;

        $columns = array_map( fn($col) => "`" . trim($col, '`') . "`", array_keys($rows[0]) );
        $sql = "INSERT INTO `{$this->name}` (". implode( ",", $columns ) . ") VALUES ";
        for ( $n=0; $n<count($rows); $n++ ) {
            if ( $n > 0 ) {
                $sql .= ",";
            }
            $data = $rows[$n];
            $data_formats = $this->getDataFormat( $data );
            $formatted_sql = "(" . implode(",", $data_formats) . ")" ;
            $values = array();
            foreach ( $data as $_ => $value ) {
                $values[] = $value;
            }
            $sql .= $wpdb->prepare($formatted_sql, $values);
        }
        $ret = $wpdb->query($sql);
        if ( is_null($ret) ) {
            $ret = false;
            sosidee_log("WpTable::inserts() - wpdb.query($sql) returned null.");
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
                    sosidee_log("WpTable::get() - select() in table {$this->name} returned a wrong array length: " . count($results) . " (requested: 1) for \$filters: " . print_r($filters, true) );
                }
                return false;
            }
        } else {
            sosidee_log("WpTable::get() - select() in table {$this->name} did not return an array for \$filters: " . print_r($filters, true) );
            return false;
        }
    }

    /**
     * @param array $columns array [ column_name_1, column_name_2, ... ]
     * @param array $filters associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     *          'foo[>=]' => 123    means foo >= 123
     * @param array $orders mixed array [ column_name1, column_name2 => direction ] e.g. [ 'foo', 'bar' => 'DESC' ] note: foo is ASC (default)
     * @return array|false: array of objects | false
     */
    public function distinct( $columns, $filters = [], $orders = [] ) {
        $columns = array_map( fn($col) => "`" . trim($col, '`') . "`", $columns );
        $what = "DISTINCT " . implode(',', $columns);
        return $this->querySelect( $what, $filters, $orders );
    }

    /**
     * @param array $columns array [ column_name_1, column_name_2, ... ]
     * @param array $filters associative array [ column_name => value ]
     *      e.g.
     *          'foo' => 123        means foo = 123
     *          'foo[>=]' => 123    means foo >= 123
     * @param array $orders mixed array [ column_name1, column_name2 => direction ] e.g. [ 'foo', 'bar' => 'DESC' ] note: foo is ASC (default)
     * @return array|false: array of objects | false
     */
    public function selectColumns( $columns, $filters = [], $orders = [] ) {
        $columns = array_map( fn($col) => "`" . trim($col, '`') . "`", $columns );
        $what = implode(',', $columns);
        return $this->querySelect( $what, $filters, $orders );
    }

}