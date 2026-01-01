<?php

namespace App\Livewire\Inventory;

use App\Events\RestockRequestCreated;
use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\RestockRequest;
use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Low Stock Alerts')]
class LowStockAlerts extends Component
{
    public ?int $selectedShopId = null;
    public bool $showRequestModal = false;
    public ?int $requestCategoryId = null;
    public int $requestQuantity = 0;
    public string $requestNotes = '';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->shop_id) {
            $this->selectedShopId = $user->shop_id;
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
     * Get low stock alerts grouped by category.
     */
    #[Computed]
    public function lowStockAlerts(): Collection
    {
        return Inventory::query()
            ->select(
                'inventories.shop_id',
                'inventories.egg_category_id',
                DB::raw('SUM(inventories.available_stock) as available_stock'),
                DB::raw('SUM(inventories.reserved_stock) as reserved_stock')
            )
            ->join('egg_categories', 'inventories.egg_category_id', '=', 'egg_categories.id')
            ->join('shops', 'inventories.shop_id', '=', 'shops.id')
            ->whereRaw('inventories.available_stock <= egg_categories.low_stock_threshold')
            ->where('inventories.available_stock', '>', 0)
            ->where('shops.is_active', true)
            ->when($this->selectedShopId, fn ($q) => $q->where('inventories.shop_id', $this->selectedShopId))
            ->groupBy('inventories.shop_id', 'inventories.egg_category_id')
            ->with(['shop:id,name', 'eggCategory:id,name,code,low_stock_threshold,restock_quantity'])
            ->get()
            ->map(function ($item) {
                $item->threshold = $item->eggCategory->low_stock_threshold;
                $item->suggested_restock = $item->eggCategory->restock_quantity ?? 100;
                $item->deficit = max(0, $item->threshold - $item->available_stock);
                
                // Check if there's already a pending restock request
                $item->has_pending_request = RestockRequest::where('shop_id', $item->shop_id)
                    ->where('egg_category_id', $item->egg_category_id)
                    ->whereIn('status', ['pending', 'acknowledged'])
                    ->exists();
                
                return $item;
            })
            ->sortByDesc('deficit');
    }

    /**
     * Get out of stock items.
     */
    #[Computed]
    public function outOfStockAlerts(): Collection
    {
        return Inventory::query()
            ->select(
                'inventories.shop_id',
                'inventories.egg_category_id',
                DB::raw('SUM(inventories.available_stock) as available_stock')
            )
            ->join('shops', 'inventories.shop_id', '=', 'shops.id')
            ->where('inventories.available_stock', '<=', 0)
            ->where('shops.is_active', true)
            ->when($this->selectedShopId, fn ($q) => $q->where('inventories.shop_id', $this->selectedShopId))
            ->groupBy('inventories.shop_id', 'inventories.egg_category_id')
            ->with(['shop:id,name', 'eggCategory:id,name,code,restock_quantity'])
            ->get()
            ->map(function ($item) {
                $item->suggested_restock = $item->eggCategory->restock_quantity ?? 100;
                
                $item->has_pending_request = RestockRequest::where('shop_id', $item->shop_id)
                    ->where('egg_category_id', $item->egg_category_id)
                    ->whereIn('status', ['pending', 'acknowledged'])
                    ->exists();
                
                return $item;
            });
    }

    /**
     * Open the restock request modal.
     */
    public function openRequestModal(int $shopId, int $categoryId, int $suggestedQty): void
    {
        $this->selectedShopId = $shopId;
        $this->requestCategoryId = $categoryId;
        $this->requestQuantity = $suggestedQty;
        $this->requestNotes = '';
        $this->showRequestModal = true;
    }

    /**
     * Submit a restock request.
     */
    public function submitRestockRequest(): void
    {
        $this->validate([
            'requestQuantity' => 'required|integer|min:1',
        ]);

        $shop = Shop::find($this->selectedShopId);
        
        $request = RestockRequest::create([
            'shop_id' => $this->selectedShopId,
            'egg_category_id' => $this->requestCategoryId,
            'requested_quantity' => $this->requestQuantity,
            'requested_by' => auth()->id(),
            'notes' => $this->requestNotes,
            'status' => 'pending',
            'farm_id' => $shop?->farm_id,
        ]);

        event(new RestockRequestCreated($request));

        $this->showRequestModal = false;
        $this->requestCategoryId = null;
        $this->requestQuantity = 0;
        $this->requestNotes = '';

        // Clear computed cache
        unset($this->lowStockAlerts);
        unset($this->outOfStockAlerts);

        session()->flash('success', 'Restock request submitted successfully.');
    }

    /**
     * Handle real-time stock updates.
     */
    #[On('echo:stock.{selectedShopId},StockUpdated')]
    #[On('echo:stock.{selectedShopId},LowStockDetected')]
    public function handleStockUpdate(): void
    {
        unset($this->lowStockAlerts);
        unset($this->outOfStockAlerts);
    }

    public function render()
    {
        return view('livewire.inventory.low-stock-alerts');
    }
}
