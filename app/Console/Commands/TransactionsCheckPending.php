<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

class TransactionsCheckPending extends Command
{
    protected $signature = 'transactions:check-pending';
    protected $description = 'Check pending_review transactions older than 24 hours';

    public function handle(): int
    {
        $count = Transaction::query()
            ->where('status', 'pending_review')
            ->where('created_at', '<=', now()->subHours(24))
            ->count();

        $this->info("pending_review older than 24h: {$count}");
        return self::SUCCESS;
    }
}
