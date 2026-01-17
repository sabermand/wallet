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
use Illuminate\Support\Facades\Gate;
use App\Services\Fraud\Rules\NightLargeTransactionRule;
use App\Services\Fraud\Rules\NewAccountLargeTransactionRule;
use App\Services\Fraud\Exceptions\RequiresManualApprovalException;
use App\Models\FraudFlag;
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
                new NightLargeTransactionRule(),
                new NewAccountLargeTransactionRule(),
            ]);
        });
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', function ($user) {
            return $user->role === 'admin';
        });
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('transfer', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }    
}