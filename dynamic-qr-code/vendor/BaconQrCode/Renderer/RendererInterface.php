<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\BaconQrCode\Renderer;

use SOSIDEE_DYNAMIC_QRCODE\vendor\BaconQrCode\Encoder\QrCode;
interface RendererInterface
{
    public function render(QrCode $qrCode): string;
}
