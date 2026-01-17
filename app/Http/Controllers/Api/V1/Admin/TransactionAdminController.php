<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectTransactionRequest;
use App\Models\Transaction;
use App\Services\Admin\AdminTransactionService;
use Illuminate\Http\Request;

class TransactionAdminController extends Controller
{
    public function __construct(private readonly AdminTransactionService $adminTxs) {}

    public function pendingReview()
    {
        $txs = Transaction::query()
            ->where('status', 'pending_review')
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

    public function approve(string $id, Request $request)
    {
        $tx = $this->adminTxs->approve(
            transactionId: $id,
            adminId: $request->user()->id,
            ipAddress: $request->ip()
        );

        return response()->json(['data' => $tx]);
    }

    public function reject(string $id, RejectTransactionRequest $request)
    {
        $tx = $this->adminTxs->reject(
            transactionId: $id,
            adminId: $request->user()->id,
            reason: $request->validated('reason'),
            ipAddress: $request->ip()
        );

        return response()->json(['data' => $tx]);
    }
}
