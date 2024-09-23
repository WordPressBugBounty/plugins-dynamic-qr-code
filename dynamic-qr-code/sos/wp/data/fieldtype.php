<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\DATA;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class FieldType
{
    const LABEL = 0;
    const TEXT = 1;
    const TEXTAREA = 2;
    const CHECK = 3;
    const SELECT = 4;
    const OPTION = 5; //radio button
    const NUMBER = 6;
    const CHECKLIST = 7;
    const COMBOBOX = 8;
    const COLOR = 9;
    const HIDDEN = 10;
}