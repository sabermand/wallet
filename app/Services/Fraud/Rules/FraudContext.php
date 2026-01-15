<?php

namespace App\Services\Fraud;

use App\Models\Wallet;

class FraudContext
{
    public function __construct(
        public readonly Wallet $sourceWallet,
        public readonly Wallet $destinationWallet,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?string $ipAddress,
    ) {}
}
