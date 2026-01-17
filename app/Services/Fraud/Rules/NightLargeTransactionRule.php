<?php

namespace App\Services\Fraud\Rules;

use App\Services\Fraud\FraudContext;
use App\Services\Fraud\Exceptions\RequiresManualApprovalException;

class NightLargeTransactionRule implements FraudRuleInterface
{
    public function check(FraudContext $context): void
    {
        if ($context->currency !== 'TRY') {
            return;
        }

        if ($context->amount < 5000) {
            return;
        }

        $hour = (int) now()->format('H');
        if ($hour >= 2 && $hour < 6) {
            throw new RequiresManualApprovalException(
                ruleType: 'night_large_tx',
                details: ['hour' => $hour, 'amount' => $context->amount]
            );
        }
    }
}
