<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class Table
{
    use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TMessage;

    protected static $db = null;

    protected $table;
    protected $columns;

    public function __construct($name) {
        $this->table = self::$db->addTable($name);
        $this->columns = array();
    }

    private function convert( $records ) {
        if ( !is_array($records) ) {
            return $records;
        }
        for ( $n=0; $n<count($records); $n++) {
            $rec = &$records[$n];
            foreach ( $this->columns as $field => $column ) {
                if ( $field !== $column ) {
                    if ( property_exists($rec, $column) ) {
                        $rec->{$field} = $rec->{$column};
                        unset($rec->{$column});
                    }
                }
            }
            unset($rec);
        }
        return $records;
    }

    public function addID($column = 'id', $name = false) {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        $this->table->primaryKey = $name;
        $ret = $this->table->addID($name);
        $ret->autoIncrement = true;
        return $ret;
    }

    public function addBoolean($column, $name = false) {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        return $this->table->addBoolean($name);
    }

    public function addTinyInteger($column, $name = false) {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        return $this->table->addTinyInteger($name);
    }

    public function addSmallInteger($column, $name = false) {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        return $this->table->addSmallInteger($name);
    }

    public function addInteger($column, $name = false) {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        return $this->table->addInteger($name);
    }

    public function addCurrency($column, $name = false) {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        return $this->table->addCurrency($name);
    }

    public function addVarChar($column, $name = false, $length = '') {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        return $this->table->addVarChar($name, $length);
    }

    public function addDateTime($column, $name = false) {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        return $this->table->addDateTime($name);
    }

    public function addTime($column, $name = false) {
        if ( $name === false) {
            $name = $column;
        }
        $this->columns[$column] = $name;
        return $this->table->addTime($name);
    }

    public function select( $filters = [], $orders = [] ) {
        $results = $this->table->select( $filters, $orders );
        return $this->convert($results);
    }
    public function update( $data, $filters ) {
        return $this->table->update( $data, $filters );
    }
    public function insert( $data ) {
        return $this->table->insert( $data );
    }

    public static function setDb( $database ) {
        self::$db = $database;
    }

}