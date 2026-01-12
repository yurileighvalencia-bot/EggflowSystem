<?php

namespace App\Livewire\Deliveries;

use App\Models\Delivery;
use App\Models\Shop;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Deliveries')]
class DeliveryList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $shopId = null;

    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $sortBy = 'dispatched_at';

    #[Url]
    public string $sortDir = 'desc';

    public int $perPage = 15;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->isShopStaff()) {
            $this->shopId = $user->shop_id;
        }
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /**
     * Get the current shop ID for Echo listener.
     */
    #[Computed]
    public function currentShopId(): ?int
    {
        return $this->shopId ?? auth()->user()?->shop_id;
    }

    /**
     * Listen for new delivery dispatched events via Echo.
     */
    #[On('echo:deliveries.{currentShopId},DeliveryDispatched')]
    public function handleDeliveryDispatched(): void
    {
        // Refresh the deliveries list when a new delivery is dispatched
        unset($this->deliveries);
        unset($this->stats);
    }

    /**
     * Refresh list when discrepancy is reported.
     */
    #[On('discrepancy-reported')]
    public function handleDiscrepancyReported(): void
    {
        unset($this->deliveries);
        unset($this->stats);
    }

    /**
     * Get all shops for filter.
     */
    #[Computed]
    public function shops()
    {
        return Shop::orderBy('name')->get();
    }

    /**
     * Get delivery statuses.
     */
    #[Computed]
    public function statuses(): array
    {
        return [
            Delivery::STATUS_DISPATCHED => 'Dispatched',
            Delivery::STATUS_IN_TRANSIT => 'In Transit',
            Delivery::STATUS_RECEIVED => 'Received',
            Delivery::STATUS_PARTIAL => 'Partial',
            Delivery::STATUS_DISPUTED => 'Disputed',
            Delivery::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get paginated deliveries.
     */
    #[Computed]
    public function deliveries()
    {
        return Delivery::query()
            ->with(['shop', 'dispatcher', 'receiver', 'items.eggCategory', 'discrepancies'])
            ->when($this->search, function ($query) {
                $query->whereHas('shop', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('dispatcher', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn($q) => $q->whereDate('dispatched_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('dispatched_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);
    }

    /**
     * Get summary stats.
     */
    #[Computed]
    public function stats(): array
    {
        $query = Delivery::query()
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('dispatched_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('dispatched_at', '<=', $this->dateTo));

        return [
            'total' => (clone $query)->count(),
            'in_transit' => (clone $query)->where('status', Delivery::STATUS_IN_TRANSIT)->count(),
            'received' => (clone $query)->where('status', Delivery::STATUS_RECEIVED)->count(),
            'disputed' => (clone $query)->where('status', Delivery::STATUS_DISPUTED)->count(),
        ];
    }

    /**
     * Sort by column.
     */
    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
    }

    /**
     * Reset filters.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'status']);
        if (!auth()->user()?->isShopStaff()) {
            $this->reset('shopId');
        }
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    /**
     * Get status badge class.
     */
    public function getStatusClass(string $status): string
    {
        return match ($status) {
            Delivery::STATUS_DISPATCHED => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400',
            Delivery::STATUS_IN_TRANSIT => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
            Delivery::STATUS_RECEIVED => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400',
            Delivery::STATUS_PARTIAL => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-400',
            Delivery::STATUS_DISPUTED => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400',
            Delivery::STATUS_CANCELLED => 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-400',
        };
    }

    public function render()
    {
        return view('livewire.deliveries.delivery-list');
    }
}
