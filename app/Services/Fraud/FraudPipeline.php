<?php

namespace App\Services\Fraud;

use App\Services\Fraud\Rules\FraudRuleInterface;

class FraudPipeline
{
    /** @param FraudRuleInterface[] $rules */
    public function __construct(private array $rules) {}

    public function run(FraudContext $context): void
    {
        foreach ($this->rules as $rule) {
            $rule->check($context);
        }
    }
}
