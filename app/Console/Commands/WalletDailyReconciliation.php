<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use Illuminate\Console\Command;

class WalletDailyReconciliation extends Command
{
    protected $signature = 'wallet:daily-reconciliation';
    protected $description = 'Daily reconciliation report (basic)';

    public function handle(): int
    {
        $totalWallets = Wallet::query()->count();
        $totalBalance = (float) Wallet::query()->sum('balance');

        $this->info("total_wallets: {$totalWallets}");
        $this->info("total_balance_sum: {$totalBalance}");

        return self::SUCCESS;
    }
}
