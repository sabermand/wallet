<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function transfer(TransferRequest $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        $ipAddress = $request->ip();

        $tx = $this->transactionService->transfer(
            sourceWalletId: $request->validated('source_wallet_id'),
            destinationWalletId: $request->validated('destination_wallet_id'),
            amount: (float) $request->validated('amount'),
            idempotencyKey: $idempotencyKey,
            ipAddress: $ipAddress
        );

        return response()->json([
            'data' => [
                'id' => $tx->id,
                'type' => $tx->type,
                'status' => $tx->status,
                'currency' => $tx->currency,
                'amount' => (float) $tx->amount,
                'fee_amount' => (float) $tx->fee_amount,
                'source_wallet_id' => $tx->source_wallet_id,
                'destination_wallet_id' => $tx->destination_wallet_id,
                'completed_at' => $tx->completed_at,
            ],
        ], 201);
    }
}
