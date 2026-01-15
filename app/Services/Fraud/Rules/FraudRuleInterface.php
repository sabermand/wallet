<?php

namespace App\Services\Fraud\Rules;

use App\Services\Fraud\FraudContext;

interface FraudRuleInterface
{
    public function check(FraudContext $context): void;
}
