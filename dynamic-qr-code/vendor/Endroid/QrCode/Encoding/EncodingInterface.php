<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Encoding;

interface EncodingInterface extends \Stringable
{
    public function __toString(): string;
}
