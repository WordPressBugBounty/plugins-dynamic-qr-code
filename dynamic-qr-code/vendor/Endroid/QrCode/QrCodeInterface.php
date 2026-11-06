<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Color\ColorInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Encoding\EncodingInterface;
interface QrCodeInterface
{
    public function getData(): string;
    public function getEncoding(): EncodingInterface;
    public function getErrorCorrectionLevel(): ErrorCorrectionLevel;
    public function getSize(): int;
    public function getMargin(): int;
    public function getRoundBlockSizeMode(): RoundBlockSizeMode;
    public function getForegroundColor(): ColorInterface;
    public function getBackgroundColor(): ColorInterface;
}
