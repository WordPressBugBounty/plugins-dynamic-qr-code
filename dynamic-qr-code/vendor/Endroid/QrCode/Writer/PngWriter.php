<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\LabelInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Logo\LogoInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\QrCodeInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\GdResult;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\PngResult;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\ResultInterface;
final readonly class PngWriter extends AbstractGdWriter
{
    public const WRITER_OPTION_COMPRESSION_LEVEL = 'compression_level';
    public const WRITER_OPTION_NUMBER_OF_COLORS = 'number_of_colors';
    public function write(QrCodeInterface $qrCode, ?LogoInterface $logo = null, ?LabelInterface $label = null, array $options = []): ResultInterface
    {
        if (!isset($options[self::WRITER_OPTION_COMPRESSION_LEVEL])) {
            $options[self::WRITER_OPTION_COMPRESSION_LEVEL] = -1;
        }
        if (!array_key_exists(self::WRITER_OPTION_NUMBER_OF_COLORS, $options)) {
            // When a logo is present use true color, otherwise use a palette of 16 colors
            $options[self::WRITER_OPTION_NUMBER_OF_COLORS] = $logo instanceof LogoInterface ? null : 16;
        }
        /** @var GdResult $gdResult */
        $gdResult = parent::write($qrCode, $logo, $label, $options);
        return new PngResult($gdResult->getMatrix(), $gdResult->getImage(), $options[self::WRITER_OPTION_COMPRESSION_LEVEL], $options[self::WRITER_OPTION_NUMBER_OF_COLORS]);
    }
}
