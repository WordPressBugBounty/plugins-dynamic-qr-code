<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/***
 * Note: this class has not the property 'value'
 * It's not a data container, therefore data variables must be stored elsewhere
 */
class WpColumn
{

    use TSqlDateTime;

    public $name;
    public $type;
    public $defaultValue;
    public $autoIncrement;
    public $nullable;
    public $length;
    public $currentTimestamp;

    public function __construct( $name, $type, $length = '' ) {

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

    public function setDefaultValue( $value ) {
        $this->defaultValue = $value;
        return $this;
    }

    public function setNullable( $value = true ) {
        $this->nullable = $value;
        return $this;
    }

    public function setDefaultValueAsCurrentDateTime() {
        //$this->setDefaultValue( 'CURRENT_TIMESTAMP' ); // database can have a timezone different from server
        $this->currentTimestamp = true;
        return $this;
    }

    public function getNativeValueFromString( $value ) {
        if ( is_null($value) ) {
            if ( !$this->nullable ) {
                sosidee_log("WpColumn::getNativeValueFromString() - value is null for not nullable column '{$this->name}' of type {$this->type}.");
            }
            return null;
        }

        $ret = $value;
        switch ($this->type) {
            case WpColumnType::BOOLEAN:
                if ( is_string($value) ) {
                    $value = strtolower(trim($value));
                }
                if ( (string)$value == 1 ) {
                    $value = true;
                } elseif ( $value === '0' || $value === 'false' || $value === 0 || $value === '' ) {
                    $value = false;
                }
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
                $ret = (string)$value; // casting to string preserves precision
                break;
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
            case WpColumnType::VARCHAR:
            case WpColumnType::TEXT:
            // case WpColumnType::CHAR:
                $ret = (string)$value;
                break;
            default:
                sosidee_log("WpColumn::getNativeValueFromString() - No handled case for column '{$this->name}' of type {$this->type}.");
        }
        return $ret;
    }

    protected function getValueAsSqlString($value) {
        if ( is_null($value) ) {
            if ( !$this->nullable ) {
                sosidee_log("WpColumn::getValueAsSqlString() - value is null for not nullable column '{$this->name}' of type {$this->type}.");
            }
            return 'NULL';
        }
        switch ($this->type) {

            case WpColumnType::BOOLEAN:
                return boolval($value) ? '1' : '0';

            case WpColumnType::INTEGER:
            case WpColumnType::TINY_INTEGER:
            case WpColumnType::SMALL_INTEGER:
            case WpColumnType::DOUBLE:
            case WpColumnType::DECIMAL:
            case WpColumnType::CURRENCY:
                return strval($value);

            case WpColumnType::TEXT:
            case WpColumnType::VARCHAR:
                return "'" . esc_sql($value) . "'";

            case WpColumnType::DATETIME:
            case WpColumnType::TIMESTAMP:
                return $this->getDatetimeValueAsSqlString($value, true);

            case WpColumnType::TIME:
                return $this->getTimeValueAsSqlString($value, true);

            default:
                sosidee_log("WpColumn::getValueAsSqlString() - Cannot get sql string value for column '{$this->name}' of type {$this->type}; value=" . print_r($value, true));
                return 'NULL';
        }
    }

    public function getCommandSql() {
        $type = $this->type != WPColumnType::BOOLEAN ? $this->type : WPColumnType::TINY_INTEGER;
        $ret = "`{$this->name}` {$type}";
        if ( !empty($this->length) ) {
            $ret .= "({$this->length})";
        }
        $ret .= $this->nullable ? " NULL" : " NOT NULL";
        $ret .= " DEFAULT ";
        if ( !is_null($this->defaultValue) ) {
            $ret .= $this->getValueAsSqlString( $this->defaultValue );
        } else {
            if ( !$this->nullable ) {
                $ret .= $this->getDefaultValueAsSqlString();
            } else {
                $ret .= "NULL";
            }
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

    private function getDefaultValueAsSqlString() {
        switch ($this->type) {
            case WpColumnType::VARCHAR:
            case WpColumnType::TEXT:
                // case WpColumnType::CHAR:
            return self::getQuoted('', true);

            case WpColumnType::BOOLEAN: // TINYINT
                return "0";

            case WpColumnType::INTEGER:
            case WpColumnType::TINY_INTEGER:
            case WpColumnType::SMALL_INTEGER:
                // case WpColumnType::MEDIUMINT:
                // case WpColumnType::BIGINT:
                return "0";

            case WpColumnType::FLOAT:
            case WpColumnType::DOUBLE:
                return "0.0";

            case WpColumnType::DECIMAL: // include CURRENCY
                return "0.00";

            case WpColumnType::DATETIME:
            case WpColumnType::TIMESTAMP:
                return $this->getNullDatetimeAsSqlString(true);
                //return "'0000-00-00 00:00:00'";

            // case WpColumnType::DATE:
            //    return "'0000-00-00'";

            case WpColumnType::TIME:
                return $this->getNullTimeAsSqlString(true);
                //return "'00:00:00'";

            // case WpColumnType::YEAR:
            //    return "'0000'";

            default:
                sosidee_log("WpColumn::getDefaultValueAsSqlString() - No known implicit default value for column '{$this->name}' of type {$this->type}.");
                return 'NULL';
        }
    }

}