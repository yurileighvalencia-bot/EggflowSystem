<?php

namespace App\Livewire\Dashboard;

use App\Models\Batch;
use App\Models\Delivery;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\RestockRequest;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use App\Models\WastageLog;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Manager Dashboard')]
class ManagerDashboard extends Component
{
    /**
     * Overview statistics for the manager.
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'total_farms' => Farm::count(),
            'total_shops' => Shop::count(),
            'total_users' => User::count(),
            'total_categories' => EggCategory::count(),
        ];
    }

    /**
     * Inventory health metrics.
     */
    #[Computed]
    public function inventoryHealth(): array
    {
        $activeBatches = Batch::where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->count();

        $lowStockAlerts = Inventory::join('egg_categories', 'inventories.egg_category_id', '=', 'egg_categories.id')
            ->whereRaw('inventories.available_stock <= egg_categories.low_stock_threshold')
            ->where('inventories.available_stock', '>', 0)
            ->count();

        $expiringBatches = Batch::where('status', 'active')
            ->where('expires_at', '<=', now()->addDays(3))
            ->where('expires_at', '>', now())
            ->count();

        $totalStock = Batch::where('status', 'active')->sum('current_quantity');

        return [
            'active_batches' => $activeBatches,
            'low_stock_alerts' => $lowStockAlerts,
            'expiring_soon' => $expiringBatches,
            'total_stock' => $totalStock,
        ];
    }

    /**
     * Today's operational metrics.
     */
    #[Computed]
    public function todayMetrics(): array
    {
        $salesToday = Sale::whereDate('created_at', today())->count();
        $revenueToday = Sale::whereDate('created_at', today())->sum('total_amount');
        $eggsSoldToday = Sale::whereDate('created_at', today())
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->sum('sale_items.quantity');

        $pendingReservations = Reservation::whereIn('status', ['pending', 'confirmed', 'ready'])
            ->whereDate('pickup_date', today())
            ->count();

        return [
            'sales_count' => $salesToday,
            'revenue' => $revenueToday,
            'eggs_sold' => $eggsSoldToday,
            'pending_pickups' => $pendingReservations,
        ];
    }

    /**
     * Supply chain status.
     */
    #[Computed]
    public function supplyChainStatus(): array
    {
        return [
            'pending_restock_requests' => RestockRequest::where('status', 'pending')->count(),
            'acknowledged_requests' => RestockRequest::where('status', 'acknowledged')->count(),
            'deliveries_in_transit' => Delivery::where('status', 'dispatched')->count(),
            'deliveries_today' => Delivery::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Recent wastage summary.
     */
    #[Computed]
    public function wastageSummary(): array
    {
        $wastageThisWeek = WastageLog::where('created_at', '>=', now()->startOfWeek())
            ->sum('quantity');

        $wastageBySource = WastageLog::where('created_at', '>=', now()->startOfWeek())
            ->select('source', DB::raw('SUM(quantity) as total'))
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        return [
            'total_this_week' => $wastageThisWeek,
            'by_source' => $wastageBySource,
        ];
    }

    /**
     * Stock levels by category for chart.
     */
    #[Computed]
    public function stockByCategory(): array
    {
        return Inventory::select('egg_category_id', DB::raw('SUM(available_stock) as total'))
            ->groupBy('egg_category_id')
            ->with('eggCategory:id,name')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->eggCategory->name ?? 'Unknown' => $item->total])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.manager-dashboard');
    }
}
