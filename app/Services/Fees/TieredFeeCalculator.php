<?php

namespace App\Services\Fees;

class TieredFeeCalculator implements FeeCalculatorInterface
{
    public function calculate(float $amountTry): float
    {
        // 2 TRY for first 1000 + 0.3% on remainder
        $first = 2.0;
        $remainder = max(0, $amountTry - 1000);
        $extra = round($remainder * 0.003, 2);
        return $first + $extra;
    }

    public function name(): string
    {
        return 'tiered';
    }
}
