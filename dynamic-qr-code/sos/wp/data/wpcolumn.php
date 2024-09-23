<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/***
 * Note: this class has not the property 'value'
 * It's not a data container, therefore data variables must be stored elsewhere
 */
class WpColumn
{
    protected $parent; //name

    public $name;
    public $type;
    public $defaultValue;
    public $autoIncrement;
    public $nullable;
    public $length;
    public $currentTimestamp;

    public function __construct( $parent, $name, $type, $length = '' ) {
        $this->parent = $parent;

        $this->name = $name;
        $this->type = $type;
        $this->length = $length != '' ? $length : $this->getLengthByType($type);

        $this->defaultValue = null;
        $this->autoIncrement = false;
        $this->nullable = false;

        $this->currentTimestamp = false;
    }

    private function getLengthByType( $type ) {
        $ret = '';
        switch ($type) {
            case WpColumnType::VARCHAR:
                $ret = '255';
                break;
            case WpColumnType::INTEGER:
                $ret = '11';
                break;
            case WpColumnType::TINY_INTEGER:
                $ret = '4';
                break;
            case WpColumnType::SMALL_INTEGER:
                $ret = '6';
                break;
            case WpColumnType::CURRENCY:
                $ret = '10,2';
                break;
            case WpColumnType::BOOLEAN:
                $ret = '1';
                break;
        }
        return $ret;
    }

    public function getDatetimeValueAsString( $value, $quoted = false ) {
        if ( is_null($value) && $this->nullable ) {
            return null;
        } else {
            return Db::getDatetimeAsString( $value, $quoted );
        }
    }

    private function getDatetimeValueFromString( $value ) {
        if ( is_null($value) && $this->nullable ) {
            return null;
        } else {
            return Db::getDatetimeFromString( $value );
        }
    }

    public function getTimeValueAsString( $value, $quoted = false ) {
        if ( is_null($value) && $this->nullable ) {
            return null;
        } else {
            return Db::getTimeAsString( $value, $quoted );
        }
    }

    private function getTimeValueFromString( $value ) {
        if ( is_null($value) && $this->nullable ) {
            return null;
        } else {
            return Db::getTimeFromString( $value );
        }
    }

    public function setDefaultValue( $value ) {
        $this->defaultValue = $value;
        return $this;
    }

    public function setDefaultValueAsCurrentDateTime() {
        //$this->setDefaultValue( 'CURRENT_TIMESTAMP' ); // database can have a timezone different from server
        $this->currentTimestamp = true;
        return $this;
    }

    protected function getValueAsSqlString($value) {
        if ( is_null($value) && $this->nullable ) {
            return null;
        }

        $ret = '';
        switch ($this->type) {
            case WpColumnType::BOOLEAN:
                $ret = boolval($value) ? '1' : '0';
                break;
            case WpColumnType::INTEGER:
            case WpColumnType::TINY_INTEGER:
            case WpColumnType::SMALL_INTEGER:
            case WpColumnType::DOUBLE:
            case WpColumnType::DECIMAL:
            case WpColumnType::CURRENCY:
                $ret = strval($value);
                break;
            case WpColumnType::TEXT:
            case WpColumnType::VARCHAR:
                $ret = "'" . esc_sql($value) . "'";
                break;
            case WpColumnType::DATETIME:
            case WpColumnType::TIMESTAMP:
                $ret = $this->getDatetimeValueAsString($value, true);
                break;
            case WpColumnType::TIME:
                $ret = $this->getTimeValueAsString($value, true);
                break;
        }
        return $ret;
    }

    public function getNativeValueFromString( $value ) {
        if ( is_null($value) && $this->nullable ) {
            return null;
        }

        $ret = $value;
        switch ($this->type) {
            case WpColumnType::BOOLEAN:
                $ret = boolval($value);
                break;
            case WpColumnType::INTEGER:
            case WpColumnType::TINY_INTEGER:
            case WpColumnType::SMALL_INTEGER:
                $ret = intval($value);
                break;
            case WpColumnType::FLOAT:
                $ret = floatval($value);
                break;
            case WpColumnType::DECIMAL:
            case WpColumnType::CURRENCY:
            case WpColumnType::DOUBLE:
                $ret = doubleval($value);
                break;
            case WpColumnType::DATETIME:
            case WpColumnType::TIMESTAMP:
                $ret = $this->getDatetimeValueFromString( $value );
                break;
            case WpColumnType::TIME:
                $ret = $this->getTimeValueFromString( $value );
                break;
        }
        return $ret;
    }

    public function getCommandSql() {
        $ret = "{$this->name} {$this->type}";
        if ($this->length != '') {
            $ret .= "({$this->length})";
        }
        $ret .= $this->nullable ? " NULL" : " NOT NULL";
        if ( !is_null($this->defaultValue) ) {
            $ret .= " DEFAULT " . $this->getValueAsSqlString( $this->defaultValue );
        }
        if ( $this->autoIncrement ) {
            $ret .= " AUTO_INCREMENT";
        }
        return $ret;
    }

    public function getQueryFormat() {
        switch ($this->type) {
            case WpColumnType::BOOLEAN:
            case WpColumnType::INTEGER:
            case WpColumnType::TINY_INTEGER:
            case WpColumnType::SMALL_INTEGER:
                $ret = '%d';
                break;
            case WpColumnType::CURRENCY:
            case WpColumnType::DECIMAL:
            case WpColumnType::FLOAT:
            case WpColumnType::DOUBLE:
                $ret = '%f';
                break;
            default:
                $ret = '%s';
        }
        return $ret;
    }

}