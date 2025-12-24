<?php

namespace App\Providers;

use App\Models\Batch;
use App\Models\DailyCollection;
use App\Models\Delivery;
use App\Models\DeliveryDiscrepancy;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\RestockRequest;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use App\Models\WastageLog;
use App\Policies\BatchPolicy;
use App\Policies\DailyCollectionPolicy;
use App\Policies\DeliveryDiscrepancyPolicy;
use App\Policies\DeliveryPolicy;
use App\Policies\EggCategoryPolicy;
use App\Policies\FarmPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RestockRequestPolicy;
use App\Policies\SalePolicy;
use App\Policies\ShopPolicy;
use App\Policies\UserPolicy;
use App\Policies\WastageLogPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Batch::class => BatchPolicy::class,
        DailyCollection::class => DailyCollectionPolicy::class,
        Delivery::class => DeliveryPolicy::class,
        DeliveryDiscrepancy::class => DeliveryDiscrepancyPolicy::class,
        EggCategory::class => EggCategoryPolicy::class,
        Farm::class => FarmPolicy::class,
        Inventory::class => InventoryPolicy::class,
        Reservation::class => ReservationPolicy::class,
        RestockRequest::class => RestockRequestPolicy::class,
        Sale::class => SalePolicy::class,
        Shop::class => ShopPolicy::class,
        User::class => UserPolicy::class,
        WastageLog::class => WastageLogPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Implicitly grant "manager" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('manager') ? true : null;
        });

        // Configure rate limiters
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limit: 60 requests per minute for authenticated users
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Strict limit for authentication endpoints to prevent brute force
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Password reset: very strict
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Bulk operations: limited to prevent abuse
        RateLimiter::for('bulk', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Sales: moderate limit (higher for POS operations)
        RateLimiter::for('sales', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
