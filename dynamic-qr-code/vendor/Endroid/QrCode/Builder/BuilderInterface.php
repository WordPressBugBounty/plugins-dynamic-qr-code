<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Builder;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Color\ColorInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Encoding\EncodingInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\ErrorCorrectionLevel;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\Font\FontInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\LabelAlignment;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\Margin\MarginInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\RoundBlockSizeMode;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\ResultInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\WriterInterface;
interface BuilderInterface
{
    /** @param array<mixed>|null $writerOptions */
    public function build(
        ?WriterInterface $writer = null,
        ?array $writerOptions = null,
        ?bool $validateResult = null,
        // QrCode options
        ?string $data = null,
        ?EncodingInterface $encoding = null,
        ?ErrorCorrectionLevel $errorCorrectionLevel = null,
        ?int $size = null,
        ?int $margin = null,
        ?RoundBlockSizeMode $roundBlockSizeMode = null,
        ?ColorInterface $foregroundColor = null,
        ?ColorInterface $backgroundColor = null,
        // Label options
        ?string $labelText = null,
        ?FontInterface $labelFont = null,
        ?LabelAlignment $labelAlignment = null,
        ?MarginInterface $labelMargin = null,
        ?ColorInterface $labelTextColor = null,
        // Logo options
        ?string $logoPath = null,
        ?int $logoResizeToWidth = null,
        ?int $logoResizeToHeight = null,
        ?bool $logoPunchoutBackground = null
    ): ResultInterface;
}
