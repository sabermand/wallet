<?php

namespace App\Services\Fees;

interface FeeCalculatorInterface
{
    public function calculate(float $amountTry): float;
    public function name(): string;
}
