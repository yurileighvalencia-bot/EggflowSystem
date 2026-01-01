<?php

namespace App\Livewire\Inventory;

use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Stock Overview')]
class StockOverview extends Component
{
    public ?int $selectedShopId = null;
    public ?int $selectedCategoryId = null;
    public string $search = '';

    public function mount(): void
    {
        // Default to user's shop or first shop
        $user = auth()->user();
        if ($user?->shop_id) {
            $this->selectedShopId = $user->shop_id;
        } elseif ($user?->hasRole('Manager')) {
            // Managers see all shops initially
            $this->selectedShopId = null;
        } else {
            $this->selectedShopId = Shop::first()?->id;
        }
    }

    /**
     * Get all shops for the filter dropdown.
     */
    #[Computed]
    public function shops(): Collection
    {
        return Shop::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get all categories for the filter dropdown.
     */
    #[Computed]
    public function categories(): Collection
    {
        return EggCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code']);
    }

    /**
     * Get the stock grid data (per-shop, per-category).
     */
    #[Computed]
    public function stockGrid(): Collection
    {
        $query = Inventory::query()
            ->select(
                'inventories.shop_id',
                'inventories.egg_category_id',
                DB::raw('SUM(inventories.available_stock) as available_stock'),
                DB::raw('SUM(inventories.reserved_stock) as reserved_stock'),
                DB::raw('AVG(inventories.unit_price) as avg_price')
            )
            ->join('shops', 'inventories.shop_id', '=', 'shops.id')
            ->join('egg_categories', 'inventories.egg_category_id', '=', 'egg_categories.id')
            ->where('shops.is_active', true)
            ->where('egg_categories.is_active', true)
            ->groupBy('inventories.shop_id', 'inventories.egg_category_id');

        if ($this->selectedShopId) {
            $query->where('inventories.shop_id', $this->selectedShopId);
        }

        if ($this->selectedCategoryId) {
            $query->where('inventories.egg_category_id', $this->selectedCategoryId);
        }

        return $query->with(['shop:id,name', 'eggCategory:id,name,code,low_stock_threshold,default_price'])
            ->get()
            ->map(function ($item) {
                $threshold = $item->eggCategory->low_stock_threshold ?? 0;
                $item->is_low_stock = $item->available_stock <= $threshold && $item->available_stock > 0;
                $item->is_out_of_stock = $item->available_stock <= 0;
                $item->total_stock = $item->available_stock + $item->reserved_stock;
                return $item;
            });
    }

    /**
     * Summary statistics.
     */
    #[Computed]
    public function summary(): array
    {
        $baseQuery = Inventory::query();

        if ($this->selectedShopId) {
            $baseQuery->where('shop_id', $this->selectedShopId);
        }

        $totalAvailable = (clone $baseQuery)->sum('available_stock');
        $totalReserved = (clone $baseQuery)->sum('reserved_stock');

        $lowStockCount = Inventory::query()
            ->join('egg_categories', 'inventories.egg_category_id', '=', 'egg_categories.id')
            ->whereRaw('inventories.available_stock <= egg_categories.low_stock_threshold')
            ->where('inventories.available_stock', '>', 0)
            ->when($this->selectedShopId, fn ($q) => $q->where('inventories.shop_id', $this->selectedShopId))
            ->count();

        $outOfStockCount = Inventory::query()
            ->where('available_stock', '<=', 0)
            ->when($this->selectedShopId, fn ($q) => $q->where('shop_id', $this->selectedShopId))
            ->count();

        return [
            'total_available' => $totalAvailable,
            'total_reserved' => $totalReserved,
            'total_stock' => $totalAvailable + $totalReserved,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ];
    }

    /**
     * Handle real-time stock updates via Echo.
     */
    #[On('echo:stock.{selectedShopId},StockUpdated')]
    public function handleStockUpdate(): void
    {
        // Force recompute of computed properties
        unset($this->stockGrid);
        unset($this->summary);
    }

    /**
     * Clear filters.
     */
    public function clearFilters(): void
    {
        $this->selectedShopId = null;
        $this->selectedCategoryId = null;
        $this->search = '';
    }

    public function render()
    {
        return view('livewire.inventory.stock-overview');
    }
}
