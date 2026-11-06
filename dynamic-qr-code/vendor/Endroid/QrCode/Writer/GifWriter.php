<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\LabelInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Logo\LogoInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\QrCodeInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\GdResult;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\GifResult;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\ResultInterface;
final readonly class GifWriter extends AbstractGdWriter
{
    public function write(QrCodeInterface $qrCode, ?LogoInterface $logo = null, ?LabelInterface $label = null, array $options = []): ResultInterface
    {
        /** @var GdResult $gdResult */
        $gdResult = parent::write($qrCode, $logo, $label, $options);
        return new GifResult($gdResult->getMatrix(), $gdResult->getImage());
    }
}
