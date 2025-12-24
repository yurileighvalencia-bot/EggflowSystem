<?php

namespace App\Providers;

use App\Events\BatchExpired;
use App\Events\DeliveryDiscrepancyReported;
use App\Events\DeliveryDispatched;
use App\Events\DeliveryReceived;
use App\Events\DiscrepancyInvestigated;
use App\Events\LowStockDetected;
use App\Events\ReservationCancelled;
use App\Events\ReservationCreated;
use App\Events\ReservationExpired;
use App\Events\RestockRequestAcknowledged;
use App\Events\RestockRequestCreated;
use App\Events\SaleCompleted;
use App\Listeners\CheckStockAfterSale;
use App\Listeners\HandleLowStockAlert;
use App\Listeners\LogExpiredBatchWastage;
use App\Listeners\NotifyCustomerOfCancelledReservation;
use App\Listeners\NotifyCustomerOfExpiredReservation;
use App\Listeners\NotifyFarmOfDeliveryReceipt;
use App\Listeners\NotifyFarmStaffOfRestockRequest;
use App\Listeners\NotifyManagersOfDiscrepancy;
use App\Listeners\NotifyReporterOfResolution;
use App\Listeners\NotifyShopOfAcknowledgement;
use App\Listeners\NotifyShopOfDeliveryDispatch;
use App\Listeners\SendReservationConfirmation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Batch Events
        BatchExpired::class => [
            LogExpiredBatchWastage::class,
        ],

        // Restock Request Events
        RestockRequestCreated::class => [
            NotifyFarmStaffOfRestockRequest::class,
        ],
        RestockRequestAcknowledged::class => [
            NotifyShopOfAcknowledgement::class,
        ],

        // Delivery Events
        DeliveryDispatched::class => [
            NotifyShopOfDeliveryDispatch::class,
        ],
        DeliveryReceived::class => [
            NotifyFarmOfDeliveryReceipt::class,
        ],
        DeliveryDiscrepancyReported::class => [
            NotifyManagersOfDiscrepancy::class,
        ],
        DiscrepancyInvestigated::class => [
            NotifyReporterOfResolution::class,
        ],

        // Reservation Events
        ReservationCreated::class => [
            SendReservationConfirmation::class,
        ],
        ReservationCancelled::class => [
            NotifyCustomerOfCancelledReservation::class,
        ],
        ReservationExpired::class => [
            NotifyCustomerOfExpiredReservation::class,
        ],

        // Sale Events
        SaleCompleted::class => [
            CheckStockAfterSale::class,
        ],

        // Inventory Events
        LowStockDetected::class => [
            HandleLowStockAlert::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
