<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Color\ColorInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\Font\FontInterface;
use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Label\Margin\MarginInterface;
interface LabelInterface
{
    public function getText(): string;
    public function getFont(): FontInterface;
    public function getAlignment(): LabelAlignment;
    public function getMargin(): MarginInterface;
    public function getTextColor(): ColorInterface;
}
