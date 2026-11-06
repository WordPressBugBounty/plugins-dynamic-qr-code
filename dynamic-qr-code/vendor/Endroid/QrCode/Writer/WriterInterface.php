<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\LabelInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Logo\LogoInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\QrCodeInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\ResultInterface;
interface WriterInterface
{
    /** @param array<string, mixed> $options */
    public function write(QrCodeInterface $qrCode, ?LogoInterface $logo = null, ?LabelInterface $label = null, array $options = []): ResultInterface;
}
