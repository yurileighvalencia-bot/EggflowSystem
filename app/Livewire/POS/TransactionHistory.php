<?php

namespace App\Livewire\POS;

use App\Models\Sale;
use App\Models\Shop;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Transaction History')]
class TransactionHistory extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?int $shopId = null;
    public string $dateFilter = 'today';
    public ?string $statusFilter = null;
    public string $search = '';
    
    public bool $showVoidModal = false;
    public ?int $voidSaleId = null;
    public string $voidReason = '';

    public bool $showDetailModal = false;
    public ?Sale $selectedSale = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id ?? Shop::first()?->id;
    }

    /**
     * Get all shops for filter.
     */
    #[Computed]
    public function shops(): Collection
    {
        return Shop::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Get filtered sales.
     */
    public function getSalesProperty()
    {
        $query = Sale::query()
            ->when($this->shopId, fn ($q) => $q->where('shop_id', $this->shopId))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('sale_code', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->with(['shop:id,name', 'staff:id,name', 'items.eggCategory:id,name']);

        // Apply date filter
        switch ($this->dateFilter) {
            case 'today':
                $query->whereDate('sold_at', today());
                break;
            case 'yesterday':
                $query->whereDate('sold_at', today()->subDay());
                break;
            case 'week':
                $query->whereBetween('sold_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('sold_at', now()->month)->whereYear('sold_at', now()->year);
                break;
        }

        return $query->orderBy('sold_at', 'desc')->paginate(20);
    }

    /**
     * Today's summary statistics.
     */
    #[Computed]
    public function todaySummary(): array
    {
        $baseQuery = Sale::query()
            ->whereDate('sold_at', today())
            ->when($this->shopId, fn ($q) => $q->where('shop_id', $this->shopId));

        $completedQuery = (clone $baseQuery)->where('status', 'completed');
        $voidedQuery = (clone $baseQuery)->where('status', 'voided');

        return [
            'total_sales' => $completedQuery->count(),
            'total_revenue' => $completedQuery->sum('total'),
            'voided_count' => $voidedQuery->count(),
            'voided_amount' => $voidedQuery->sum('total'),
            'average_sale' => $completedQuery->count() > 0 
                ? $completedQuery->sum('total') / $completedQuery->count() 
                : 0,
        ];
    }

    /**
     * View sale details.
     */
    public function viewSale(int $saleId): void
    {
        $this->selectedSale = Sale::with(['shop', 'staff', 'customer', 'items.eggCategory', 'items.batch'])
            ->find($saleId);
        $this->showDetailModal = true;
    }

    /**
     * Open void confirmation modal.
     */
    public function confirmVoid(int $saleId): void
    {
        $sale = Sale::find($saleId);
        
        if (!$sale || $sale->status !== 'completed') {
            session()->flash('error', 'This sale cannot be voided.');
            return;
        }

        $this->voidSaleId = $saleId;
        $this->voidReason = '';
        $this->showVoidModal = true;
    }

    /**
     * Void a sale and restore inventory.
     */
    public function voidSale(): void
    {
        $this->authorize('manage-inventory');

        $this->validate([
            'voidReason' => 'required|min:3',
        ]);

        $sale = Sale::with('items')->find($this->voidSaleId);

        if (!$sale || $sale->status !== 'completed') {
            session()->flash('error', 'This sale cannot be voided.');
            $this->showVoidModal = false;
            return;
        }

        try {
            DB::transaction(function () use ($sale) {
                // Restore inventory for each item
                foreach ($sale->items as $item) {
                    $inventory = \App\Models\Inventory::where('shop_id', $sale->shop_id)
                        ->where('egg_category_id', $item->egg_category_id)
                        ->where('batch_id', $item->batch_id)
                        ->first();

                    if ($inventory) {
                        $inventory->available_stock += $item->quantity;
                        $inventory->save();
                    }

                    // Restore batch quantity
                    if ($item->batch) {
                        $item->batch->increment('current_quantity', $item->quantity);
                    }
                }

                // Update sale status
                $sale->status = 'voided';
                $sale->notes = ($sale->notes ? $sale->notes . "\n" : '') . "VOIDED: " . $this->voidReason;
                $sale->save();
            });

            session()->flash('success', 'Sale voided successfully. Inventory has been restored.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to void sale: ' . $e->getMessage());
        }

        $this->showVoidModal = false;
        $this->voidSaleId = null;
        $this->voidReason = '';
    }

    /**
     * Reset filters.
     */
    public function resetFilters(): void
    {
        $this->dateFilter = 'today';
        $this->statusFilter = null;
        $this->search = '';
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.pos.transaction-history', [
            'sales' => $this->sales,
        ]);
    }
}
