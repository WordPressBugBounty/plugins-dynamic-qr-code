<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class FormFieldType
{
    const TEXT = 1;
    const TEXTAREA = 2;
    const CHECK = 3;
    const SELECT = 4;
    const COLOR = 5;
    const NUMBER = 6;
    const DATE = 7;
    const TIME = 8;
    const HIDDEN = 9;
    const COMBOBOX = 10;
    const FILE = 11;
}