<?php

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
{
    public function findById(string $id): ?Wallet
    {
        return Wallet::query()->find($id);
    }

    public function findUserWalletByCurrency(int $userId, string $currency): ?Wallet
    {
        return Wallet::query()
            ->where('user_id', $userId)
            ->where('currency', $currency)
            ->first();
    }

    public function create(array $data): Wallet
    {
        return Wallet::query()->create($data);
    }

    public function save(Wallet $wallet): bool
    {
        return $wallet->save();
    }
}
