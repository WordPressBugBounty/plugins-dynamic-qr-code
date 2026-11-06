<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Writer\Result;

use SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Matrix\MatrixInterface;
final class PdfResult extends AbstractResult
{
    public function __construct(MatrixInterface $matrix, private readonly \SOSIDEE_DYNAMIC_QRCODE\vendor\FPDF $fpdf)
    {
        parent::__construct($matrix);
    }
    public function getPdf(): \SOSIDEE_DYNAMIC_QRCODE\vendor\FPDF
    {
        return $this->fpdf;
    }
    public function getString(): string
    {
        return $this->fpdf->Output('S');
    }
    public function getMimeType(): string
    {
        return 'application/pdf';
    }
}
