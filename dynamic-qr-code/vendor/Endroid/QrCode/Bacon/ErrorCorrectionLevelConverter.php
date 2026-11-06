<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Bacon;

use SOSIDEE_DYNAMIC_QRCODE\vendor\BaconQrCode\Common\ErrorCorrectionLevel as BaconErrorCorrectionLevel;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\ErrorCorrectionLevel;
final readonly class ErrorCorrectionLevelConverter
{
    public static function convertToBaconErrorCorrectionLevel(ErrorCorrectionLevel $errorCorrectionLevel): BaconErrorCorrectionLevel
    {
        return match ($errorCorrectionLevel) {
            ErrorCorrectionLevel::Low => BaconErrorCorrectionLevel::valueOf('L'),
            ErrorCorrectionLevel::Medium => BaconErrorCorrectionLevel::valueOf('M'),
            ErrorCorrectionLevel::Quartile => BaconErrorCorrectionLevel::valueOf('Q'),
            ErrorCorrectionLevel::High => BaconErrorCorrectionLevel::valueOf('H'),
        };
    }
}
