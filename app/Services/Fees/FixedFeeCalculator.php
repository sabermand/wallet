<?php

namespace App\Services\Fees;

class FixedFeeCalculator implements FeeCalculatorInterface
{
    public function calculate(float $amountTry): float
    {
        return 2.0;
    }

    public function name(): string
    {
        return 'fixed';
    }
}
