<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Bacon\MatrixFactory;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\LabelInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Logo\LogoInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\QrCodeInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\BinaryResult;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\ResultInterface;
final readonly class BinaryWriter implements WriterInterface
{
    public function write(QrCodeInterface $qrCode, ?LogoInterface $logo = null, ?LabelInterface $label = null, array $options = []): ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);
        return new BinaryResult($matrix);
    }
}
