<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\CreateWalletRequest;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function index(Request $request)
    {
        $wallets = $request->user()->wallets()->latest()->get();

        return response()->json([
            'data' => $wallets->map(fn (Wallet $w) => [
                'id' => $w->id,
                'currency' => $w->currency,
                'balance' => (float) $w->balance,
                'status' => $w->status,
                'blocked_at' => $w->blocked_at,
            ]),
        ]);
    }

    public function store(CreateWalletRequest $request)
    {
        $wallet = $this->walletService->createWallet(
            userId: $request->user()->id,
            currency: $request->validated('currency'),
        );

        return response()->json([
            'data' => [
                'id' => $wallet->id,
                'currency' => $wallet->currency,
                'balance' => (float) $wallet->balance,
                'status' => $wallet->status,
            ],
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $wallet = $request->user()->wallets()->where('id', $id)->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $wallet->id,
                'currency' => $wallet->currency,
                'balance' => (float) $wallet->balance,
                'status' => $wallet->status,
                'blocked_at' => $wallet->blocked_at,
                'block_reason' => $wallet->block_reason,
            ],
        ]);
    }

    public function balance(Request $request, string $id)
    {
        $wallet = $request->user()->wallets()->where('id', $id)->firstOrFail();

        return response()->json([
            'data' => [
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'balance' => (float) $wallet->balance,
            ],
        ]);
    }

    public function transactions(Request $request, string $id)
    {
        $wallet = $request->user()->wallets()->where('id', $id)->firstOrFail();

        $txs = \App\Models\Transaction::query()
            ->where('source_wallet_id', $wallet->id)
            ->orWhere('destination_wallet_id', $wallet->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $txs->items(),
            'meta' => [
                'current_page' => $txs->currentPage(),
                'per_page' => $txs->perPage(),
                'total' => $txs->total(),
                'last_page' => $txs->lastPage(),
            ],
        ]);
    }
}
