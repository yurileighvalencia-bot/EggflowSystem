<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Collections\CollectionList;
use App\Livewire\Collections\DailyCollectionForm;
use App\Livewire\Collections\VerifyCollections;
use App\Livewire\Dashboard;
use App\Livewire\Dashboard\FarmDashboard;
use App\Livewire\Dashboard\ManagerDashboard;
use App\Livewire\Dashboard\ShopDashboard;
use App\Livewire\Deliveries\DeliveryList;
use App\Livewire\Deliveries\DispatchDelivery;
use App\Livewire\Deliveries\ReceiveDelivery;
use App\Livewire\Inventory\ExpiringBatches;
use App\Livewire\Inventory\LowStockAlerts;
use App\Livewire\Inventory\StockOverview;
use App\Livewire\POS\PointOfSale;
use App\Livewire\POS\TransactionHistory;
use Illuminate\Support\Facades\Route;

// ==========================================
// Public Routes
// ==========================================
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

// ==========================================
// Guest Routes (Auth)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

// ==========================================
// Authenticated Routes
// ==========================================
Route::middleware(['auth'])->group(function () {
    // Email Verification
    Route::get('/email/verify', VerifyEmail::class)
        ->name('verification.notice');

    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    // Smart dashboard redirect based on user role
    Route::get('/home', function () {
        $user = auth()->user();

        // Route based on role
        if ($user->hasRole('Manager')) {
            return redirect()->route('manager.dashboard');
        }

        if ($user->hasRole('Farm Staff')) {
            return redirect()->route('farm.dashboard');
        }

        if ($user->hasRole('Shop Staff')) {
            return redirect()->route('shop.dashboard');
        }

        // Default fallback
        return redirect()->route('web.dashboard');
    })->name('home');

    // Legacy dashboard (for backward compatibility)
    Route::get('/dashboard', Dashboard::class)->name('web.dashboard');

    // Role-based dashboards
    Route::get('/manager/dashboard', ManagerDashboard::class)
        ->middleware('role:manager')
        ->name('manager.dashboard');
    Route::get('/shop/dashboard', ShopDashboard::class)
        ->middleware('role:shop_staff|manager')
        ->name('shop.dashboard');
    Route::get('/farm/dashboard', FarmDashboard::class)
        ->middleware('role:farm_staff|manager')
        ->name('farm.dashboard');

    // ==========================================
    // Inventory Module Routes
    // ==========================================
    Route::prefix('inventory')->name('inventory.')->middleware('permission:view-inventory')->group(function () {
        Route::get('/stock', StockOverview::class)->name('stock');
        Route::get('/low-stock', LowStockAlerts::class)->name('low-stock');
        Route::get('/expiring', ExpiringBatches::class)->name('expiring');
        Route::get('/log-wastage', \App\Livewire\Inventory\LogWastage::class)
            ->middleware('permission:log-wastage')
            ->name('log-wastage');
        Route::get('/wastage-history', \App\Livewire\Inventory\WastageHistory::class)
            ->middleware('permission:view-wastage')
            ->name('wastage-history');
    });

    // ==========================================
    // POS Module Routes
    // ==========================================
    Route::prefix('pos')->name('pos.')->middleware('permission:create-sale|view-sales')->group(function () {
        Route::get('/', PointOfSale::class)
            ->middleware('permission:create-sale')
            ->name('index');
        Route::get('/history', TransactionHistory::class)
            ->middleware('permission:view-sales')
            ->name('history');
    });

    // Legacy route alias for POS (keeping backward compatibility)
    Route::get('/pos-sale', PointOfSale::class)
        ->middleware('permission:create-sale')
        ->name('pos');

    // ==========================================
    // Print Routes
    // ==========================================
    Route::get('/print/receipt/{sale}', function (\App\Models\Sale $sale) {
        $sale->load(['shop', 'staff', 'items.category']);
        return view('print.receipt', compact('sale'));
    })->name('print.receipt');

    // ==========================================
    // Collections Module Routes
    // ==========================================
    Route::prefix('collections')->name('collections.')->middleware('permission:view-collections')->group(function () {
        Route::get('/', CollectionList::class)->name('index');
        Route::get('/create', DailyCollectionForm::class)
            ->middleware('permission:create-collection')
            ->name('create');
        Route::get('/verify', VerifyCollections::class)
            ->middleware('permission:verify-collection')
            ->name('verify');
    });

    // ==========================================
    // Deliveries Module Routes
    // ==========================================
    Route::prefix('deliveries')->name('deliveries.')->middleware('permission:view-deliveries')->group(function () {
        Route::get('/', DeliveryList::class)->name('index');
        Route::get('/dispatch', DispatchDelivery::class)
            ->middleware('permission:dispatch-delivery')
            ->name('dispatch');
        Route::get('/discrepancies', \App\Livewire\Deliveries\DiscrepancyList::class)
            ->middleware('permission:report-discrepancy|investigate-discrepancy')
            ->name('discrepancies');
        Route::get('/{delivery}', DeliveryList::class)->name('show');
        Route::get('/{delivery}/receive', ReceiveDelivery::class)
            ->middleware('permission:receive-delivery')
            ->name('receive');
    });

    // ==========================================
    // Reservations Module Routes
    // ==========================================
    Route::prefix('reservations')->name('reservations.')->middleware('permission:view-reservations')->group(function () {
        Route::get('/', \App\Livewire\Reservations\ReservationList::class)->name('index');
        Route::get('/create', \App\Livewire\Reservations\CreateReservation::class)
            ->middleware('permission:create-reservation')
            ->name('create');
        Route::get('/{reservation}/convert', \App\Livewire\Reservations\ConvertToSale::class)
            ->middleware('permission:confirm-reservation')
            ->name('convert');
    });

    // ==========================================
    // Reports Module Routes
    // ==========================================
    Route::prefix('reports')->name('reports.')->middleware('permission:view-reports')->group(function () {
        Route::get('/sales', \App\Livewire\Reports\SalesSummary::class)->name('sales');
        Route::get('/shifts', \App\Livewire\Reports\ShiftReport::class)->name('shifts');
        Route::get('/inventory', \App\Livewire\Reports\InventoryReport::class)->name('inventory');
        Route::get('/wastage', \App\Livewire\Reports\WastageReport::class)->name('wastage');
        Route::get('/collection-sales', \App\Livewire\Reports\CollectionSalesComparison::class)->name('collection-sales');
    });

    // ==========================================
    // Activity Feed
    // ==========================================
    Route::get('/activity', \App\Livewire\Dashboard\ActivityFeed::class)->name('activity-feed');

    // ==========================================
    // Notification Center
    // ==========================================
    Route::get('/notifications', \App\Livewire\Dashboard\NotificationCenter::class)->name('notifications');
    Route::get('/notifications/preferences', \App\Livewire\Settings\NotificationPreferences::class)->name('notifications.preferences');

    // ==========================================
    // Settings Module Routes
    // ==========================================
    Route::prefix('settings')->name('settings.')->middleware('role:manager')->group(function () {
        Route::get('/', \App\Livewire\Settings\SettingsPage::class)->name('index');
        Route::get('/categories', \App\Livewire\Settings\CategoryManagement::class)
            ->middleware('permission:manage-categories')
            ->name('categories');
        Route::get('/users', \App\Livewire\Settings\UserManagement::class)
            ->middleware('permission:manage-users')
            ->name('users');
    });

    // ==========================================
    // Restock Requests Module Routes
    // ==========================================
    Route::prefix('restock-requests')->name('restock-requests.')->middleware('permission:view-restock-requests')->group(function () {
        Route::get('/', \App\Livewire\RestockRequests\RestockRequestList::class)->name('index');
    });
});
