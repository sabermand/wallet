<?php

namespace App\Services\Fraud\Rules;

use App\Models\Transaction;
use App\Services\Fraud\FraudContext;
use Illuminate\Validation\ValidationException;

class DailyLimitRule implements FraudRuleInterface
{
    private const DAILY_LIMIT = 50000;

    public function check(FraudContext $context): void
    {
        if ($context->currency !== 'TRY') {
            return;
        }

        $todayTotal = Transaction::query()
            ->where('source_wallet_id', $context->sourceWallet->id)
            ->where('type', 'transfer')
            ->whereDate('created_at', today())
            ->sum('amount');

        if ($todayTotal + $context->amount > self::DAILY_LIMIT) {
            throw ValidationException::withMessages([
                'daily_limit' => 'Daily transfer limit exceeded.',
            ]);
        }
    }
}
