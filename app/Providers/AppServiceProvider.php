<?php

namespace App\Providers;

use App\Models\Batch;
use App\Models\DailyCollection;
use App\Models\Delivery;
use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\RestockRequest;
use App\Models\Sale;
use App\Models\WastageLog;
use App\Policies\BatchPolicy;
use App\Policies\DailyCollectionPolicy;
use App\Policies\DeliveryPolicy;
use App\Policies\EggCategoryPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RestockRequestPolicy;
use App\Policies\SalePolicy;
use App\Policies\WastageLogPolicy;
use Illuminate\Support\Facades\Gate;
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
        EggCategory::class => EggCategoryPolicy::class,
        Inventory::class => InventoryPolicy::class,
        Reservation::class => ReservationPolicy::class,
        RestockRequest::class => RestockRequestPolicy::class,
        Sale::class => SalePolicy::class,
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
    }
}
