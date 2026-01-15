<?php

namespace App\Services\Fees;

class PercentageFeeCalculator implements FeeCalculatorInterface
{
    public function __construct(private readonly float $percent) {}

    public function calculate(float $amountTry): float
    {
        return round($amountTry * ($this->percent / 100), 2);
    }

    public function name(): string
    {
        return 'percentage';
    }
}
