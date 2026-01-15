<?php

namespace App\Services;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $wallets
    ) {}

    public function createWallet(int $userId, string $currency): Wallet
    {
        // Check if the wallet already exists
        $existing = $this->wallets->findUserWalletByCurrency($userId, $currency);
        if ($existing) {
            return $existing;
        }

        return $this->wallets->create([
            'user_id' => $userId,
            'currency' => $currency,
            'balance' => 0,
            'status' => 'active',
        ]);
    }
}
