<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Services\Fees\FeeResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        private readonly WalletRepositoryInterface $wallets,
        private readonly TransactionRepositoryInterface $transactions,
        private readonly FeeResolver $feeResolver,
    ) {}

    /**
     * Transfer funds between two wallets.
     *
     * Guarantees:
     * - Idempotency protection
     * - No negative balance
     * - Fee calculation based on defined rules
     * - Atomic execution using database transaction
     */
    public function transfer(
        string $sourceWalletId,
        string $destinationWalletId,
        float $amount,
        ?string $idempotencyKey,
        ?string $ipAddress = null
    ): Transaction {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than zero.',
            ]);
        }

        if ($amount > 10000) {
            throw ValidationException::withMessages([
                'amount' => 'Maximum allowed amount per transaction is 10,000 TRY.',
            ]);
        }

        // Idempotency check: prevent duplicate transfers
        if ($idempotencyKey && $this->transactions->existsByIdempotencyKey($idempotencyKey)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'This request has already been processed.',
            ]);
        }

        return DB::transaction(function () use (
            $sourceWalletId,
            $destinationWalletId,
            $amount,
            $idempotencyKey,
            $ipAddress
        ) {
            /** @var Wallet $source */
            $source = Wallet::query()
                ->where('id', $sourceWalletId)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Wallet $destination */
            $destination = Wallet::query()
                ->where('id', $destinationWalletId)
                ->lockForUpdate()
                ->firstOrFail();

            // Outgoing transactions are not allowed from blocked wallets
            if ($source->status === 'blocked') {
                throw ValidationException::withMessages([
                    'wallet' => 'Source wallet is blocked and cannot perform outgoing transactions.',
                ]);
            }

            // Currency mismatch is not allowed
            if ($source->currency !== $destination->currency) {
                throw ValidationException::withMessages([
                    'currency' => 'Source and destination wallets must have the same currency.',
                ]);
            }

            // Resolve and calculate transaction fee
            $calculator = $this->feeResolver->resolve($amount);
            $fee = $calculator->calculate($amount);

            // Ensure sufficient balance (amount + fee)
            $totalDebit = $amount + $fee;
            if ((float) $source->balance < $totalDebit) {
                throw ValidationException::withMessages([
                    'balance' => 'Insufficient balance to complete the transfer including fees.',
                ]);
            }

            // Create transaction record
            $transaction = $this->transactions->create([
                'type' => 'transfer',
                'status' => 'completed',
                'currency' => $source->currency,
                'amount' => $amount,
                'fee_amount' => $fee,
                'source_wallet_id' => $source->id,
                'destination_wallet_id' => $destination->id,
                'idempotency_key' => $idempotencyKey,
                'ip_address' => $ipAddress,
                'completed_at' => now(),
            ]);

            // Apply balance changes
            $source->balance = (float) $source->balance - $totalDebit;
            $destination->balance = (float) $destination->balance + $amount;

            $source->save();
            $destination->save();

            // Persist fee details for auditing and reporting
            $transaction->fee()->create([
                'fee_type' => $calculator->name(),
                'fee_amount' => $fee,
                'meta' => [
                    'amount' => $amount,
                    'total_debit' => $totalDebit,
                ],
            ]);

            return $transaction;
        });
    }
}
