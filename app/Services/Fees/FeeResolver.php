<?php

namespace App\Services\Fees;

class FeeResolver
{
    public function resolve(float $amountTry): FeeCalculatorInterface
    {
        if ($amountTry <= 1000) {
            return new FixedFeeCalculator();
        }

        if ($amountTry <= 10000) {
            return new PercentageFeeCalculator(0.5);
        }

        return new TieredFeeCalculator();
    }
}
