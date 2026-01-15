<?php

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(array $data): Transaction
    {
        return Transaction::query()->create($data);
    }

    public function findById(string $id): ?Transaction
    {
        return Transaction::query()->find($id);
    }

    public function existsByIdempotencyKey(string $key): bool
    {
        return Transaction::query()
            ->where('idempotency_key', $key)
            ->exists();
    }
}
