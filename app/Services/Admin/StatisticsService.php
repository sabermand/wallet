<?php

namespace App\Services\Admin;

use App\Models\FraudFlag;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    private const CACHE_KEY = 'admin:statistics:v1';
    private const TTL_SECONDS = 300; // 5 min

    public function getCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn () => $this->compute());
    }

    public function refresh(): array
    {
        $data = $this->compute();
        Cache::put(self::CACHE_KEY, $data, self::TTL_SECONDS);
        return $data;
    }

    public function compute(): array
    {
        $now = now();
        $dayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        $base = Transaction::query()->whereIn('status', ['completed', 'pending_review', 'rejected', 'failed', 'pending']);

        $volumeDaily = (float) (clone $base)->where('created_at', '>=', $dayStart)->sum('amount');
        $volumeWeekly = (float) (clone $base)->where('created_at', '>=', $weekStart)->sum('amount');
        $volumeMonthly = (float) (clone $base)->where('created_at', '>=', $monthStart)->sum('amount');

        $countByType = (clone $base)
            ->select('type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('type')
            ->pluck('cnt', 'type')
            ->toArray();

        $countByStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $totalFees = (float) Transaction::query()->where('status', 'completed')->sum('fee_amount');
        $avgAmount = (float) Transaction::query()->where('status', 'completed')->avg('amount');

        $topUsers = Transaction::query()
            ->where('transactions.status', 'completed')
            ->whereNotNull('transactions.source_wallet_id')
            ->join('wallets as w', 'w.id', '=', 'transactions.source_wallet_id')
            ->select('w.user_id', DB::raw('SUM(transactions.amount) as total_volume'))
            ->groupBy('w.user_id')
            ->orderByDesc('total_volume')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['user_id' => (int) $r->user_id, 'total_volume' => (float) $r->total_volume])
            ->toArray();


        $currencyDistribution = Wallet::query()
            ->select('currency', DB::raw('COUNT(*) as cnt'))
            ->groupBy('currency')
            ->pluck('cnt', 'currency')
            ->toArray();

        $fraud7d = FraudFlag::query()->where('triggered_at', '>=', now()->subDays(7));
        $fraudFlaggedCount = (int) (clone $fraud7d)->count();

        $fraudByRule = (clone $fraud7d)
            ->select('rule_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('rule_type')
            ->pluck('cnt', 'rule_type')
            ->toArray();

        $pendingReviewCount = (int) Transaction::query()->where('status', 'pending_review')->count();

        return [
            'transaction_volume' => [
                'daily' => $volumeDaily,
                'weekly' => $volumeWeekly,
                'monthly' => $volumeMonthly,
            ],
            'transaction_count_by_type' => $countByType,
            'transaction_count_by_status' => $countByStatus,
            'total_fees_collected' => $totalFees,
            'average_transaction_amount' => $avgAmount,
            'top_10_users_by_volume' => $topUsers,
            'currency_distribution' => $currencyDistribution,
            'fraud' => [
                'flagged_transactions_last_7_days' => $fraudFlaggedCount,
                'triggers_by_rule' => $fraudByRule,
                'pending_review_count' => $pendingReviewCount,
            ],
            'cached_at' => now()->toIso8601String(),
        ];
    }
}
