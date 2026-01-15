<?php

namespace App\Services\Fraud\Rules;

use App\Models\Transaction;
use App\Services\Fraud\FraudContext;
use Illuminate\Validation\ValidationException;

class HourlyRecipientLimitRule implements FraudRuleInterface
{
    public function check(FraudContext $context): void
    {
        $count = Transaction::query()
            ->where('source_wallet_id', $context->sourceWallet->id)
            ->where('destination_wallet_id', $context->destinationWallet->id)
            ->where('type', 'transfer')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($count >= 3) {
            throw ValidationException::withMessages([
                'hourly_limit' => 'Too many transfers to the same recipient within one hour.',
            ]);
        }
    }
}
