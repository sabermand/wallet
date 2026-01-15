<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;

    public function findById(string $id): ?Transaction;

    public function existsByIdempotencyKey(string $key): bool;
}
