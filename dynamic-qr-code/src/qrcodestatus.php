<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class QrCodeStatus
{
    const NONE = 0;
    const ACTIVE = 1;
    const INACTIVE = 6;
    const DISABLED = -1;
    const FINISHED = -2;
    const EXPIRED = -3;
    const ERROR = -5;
}