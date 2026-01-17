<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Fees\FeeResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminTransactionService
{
    public function __construct(private readonly FeeResolver $feeResolver) {}

    public function approve(string $transactionId, int $adminId, ?string $ipAddress = null): Transaction
    {
        return DB::transaction(function () use ($transactionId, $adminId, $ipAddress) {

            /** @var Transaction $tx */
            $tx = Transaction::query()->where('id', $transactionId)->lockForUpdate()->firstOrFail();

            if ($tx->status !== 'pending_review') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending_review transactions can be approved.',
                ]);
            }

            if ($tx->type !== 'transfer') {
                throw ValidationException::withMessages([
                    'type' => 'Only transfer transactions can be approved.',
                ]);
            }

            /** @var Wallet $source */
            $source = Wallet::query()->where('id', $tx->source_wallet_id)->lockForUpdate()->firstOrFail();

            /** @var Wallet $dest */
            $dest = Wallet::query()->where('id', $tx->destination_wallet_id)->lockForUpdate()->firstOrFail();

            if ($source->status === 'blocked') {
                throw ValidationException::withMessages([
                    'wallet' => 'Source wallet is blocked and cannot perform outgoing transactions.',
                ]);
            }

            if ($source->currency !== $dest->currency) {
                throw ValidationException::withMessages([
                    'currency' => 'Source and destination wallets must have the same currency.',
                ]);
            }

            // Calculate fee at approval time
            $calculator = $this->feeResolver->resolve((float) $tx->amount);
            $fee = $calculator->calculate((float) $tx->amount);

            $sourceBefore = (float) $source->balance;
            $destBefore = (float) $dest->balance;

            $totalDebit = (float) $tx->amount + $fee;

            if ($sourceBefore < $totalDebit) {
                throw ValidationException::withMessages([
                    'balance' => 'Insufficient balance to approve this transfer (including fee).',
                ]);
            }

            // Apply balances
            $source->balance = $sourceBefore - $totalDebit;
            $dest->balance = $destBefore + (float) $tx->amount;

            $source->save();
            $dest->save();

            // Mark transaction completed
            $tx->status = 'completed';
            $tx->fee_amount = $fee;
            $tx->completed_at = now();
            $tx->save();

            // Audit log
            AuditLog::query()->create([
                'transaction_id' => $tx->id,
                'admin_id' => $adminId,
                'action' => 'approve',
                'reason' => null,
                'source_wallet_id' => $source->id,
                'destination_wallet_id' => $dest->id,
                'amount' => (float) $tx->amount,
                'fee_amount' => $fee,
                'source_balance_before' => $sourceBefore,
                'source_balance_after' => (float) $source->balance,
                'dest_balance_before' => $destBefore,
                'dest_balance_after' => (float) $dest->balance,
                'ip_address' => $ipAddress,
            ]);

            return $tx;
        });
    }

    public function reject(string $transactionId, int $adminId, ?string $reason = null, ?string $ipAddress = null): Transaction
    {
        return DB::transaction(function () use ($transactionId, $adminId, $reason, $ipAddress) {

            /** @var Transaction $tx */
            $tx = Transaction::query()->where('id', $transactionId)->lockForUpdate()->firstOrFail();

            if ($tx->status !== 'pending_review') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending_review transactions can be rejected.',
                ]);
            }

            $tx->status = 'rejected';
            $tx->save();

            AuditLog::query()->create([
                'transaction_id' => $tx->id,
                'admin_id' => $adminId,
                'action' => 'reject',
                'reason' => $reason,
                'source_wallet_id' => $tx->source_wallet_id,
                'destination_wallet_id' => $tx->destination_wallet_id,
                'amount' => (float) $tx->amount,
                'fee_amount' => (float) $tx->fee_amount,
                'ip_address' => $ipAddress,
            ]);

            return $tx;
        });
    }
}
