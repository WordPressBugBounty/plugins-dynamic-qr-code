<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Matrix\MatrixInterface;
final class EpsResult extends AbstractResult
{
    public function __construct(
        MatrixInterface $matrix,
        /** @var array<string> $lines */
        private readonly array $lines
    )
    {
        parent::__construct($matrix);
    }
    public function getString(): string
    {
        return implode("\n", $this->lines);
    }
    public function getMimeType(): string
    {
        return 'image/eps';
    }
}
