<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Matrix\MatrixInterface;
class GdResult extends AbstractResult
{
    public function __construct(MatrixInterface $matrix, protected readonly \GdImage $image)
    {
        parent::__construct($matrix);
    }
    public function getImage(): \GdImage
    {
        return $this->image;
    }
    public function getString(): string
    {
        throw new \Exception('You can only use this method in a concrete implementation');
    }
    public function getMimeType(): string
    {
        throw new \Exception('You can only use this method in a concrete implementation');
    }
}
