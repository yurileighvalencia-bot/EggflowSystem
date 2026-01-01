<?php

namespace App\Livewire\Dashboard;

use App\Models\Delivery;
use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\RestockRequest;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Shop Dashboard')]
class ShopDashboard extends Component
{
    public ?int $shopId = null;

    public function mount(): void
    {
        // Get shop from authenticated user or default to first shop for testing
        $this->shopId = auth()->user()?->shop_id ?? \App\Models\Shop::first()?->id;
    }

    /**
     * Current stock levels by category for this shop.
     */
    #[Computed]
    public function stockByCategory(): Collection
    {
        if (!$this->shopId) {
            return collect();
        }

        return Inventory::where('shop_id', $this->shopId)
            ->select(
                'egg_category_id',
                DB::raw('SUM(available_stock) as available'),
                DB::raw('SUM(reserved_stock) as reserved')
            )
            ->groupBy('egg_category_id')
            ->with('eggCategory:id,name,default_price,low_stock_threshold')
            ->get();
    }

    /**
     * Low stock alerts for this shop.
     */
    #[Computed]
    public function lowStockAlerts(): Collection
    {
        if (!$this->shopId) {
            return collect();
        }

        return Inventory::where('shop_id', $this->shopId)
            ->join('egg_categories', 'inventories.egg_category_id', '=', 'egg_categories.id')
            ->whereRaw('inventories.available_stock <= egg_categories.low_stock_threshold')
            ->where('inventories.available_stock', '>', 0)
            ->select('inventories.*')
            ->with('eggCategory:id,name')
            ->get();
    }

    /**
     * Today's sales metrics.
     */
    #[Computed]
    public function todaySales(): array
    {
        if (!$this->shopId) {
            return ['count' => 0, 'revenue' => 0, 'items_sold' => 0];
        }

        $sales = Sale::where('shop_id', $this->shopId)
            ->whereDate('created_at', today());

        return [
            'count' => $sales->count(),
            'revenue' => (clone $sales)->sum('total_amount'),
            'items_sold' => Sale::where('shop_id', $this->shopId)
                ->whereDate('created_at', today())
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->sum('sale_items.quantity'),
        ];
    }

    /**
     * Today's pending reservations/pickups.
     */
    #[Computed]
    public function todayPickups(): Collection
    {
        if (!$this->shopId) {
            return collect();
        }

        return Reservation::where('shop_id', $this->shopId)
            ->whereIn('status', ['pending', 'confirmed', 'ready'])
            ->whereDate('pickup_date', today())
            ->with(['customer:id,name,phone', 'items.eggCategory:id,name'])
            ->orderBy('pickup_time')
            ->get();
    }

    /**
     * Incoming deliveries.
     */
    #[Computed]
    public function incomingDeliveries(): Collection
    {
        if (!$this->shopId) {
            return collect();
        }

        return Delivery::where('shop_id', $this->shopId)
            ->where('status', 'dispatched')
            ->with(['items.eggCategory:id,name', 'dispatcher:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    /**
     * Pending restock requests from this shop.
     */
    #[Computed]
    public function pendingRestockRequests(): Collection
    {
        if (!$this->shopId) {
            return collect();
        }

        return RestockRequest::where('shop_id', $this->shopId)
            ->whereIn('status', ['pending', 'acknowledged'])
            ->with('eggCategory:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Quick action: Navigate to POS.
     */
    public function goToPOS(): void
    {
        $this->redirect(route('pos'), navigate: true);
    }

    /**
     * Quick action: Navigate to reservations.
     */
    public function goToReservations(): void
    {
        $this->redirect(route('reservations'), navigate: true);
    }

    public function render()
    {
        return view('livewire.dashboard.shop-dashboard');
    }
}
