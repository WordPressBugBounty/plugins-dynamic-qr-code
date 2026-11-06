<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\LabelInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Logo\LogoInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\QrCodeInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\GdResult;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\ResultInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\WebPResult;
final readonly class WebPWriter extends AbstractGdWriter
{
    public const WRITER_OPTION_QUALITY = 'quality';
    public function write(QrCodeInterface $qrCode, ?LogoInterface $logo = null, ?LabelInterface $label = null, array $options = []): ResultInterface
    {
        if (!isset($options[self::WRITER_OPTION_QUALITY])) {
            $options[self::WRITER_OPTION_QUALITY] = -1;
        }
        /** @var GdResult $gdResult */
        $gdResult = parent::write($qrCode, $logo, $label, $options);
        return new WebPResult($gdResult->getMatrix(), $gdResult->getImage(), $options[self::WRITER_OPTION_QUALITY]);
    }
}
