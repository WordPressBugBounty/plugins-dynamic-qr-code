<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );


class WpColumnType
{
    const BOOLEAN = 'BOOLEAN';

    const INTEGER = 'INT';
    const TINY_INTEGER = 'TINYINT';
    const SMALL_INTEGER = 'SMALLINT';

    const FLOAT = 'FLOAT';
    const DOUBLE = 'DOUBLE';
    const DECIMAL = 'DECIMAL';
    const CURRENCY = 'DECIMAL';

    const DATETIME = 'DATETIME';
    const TIMESTAMP = 'TIMESTAMP';
    const TIME = 'TIME';

    const VARCHAR = 'VARCHAR';
    const TEXT = 'TEXT';
}