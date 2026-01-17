<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Services\Fees\FeeResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Fraud\FraudContext;
use App\Services\Fraud\FraudPipeline;

class TransactionService
{
    public function __construct(
        private readonly WalletRepositoryInterface $wallets,
        private readonly TransactionRepositoryInterface $transactions,
        private readonly FeeResolver $feeResolver,
        private readonly FraudPipeline $fraudPipeline,
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

            // Create fraud context
            $context = new FraudContext(
                sourceWallet: $source,
                destinationWallet: $destination,
                amount: $amount,
                currency: $source->currency,
                ipAddress: $ipAddress
            );

            // Run fraud detection rules
            try {
                $this->fraudPipeline->run($context);
            } catch (RequiresManualApprovalException $e) {
                $tx = $this->transactions->create([
                    'type' => 'transfer',
                    'status' => 'pending_review',
                    'currency' => $source->currency,
                    'amount' => $amount,
                    'fee_amount' => 0,
                    'source_wallet_id' => $source->id,
                    'destination_wallet_id' => $destination->id,
                    'idempotency_key' => $idempotencyKey,
                    'ip_address' => $ipAddress,
                    'completed_at' => null,
                ]);

                FraudFlag::query()->create([
                    'transaction_id' => $tx->id,
                    'user_id' => $source->user_id,
                    'rule_type' => $e->ruleType,
                    'flagged_amount' => $amount,
                    'details' => $e->details,
                    'triggered_at' => now(),
                ]);

                return $tx;
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

    public function getById(string $id): Transaction
    {
        return \App\Models\Transaction::query()->with('fee')->findOrFail($id);
    }

    public function deposit(string $walletId, float $amount, ?string $ipAddress = null): Transaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($walletId, $amount, $ipAddress) {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->where('id', $walletId)->lockForUpdate()->firstOrFail();

            // Incoming transactions are allowed even if blocked (per spec for block/unblock later)
            $tx = $this->transactions->create([
                'type' => 'deposit',
                'status' => 'completed',
                'currency' => $wallet->currency,
                'amount' => $amount,
                'fee_amount' => 0,
                'destination_wallet_id' => $wallet->id,
                'ip_address' => $ipAddress,
                'completed_at' => now(),
            ]);

            $wallet->balance = (float) $wallet->balance + $amount;
            $wallet->save();

            return $tx;
        });
    }

    public function withdraw(string $walletId, float $amount, ?string $ipAddress = null): Transaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($walletId, $amount, $ipAddress) {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->where('id', $walletId)->lockForUpdate()->firstOrFail();

            if ($wallet->status === 'blocked') {
                throw ValidationException::withMessages([
                    'wallet' => 'Wallet is blocked and cannot perform outgoing transactions.',
                ]);
            }

            if ((float) $wallet->balance < $amount) {
                throw ValidationException::withMessages([
                    'balance' => 'Insufficient balance to complete the withdrawal.',
                ]);
            }

            $tx = $this->transactions->create([
                'type' => 'withdrawal',
                'status' => 'completed',
                'currency' => $wallet->currency,
                'amount' => $amount,
                'fee_amount' => 0,
                'source_wallet_id' => $wallet->id,
                'ip_address' => $ipAddress,
                'completed_at' => now(),
            ]);

            $wallet->balance = (float) $wallet->balance - $amount;
            $wallet->save();

            return $tx;
        });
    }

    public function refund(string $transactionId, ?string $reason = null): Transaction
    {
        return DB::transaction(function () use ($transactionId, $reason) {
            /** @var Transaction $original */
            $original = Transaction::query()->where('id', $transactionId)->lockForUpdate()->firstOrFail();

            if ($original->type !== 'transfer') {
                throw ValidationException::withMessages([
                    'transaction' => 'Only transfer transactions can be refunded.',
                ]);
            }

            // Prevent double refunds
            $alreadyRefunded = Transaction::query()
                ->where('type', 'refund')
                ->where('refunded_transaction_id', $original->id)
                ->exists();

            if ($alreadyRefunded) {
                throw ValidationException::withMessages([
                    'refund' => 'This transaction has already been refunded.',
                ]);
            }

            // Load wallets and lock them
            $source = Wallet::query()->with('user')->where('id', $original->source_wallet_id)->lockForUpdate()->firstOrFail();
            $dest = Wallet::query()->where('id', $original->destination_wallet_id)->lockForUpdate()->firstOrFail();

            // Refund amount back from destination to source (fee is not refunded here; can be adjusted later)
            if ((float) $dest->balance < (float) $original->amount) {
                throw ValidationException::withMessages([
                    'balance' => 'Destination wallet has insufficient balance to process the refund.',
                ]);
            }

            $refundTx = $this->transactions->create([
                'type' => 'refund',
                'status' => 'completed',
                'currency' => $original->currency,
                'amount' => (float) $original->amount,
                'fee_amount' => 0,
                'source_wallet_id' => $dest->id,
                'destination_wallet_id' => $source->id,
                'refunded_transaction_id' => $original->id,
                'completed_at' => now(),
                'ip_address' => null,
            ]);

            $dest->balance = (float) $dest->balance - (float) $original->amount;
            $source->balance = (float) $source->balance + (float) $original->amount;

            $dest->save();
            $source->save();

            // Optional: store reason somewhere later (audit_logs table). For now keep it out.

            return $refundTx;
        });
    }
}
