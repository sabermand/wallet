<?php

namespace App\Console\Commands;

use App\Services\Admin\StatisticsService;
use Illuminate\Console\Command;

class StatsRefreshCache extends Command
{
    protected $signature = 'stats:refresh-cache';
    protected $description = 'Refresh admin statistics cache';

    public function handle(StatisticsService $stats): int
    {
        $stats->refresh();
        $this->info('Statistics cache refreshed.');
        return self::SUCCESS;
    }
}
