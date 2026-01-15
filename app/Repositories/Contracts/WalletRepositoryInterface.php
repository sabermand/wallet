<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;

interface WalletRepositoryInterface
{
    public function findById(string $id): ?Wallet;

    public function findUserWalletByCurrency(int $userId, string $currency): ?Wallet;

    public function create(array $data): Wallet;

    public function save(Wallet $wallet): bool;
}
