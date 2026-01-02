<?php

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

Route::get('/', function () {
    return view('welcome');
});

// Legacy dashboard (for backward compatibility during development)
Route::get('/dashboard', Dashboard::class)->name('web.dashboard');

// Role-based dashboards (no auth middleware during development)
Route::get('/manager/dashboard', ManagerDashboard::class)->name('manager.dashboard');
Route::get('/shop/dashboard', ShopDashboard::class)->name('shop.dashboard');
Route::get('/farm/dashboard', FarmDashboard::class)->name('farm.dashboard');

// Smart dashboard redirect based on user role
Route::get('/home', function () {
    $user = auth()->user();

    if (!$user) {
        // Default to manager dashboard for testing when not authenticated
        return redirect()->route('manager.dashboard');
    }

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

// ==========================================
// Inventory Module Routes
// ==========================================
Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/stock', StockOverview::class)->name('stock');
    Route::get('/low-stock', LowStockAlerts::class)->name('low-stock');
    Route::get('/expiring', ExpiringBatches::class)->name('expiring');
    Route::get('/log-wastage', \App\Livewire\Inventory\LogWastage::class)->name('log-wastage');
    Route::get('/wastage-history', \App\Livewire\Inventory\WastageHistory::class)->name('wastage-history');
});

// ==========================================
// POS Module Routes
// ==========================================
Route::prefix('pos')->name('pos.')->group(function () {
    Route::get('/', PointOfSale::class)->name('index');
    Route::get('/history', TransactionHistory::class)->name('history');
});

// Legacy route alias for POS (keeping backward compatibility)
Route::get('/pos-sale', PointOfSale::class)->name('pos');

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
Route::prefix('collections')->name('collections.')->group(function () {
    Route::get('/', CollectionList::class)->name('index');
    Route::get('/create', DailyCollectionForm::class)->name('create');
    Route::get('/verify', VerifyCollections::class)->name('verify');
});

// ==========================================
// Deliveries Module Routes
// ==========================================
Route::prefix('deliveries')->name('deliveries.')->group(function () {
    Route::get('/', DeliveryList::class)->name('index');
    Route::get('/dispatch', DispatchDelivery::class)->name('dispatch');
    Route::get('/{delivery}', DeliveryList::class)->name('show');
    Route::get('/{delivery}/receive', ReceiveDelivery::class)->name('receive');
});

// ==========================================
// Reservations Module Routes
// ==========================================
Route::prefix('reservations')->name('reservations.')->group(function () {
    Route::get('/', \App\Livewire\Reservations\ReservationList::class)->name('index');
    Route::get('/create', \App\Livewire\Reservations\CreateReservation::class)->name('create');
    Route::get('/{reservation}/convert', \App\Livewire\Reservations\ConvertToSale::class)->name('convert');
});

// ==========================================
// Reports Module Routes
// ==========================================
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/sales', \App\Livewire\Reports\SalesSummary::class)->name('sales');
    Route::get('/shifts', \App\Livewire\Reports\ShiftReport::class)->name('shifts');
});

// ==========================================
// Activity Feed
// ==========================================
Route::get('/activity', \App\Livewire\Dashboard\ActivityFeed::class)->name('activity-feed');

// ==========================================
// Notification Center
// ==========================================
Route::get('/notifications', \App\Livewire\Dashboard\NotificationCenter::class)->name('notifications');

// ==========================================
// Settings Module Routes
// ==========================================
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', \App\Livewire\Settings\SettingsPage::class)->name('index');
    Route::get('/categories', \App\Livewire\Settings\CategoryManagement::class)->name('categories');
    Route::get('/users', \App\Livewire\Settings\UserManagement::class)->name('users');
});

// ==========================================
// Placeholder Routes (to be implemented)
// ==========================================
Route::prefix('restock-requests')->name('restock-requests.')->group(function () {
    Route::get('/', \App\Livewire\RestockRequests\RestockRequestList::class)->name('index');
});
