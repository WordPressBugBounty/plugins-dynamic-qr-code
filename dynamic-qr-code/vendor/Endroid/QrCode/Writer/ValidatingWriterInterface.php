<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result\ResultInterface;
interface ValidatingWriterInterface
{
    public function validateResult(ResultInterface $result, string $expectedData): void;
}
