<?php

namespace App\Livewire\RestockRequests;

use App\Events\RestockRequestAcknowledged;
use App\Models\Farm;
use App\Models\RestockRequest;
use App\Models\Shop;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Restock Requests')]
class RestockRequestList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $shopId = null;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public bool $showCreateModal = false;

    public function mount(): void
    {
        $user = auth()->user();
        
        // Shop staff see only their shop
        if ($user?->isShopStaff()) {
            $this->shopId = $user->shop_id;
        }
        
        // Default date range: last 30 days
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /**
     * Get farms for Echo listener (for farm staff).
     */
    #[Computed]
    public function farmId(): ?int
    {
        return auth()->user()?->farm_id;
    }

    /**
     * Listen for new restock requests via Echo.
     */
    #[On('echo:farm.{farmId},RestockRequestCreated')]
    public function handleRestockRequestCreated(): void
    {
        // Refresh the list when a new request is created
        unset($this->restockRequests);
        unset($this->stats);
    }

    /**
     * Get shops for filter dropdown.
     */
    #[Computed]
    public function shops(): Collection
    {
        return Shop::orderBy('name')->get();
    }

    /**
     * Get available statuses.
     */
    #[Computed]
    public function statuses(): array
    {
        return [
            RestockRequest::STATUS_PENDING => 'Pending',
            RestockRequest::STATUS_ACKNOWLEDGED => 'Acknowledged',
            RestockRequest::STATUS_IN_TRANSIT => 'In Transit',
            RestockRequest::STATUS_PARTIAL => 'Partial',
            RestockRequest::STATUS_DELIVERED => 'Delivered',
            RestockRequest::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get summary stats.
     */
    #[Computed]
    public function stats(): array
    {
        $baseQuery = RestockRequest::query()
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId));

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', RestockRequest::STATUS_PENDING)->count(),
            'acknowledged' => (clone $baseQuery)->where('status', RestockRequest::STATUS_ACKNOWLEDGED)->count(),
            'in_transit' => (clone $baseQuery)->where('status', RestockRequest::STATUS_IN_TRANSIT)->count(),
            'delivered' => (clone $baseQuery)->where('status', RestockRequest::STATUS_DELIVERED)->count(),
        ];
    }

    /**
     * Get paginated restock requests.
     */
    #[Computed]
    public function restockRequests()
    {
        return RestockRequest::query()
            ->with(['shop', 'eggCategory', 'requester', 'acknowledger'])
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->whereHas('shop', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('eggCategory', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    /**
     * Acknowledge a pending restock request.
     */
    public function acknowledge(int $id): void
    {
        $this->authorize('acknowledge-restock-request');

        $request = RestockRequest::findOrFail($id);
        
        if ($request->status !== RestockRequest::STATUS_PENDING) {
            session()->flash('error', 'Only pending requests can be acknowledged.');
            return;
        }

        $request->acknowledge(auth()->id());
        
        event(new RestockRequestAcknowledged($request));
        
        session()->flash('success', 'Restock request has been acknowledged.');
        unset($this->restockRequests);
        unset($this->stats);
    }

    /**
     * Open create modal.
     */
    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    /**
     * Close create modal.
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    /**
     * Handle request created from modal.
     */
    #[On('restock-request-created')]
    public function handleRequestCreated(): void
    {
        $this->showCreateModal = false;
        unset($this->restockRequests);
        unset($this->stats);
    }

    /**
     * Get status badge class.
     */
    public function getStatusClass(string $status): string
    {
        return match ($status) {
            RestockRequest::STATUS_PENDING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
            RestockRequest::STATUS_ACKNOWLEDGED => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400',
            RestockRequest::STATUS_IN_TRANSIT => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-400',
            RestockRequest::STATUS_PARTIAL => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-400',
            RestockRequest::STATUS_DELIVERED => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400',
            RestockRequest::STATUS_CANCELLED => 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-400',
        };
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
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.restock-requests.restock-request-list');
    }
}
