<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Matrix;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\QrCodeInterface;
interface MatrixFactoryInterface
{
    public function create(QrCodeInterface $qrCode): MatrixInterface;
}
