<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Eloquent\WalletRepository;
use App\Repositories\Eloquent\TransactionRepository;
use App\Services\Fraud\FraudPipeline;
use App\Services\Fraud\Rules\DailyLimitRule;
use App\Services\Fraud\Rules\HourlyRecipientLimitRule;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */    
    public function register(): void
    {
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);

        $this->app->bind(FraudPipeline::class, function () {
            return new FraudPipeline([
                new DailyLimitRule(),
                new HourlyRecipientLimitRule(),
            ]);
        });
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('transfer', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip());
        });
    }

    
}
