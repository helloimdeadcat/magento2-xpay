<?php
declare(strict_types=1);

namespace Stagem\Xpay\Api;

interface ProcessPayInterface
{
    /**
     * @return array<string, mixed>
     */
    public function processPayGet(): array;
}
