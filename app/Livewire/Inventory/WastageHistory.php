<?php

namespace App\Livewire\Inventory;

use App\Models\Shop;
use App\Models\WastageLog;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Wastage History')]
class WastageHistory extends Component
{
    use WithPagination;

    #[Url]
    public ?int $shopId = null;

    #[Url]
    public string $source = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id;
        
        // Default to last 30 days
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /**
     * Get shops for filter.
     */
    #[Computed]
    public function shops(): Collection
    {
        return Shop::orderBy('name')->get();
    }

    /**
     * Get source options.
     */
    #[Computed]
    public function sourceOptions(): array
    {
        return WastageLog::SOURCES;
    }

    /**
     * Get wastage stats for the period.
     */
    #[Computed]
    public function stats(): array
    {
        $baseQuery = WastageLog::query()
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('logged_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('logged_at', '<=', $this->dateTo));

        $bySource = (clone $baseQuery)
            ->selectRaw('source, SUM(quantity) as total')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        return [
            'total_entries' => (clone $baseQuery)->count(),
            'total_quantity' => (clone $baseQuery)->sum('quantity'),
            'by_source' => $bySource,
        ];
    }

    /**
     * Get paginated wastage logs.
     */
    #[Computed]
    public function wastageLogs()
    {
        return WastageLog::query()
            ->with(['shop', 'eggCategory', 'batch', 'loggedBy'])
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->source, fn($q) => $q->where('source', $this->source))
            ->when($this->dateFrom, fn($q) => $q->whereDate('logged_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('logged_at', '<=', $this->dateTo))
            ->orderByDesc('logged_at')
            ->paginate(20);
    }

    /**
     * Get source color for badges.
     */
    public function getSourceColor(string $source): string
    {
        return match($source) {
            WastageLog::SOURCE_BREAKAGE => 'red',
            WastageLog::SOURCE_SHOP_SPOILAGE => 'orange',
            WastageLog::SOURCE_BATCH_EXPIRED => 'yellow',
            WastageLog::SOURCE_DELIVERY_REJECTION => 'blue',
            WastageLog::SOURCE_DAMAGED_IN_TRANSIT => 'purple',
            WastageLog::SOURCE_THEFT => 'pink',
            WastageLog::SOURCE_INVENTORY_ADJUSTMENT => 'gray',
            default => 'gray',
        };
    }

    /**
     * Clear filters.
     */
    public function clearFilters(): void
    {
        $this->shopId = null;
        $this->source = '';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.inventory.wastage-history');
    }
}
