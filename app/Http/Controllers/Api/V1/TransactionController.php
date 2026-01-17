<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\Transactions\DepositRequest;
use App\Http\Requests\Transactions\WithdrawRequest;
use App\Http\Requests\Transactions\RefundRequest;
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

    public function deposit(DepositRequest $request)
    {
        $tx = $this->transactionService->deposit(
            walletId: $request->validated('wallet_id'),
            amount: (float) $request->validated('amount'),
            ipAddress: $request->ip()
        );

        return response()->json(['data' => $tx], 201);
    }

    public function withdraw(WithdrawRequest $request)
    {
        $tx = $this->transactionService->withdraw(
            walletId: $request->validated('wallet_id'),
            amount: (float) $request->validated('amount'),
            ipAddress: $request->ip()
        );

        return response()->json(['data' => $tx], 201);
    }

    public function show(string $id)
    {
        $tx = $this->transactionService->getById($id);

        return response()->json(['data' => $tx]);
    }

    public function refund(RefundRequest $request, string $id)
    {
        $tx = $this->transactionService->refund(
            transactionId: $id,
            reason: $request->validated('reason')
        );

        return response()->json(['data' => $tx], 201);
    }
}
