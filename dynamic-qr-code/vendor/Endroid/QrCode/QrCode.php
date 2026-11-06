<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Color\Color;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Color\ColorInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Encoding\Encoding;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Encoding\EncodingInterface;
final readonly class QrCode implements QrCodeInterface
{
    public function __construct(private string $data, private EncodingInterface $encoding = new Encoding('UTF-8'), private ErrorCorrectionLevel $errorCorrectionLevel = ErrorCorrectionLevel::Low, private int $size = 300, private int $margin = 10, private RoundBlockSizeMode $roundBlockSizeMode = RoundBlockSizeMode::Margin, private ColorInterface $foregroundColor = new Color(0, 0, 0), private ColorInterface $backgroundColor = new Color(255, 255, 255))
    {
    }
    public function getData(): string
    {
        return $this->data;
    }
    public function getEncoding(): EncodingInterface
    {
        return $this->encoding;
    }
    public function getErrorCorrectionLevel(): ErrorCorrectionLevel
    {
        return $this->errorCorrectionLevel;
    }
    public function getSize(): int
    {
        return $this->size;
    }
    public function getMargin(): int
    {
        return $this->margin;
    }
    public function getRoundBlockSizeMode(): RoundBlockSizeMode
    {
        return $this->roundBlockSizeMode;
    }
    public function getForegroundColor(): ColorInterface
    {
        return $this->foregroundColor;
    }
    public function getBackgroundColor(): ColorInterface
    {
        return $this->backgroundColor;
    }
}
