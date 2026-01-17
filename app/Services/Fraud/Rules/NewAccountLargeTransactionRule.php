<?php

namespace App\Services\Fraud\Rules;

use App\Services\Fraud\FraudContext;
use App\Services\Fraud\Exceptions\RequiresManualApprovalException;

class NewAccountLargeTransactionRule implements FraudRuleInterface
{
    public function check(FraudContext $context): void
    {
        if ($context->currency !== 'TRY') {
            return;
        }

        if ($context->amount < 10000) {
            return;
        }

        $createdAt = $context->sourceWallet->user->created_at ?? null;
        if (!$createdAt) {
            return;
        }

        if ($createdAt->gt(now()->subDays(7))) {
            throw new RequiresManualApprovalException(
                ruleType: 'new_account_large_tx',
                details: ['user_created_at' => $createdAt->toDateTimeString(), 'amount' => $context->amount]
            );
        }
    }
}
