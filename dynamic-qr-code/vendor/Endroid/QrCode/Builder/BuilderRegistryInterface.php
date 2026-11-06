<?php

declare (strict_types=1);
namespace SOSIDEE_DYNAMIC_QRCODE\vendor\Endroid\QrCode\Builder;

interface BuilderRegistryInterface
{
    public function set(string $name, BuilderInterface $builder): void;
    public function get(string $name): BuilderInterface;
}
