<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\BulkOperationsController;
use App\Http\Controllers\Api\DailyCollectionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\DeliveryDiscrepancyController;
use App\Http\Controllers\Api\EggCategoryController;
use App\Http\Controllers\Api\FarmController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RestockRequestController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WastageLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API Version 1
Route::prefix('v1')->name('api.')->group(function () {
    // Public routes with auth rate limiting
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/register', [AuthController::class, 'register'])->name('register');
    });

    // Password Reset (public, strict rate limiting)
    Route::middleware('throttle:password-reset')->group(function () {
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    });

    // Email Verification (signed URL - no auth required for clicking link)
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    // Protected routes with general API rate limiting
    Route::middleware(['auth:sanctum', 'verified', 'throttle:api'])->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user'])->name('user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout.all');

    // Email Verification (resend - requires auth)
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Egg Categories
    Route::apiResource('egg-categories', EggCategoryController::class)->parameters([
        'egg-categories' => 'category'
    ])->except(['destroy']);
    Route::post('/egg-categories/{category}/toggle-active', [EggCategoryController::class, 'toggleActive'])
        ->name('egg-categories.toggle-active');

    // Farms
    Route::apiResource('farms', FarmController::class)->except(['destroy']);

    // Shops
    Route::apiResource('shops', ShopController::class)->except(['destroy']);

    // Users
    Route::apiResource('users', UserController::class)->except(['destroy']);
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->name('users.toggle-active');
    Route::post('/users/{user}/password', [UserController::class, 'updatePassword'])
        ->name('users.password');
    Route::post('/users/{user}/role', [UserController::class, 'assignRole'])
        ->name('users.role');

    // Batches
    Route::get('/batches/expiring-soon', [BatchController::class, 'expiringSoon'])
        ->name('batches.expiring-soon');
    Route::apiResource('batches', BatchController::class)->except(['destroy']);
    Route::post('/batches/{batch}/expire', [BatchController::class, 'expire'])
        ->name('batches.expire');

    // ==================== BULK OPERATIONS ====================
    Route::prefix('bulk')->name('bulk.')->middleware('throttle:bulk')->group(function () {
        Route::post('/batches/expire', [BulkOperationsController::class, 'expireBatches'])
            ->name('batches.expire');
        Route::post('/delete', [BulkOperationsController::class, 'bulkDelete'])
            ->name('delete');
        Route::post('/inventory/adjust', [BulkOperationsController::class, 'bulkAdjustInventory'])
            ->name('inventory.adjust');
        Route::post('/reservations/status', [BulkOperationsController::class, 'bulkUpdateReservationStatus'])
            ->name('reservations.status');
    });

    // Daily Collections
    Route::get('/collections/summary', [DailyCollectionController::class, 'summary'])
        ->name('collections.summary');
    Route::apiResource('collections', DailyCollectionController::class)
        ->parameters(['collections' => 'collection'])
        ->except(['destroy']);
    Route::post('/collections/{collection}/verify', [DailyCollectionController::class, 'verify'])
        ->name('collections.verify');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/summary', [InventoryController::class, 'summary'])->name('inventory.summary');
    Route::get('/inventory/expiring', [InventoryController::class, 'expiring'])->name('inventory.expiring');
    Route::get('/inventory/low-stock', [InventoryController::class, 'lowStockAlerts'])->name('inventory.low-stock');
    Route::post('/inventory/{inventory}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Restock Requests
    Route::get('/restock-requests/pending-count', [RestockRequestController::class, 'pendingCount'])
        ->name('restock-requests.pending-count');
    Route::apiResource('restock-requests', RestockRequestController::class)
        ->parameters(['restock-requests' => 'restockRequest'])
        ->except(['update', 'destroy']);
    Route::post('/restock-requests/{restockRequest}/acknowledge', [RestockRequestController::class, 'acknowledge'])
        ->name('restock-requests.acknowledge');
    Route::post('/restock-requests/{restockRequest}/cancel', [RestockRequestController::class, 'cancel'])
        ->name('restock-requests.cancel');

    // Deliveries
    Route::get('/deliveries/in-transit', [DeliveryController::class, 'inTransit'])
        ->name('deliveries.in-transit');
    Route::apiResource('deliveries', DeliveryController::class)->except(['update', 'destroy']);
    Route::post('/deliveries/{delivery}/receive', [DeliveryController::class, 'receive'])
        ->name('deliveries.receive');

    // Delivery Discrepancies
    Route::get('/discrepancies/pending-count', [DeliveryDiscrepancyController::class, 'pendingCount'])
        ->name('discrepancies.pending-count');
    Route::apiResource('discrepancies', DeliveryDiscrepancyController::class)
        ->parameters(['discrepancies' => 'discrepancy'])
        ->only(['index', 'show', 'store']);
    Route::post('/discrepancies/{discrepancy}/investigate', [DeliveryDiscrepancyController::class, 'investigate'])
        ->name('discrepancies.investigate');

    // Wastage Logs
    Route::get('/wastage/sources', [WastageLogController::class, 'sources'])
        ->name('wastage.sources');
    Route::get('/wastage/summary', [WastageLogController::class, 'summary'])
        ->name('wastage.summary');
    Route::apiResource('wastage', WastageLogController::class)
        ->parameters(['wastage' => 'wastageLog'])
        ->only(['index', 'show', 'store']);

    // Reservations
    Route::get('/reservations/today-pickups', [ReservationController::class, 'todayPickups'])
        ->name('reservations.today-pickups');
    Route::post('/reservations/expire-old', [ReservationController::class, 'expireOld'])
        ->name('reservations.expire-old');
    Route::apiResource('reservations', ReservationController::class)->except(['destroy']);
    Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])
        ->name('reservations.confirm');
    Route::post('/reservations/{reservation}/ready', [ReservationController::class, 'markReady'])
        ->name('reservations.ready');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
        ->name('reservations.cancel');

    // Sales (higher rate limit for POS operations)
    Route::middleware('throttle:sales')->group(function () {
        Route::get('/sales/today-summary', [SaleController::class, 'todaySummary'])
            ->name('sales.today-summary');
        Route::apiResource('sales', SaleController::class)->only(['index', 'show', 'store']);
        Route::post('/sales/{sale}/void', [SaleController::class, 'void'])
            ->name('sales.void');
        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])
            ->name('sales.receipt');
    });

    // ==================== REPORTS ====================
    Route::prefix('reports')->name('reports.')->group(function () {
        // Sales Reports
        Route::get('/sales/daily', [ReportController::class, 'dailySales'])->name('sales.daily');
        Route::get('/sales/weekly', [ReportController::class, 'weeklySales'])->name('sales.weekly');
        Route::get('/sales/monthly', [ReportController::class, 'monthlySales'])->name('sales.monthly');
        Route::get('/sales/date-range', [ReportController::class, 'salesDateRange'])->name('sales.date-range');
        Route::get('/sales/top-categories', [ReportController::class, 'topCategories'])->name('sales.top-categories');

        // Inventory Reports
        Route::get('/inventory/snapshot', [ReportController::class, 'inventorySnapshot'])->name('inventory.snapshot');
        Route::get('/inventory/low-stock', [ReportController::class, 'lowStock'])->name('inventory.low-stock');
        Route::get('/inventory/expiring', [ReportController::class, 'expiringInventory'])->name('inventory.expiring');
        Route::get('/inventory/movement', [ReportController::class, 'inventoryMovement'])->name('inventory.movement');
        Route::get('/inventory/valuation', [ReportController::class, 'stockValuation'])->name('inventory.valuation');

        // Wastage Reports
        Route::get('/wastage/summary', [ReportController::class, 'wastageSummary'])->name('wastage.summary');
        Route::get('/wastage/trends', [ReportController::class, 'wastageTrends'])->name('wastage.trends');
        Route::get('/wastage/by-source', [ReportController::class, 'wastageBySource'])->name('wastage.by-source');
        Route::get('/wastage/discrepancy-analysis', [ReportController::class, 'discrepancyAnalysis'])->name('wastage.discrepancy-analysis');
        Route::get('/wastage/collection-efficiency', [ReportController::class, 'collectionEfficiency'])->name('wastage.collection-efficiency');

        // Comparison Reports
        Route::get('/comparison/collection-sales', [ReportController::class, 'collectionSalesComparison'])->name('comparison.collection-sales');
        Route::get('/comparison/weekly', [ReportController::class, 'weeklyComparison'])->name('comparison.weekly');
        Route::get('/comparison/monthly-trend', [ReportController::class, 'monthlyTrend'])->name('comparison.monthly-trend');

        // PDF Downloads
        Route::get('/download/sales/daily', [ReportController::class, 'downloadDailySales'])->name('download.sales.daily');
        Route::get('/download/sales/weekly', [ReportController::class, 'downloadWeeklySales'])->name('download.sales.weekly');
        Route::get('/download/sales/monthly', [ReportController::class, 'downloadMonthlySales'])->name('download.sales.monthly');
        Route::get('/download/inventory/snapshot', [ReportController::class, 'downloadInventorySnapshot'])->name('download.inventory.snapshot');
        Route::get('/download/inventory/low-stock', [ReportController::class, 'downloadLowStock'])->name('download.inventory.low-stock');
        Route::get('/download/inventory/expiring', [ReportController::class, 'downloadExpiringInventory'])->name('download.inventory.expiring');
        Route::get('/download/wastage', [ReportController::class, 'downloadWastageReport'])->name('download.wastage');
        Route::get('/download/comparison', [ReportController::class, 'downloadComparisonReport'])->name('download.comparison');
        Route::get('/download/receipt/{sale}', [ReportController::class, 'downloadSaleReceipt'])->name('download.receipt');
        Route::get('/download/manifest/{delivery}', [ReportController::class, 'downloadDeliveryManifest'])->name('download.manifest');
    });

    // ==================== AUDITS ====================
    Route::prefix('audits')->name('audits.')->group(function () {
        Route::get('/', [AuditController::class, 'index'])->name('index');
        Route::get('/models', [AuditController::class, 'models'])->name('models');
        Route::get('/events', [AuditController::class, 'events'])->name('events');
        Route::get('/recent', [AuditController::class, 'recent'])->name('recent');
        Route::get('/{model}/{id}', [AuditController::class, 'show'])->name('show');
    });
});
}); // End of v1 prefix
