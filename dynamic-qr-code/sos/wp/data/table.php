<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP as SOS_WP;

class Table
{
    use SOS_WP\TBase, SOS_WP\TMessage;

    protected $_native; // WpTable
    protected $_columns;
    protected $_name;

    public function __construct( $name ) {
        $this->_name = $name;
        $this->_columns = array();
    }

    public function getColumnNameList() {
        return array_keys($this->_columns);
    }

    /***
     * adds the columns to the derived class
     * it must be overridden!
     */
    public function defineSchema() {}

    /***
     * @param $database : WpDatabase
     * @return void
     */
    public function create( $database ) {
        $this->_native = $database->addTable( $this->_name );
        $this->defineSchema();

        $key = self::plugin()->key;
        do_action( $key . "_database_define_schema_{$this->_name}", $this );
    }

    private function convert( $records ) {
        if ( !is_array($records) ) {
            return $records;
        }
        if ( empty( array_diff_assoc( array_flip($this->_columns), $this->_columns ) ) ) {
            return $records; // $field == $column for all items
        }
        foreach ( $records as $rec ) {
            foreach ( $this->_columns as $field => $column ) {
                if ( $field !== $column && property_exists($rec, $column) ) {
                    $rec->{$field} = $rec->{$column};
                    unset($rec->{$column});
                }
            }
        }
        return $records;
    }

    /***
     * @param string $column the actual column name in the database table
     * @param string $field the logical field name used in the code
     * @return WpColumn
     */
    public function addID( $column = 'id', $field = '' ) {
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addID( $column );
    }

    public function addBoolean( $column, $field = '' ) {
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addBoolean( $column );
    }

    public function addTinyInteger( $column, $field = '' ) {
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addTinyInteger( $column );
    }

    public function addSmallInteger( $column, $field = '' ) {
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addSmallInteger( $column );
    }

    public function addInteger( $column, $field = '' ) {
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addInteger($column);
    }

    public function addCurrency( $column, $field = '' ) {
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addCurrency( $column );
    }

    public function addVarChar( $column, $field = '', $length = '') {
        if ( is_numeric($field) ) {
            $length = $field;
            $field = '';
        }
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addVarChar( $column, $length );
    }

    public function addDateTime( $column, $field = '' ) {
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addDateTime( $column );
    }

    public function addTime( $column, $field = '' ) {
        if ( empty($field) ) {
            $field = $column;
        }
        $this->_columns[$column] = $field;
        return $this->_native->addTime( $column );
    }

    public function select( $filters = [], $orders = [] ) {
        $results = $this->_native->select( $filters, $orders );
        return $this->convert($results);
    }
    public function update( $data, $filters, $return_count = false ) {
        return $this->_native->update( $data, $filters, $return_count );
    }
    public function insert( $data ) {
        return $this->_native->insert( $data );
    }

    public function distinct( $columns, $filters, $orders ) {
        if ( !is_array( $columns ) ) {
            $columns = [$columns];
        }
        return $this->_native->distinct( $columns, $filters, $orders );
    }

    public function count( $filters = [] ) {
        return $this->_native->count( $filters );
    }

    public function selectFields( $fields, $filters, $orders ) {
        if ( !is_array( $fields ) ) {
            $fields = [$fields];
        }
        return $this->_native->selectColumns( $fields, $filters, $orders );
    }

}