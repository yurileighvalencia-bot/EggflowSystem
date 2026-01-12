<?php

namespace App\Livewire\Deliveries;

use App\Events\DiscrepancyInvestigated;
use App\Models\DeliveryDiscrepancy;
use App\Models\Shop;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Delivery Discrepancies')]
class DiscrepancyList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $shopId = null;

    #[Url]
    public string $status = 'pending'; // pending, investigating, resolved, all

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $sortBy = 'reported_at';

    #[Url]
    public string $sortDir = 'desc';

    public int $perPage = 15;

    // Investigation modal
    public bool $showInvestigateModal = false;
    public ?int $investigatingId = null;
    public string $resolution = '';
    public string $investigationNotes = '';
    public bool $isSubmitting = false;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->isShopStaff()) {
            $this->shopId = $user->shop_id;
        }
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
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
     * Get resolution options.
     */
    #[Computed]
    public function resolutions(): array
    {
        return DeliveryDiscrepancy::getResolutions();
    }

    /**
     * Get status filter options.
     */
    #[Computed]
    public function statusOptions(): array
    {
        return [
            'pending' => 'Pending Investigation',
            'investigating' => 'Under Investigation',
            'resolved' => 'Resolved',
            'all' => 'All Discrepancies',
        ];
    }

    /**
     * Get paginated discrepancies.
     */
    #[Computed]
    public function discrepancies()
    {
        return DeliveryDiscrepancy::query()
            ->with([
                'delivery.shop',
                'deliveryItem.eggCategory',
                'deliveryItem.batch',
                'reporter',
                'investigator',
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('delivery.shop', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('reporter', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('notes', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->shopId, function ($query) {
                $query->whereHas('delivery', fn($q) => $q->where('shop_id', $this->shopId));
            })
            ->when($this->status === 'pending', fn($q) => $q->whereNull('resolution')->whereNull('investigated_by'))
            ->when($this->status === 'investigating', fn($q) => $q->whereNull('resolution')->whereNotNull('investigated_by'))
            ->when($this->status === 'resolved', fn($q) => $q->whereNotNull('resolution'))
            ->when($this->dateFrom, fn($q) => $q->whereDate('reported_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('reported_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);
    }

    /**
     * Get summary stats.
     */
    #[Computed]
    public function stats(): array
    {
        $baseQuery = DeliveryDiscrepancy::query()
            ->when($this->shopId, function ($query) {
                $query->whereHas('delivery', fn($q) => $q->where('shop_id', $this->shopId));
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('reported_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('reported_at', '<=', $this->dateTo));

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereNull('resolution')->whereNull('investigated_by')->count(),
            'investigating' => (clone $baseQuery)->whereNull('resolution')->whereNotNull('investigated_by')->count(),
            'resolved' => (clone $baseQuery)->whereNotNull('resolution')->count(),
            'total_missing' => (clone $baseQuery)->sum('qty_missing'),
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
        $this->status = 'pending';
        if (!auth()->user()?->isShopStaff()) {
            $this->reset('shopId');
        }
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    /**
     * Open investigation modal.
     */
    public function openInvestigateModal(int $id): void
    {
        $this->investigatingId = $id;
        $this->resolution = '';
        $this->investigationNotes = '';
        $this->showInvestigateModal = true;
    }

    /**
     * Close investigation modal.
     */
    public function closeInvestigateModal(): void
    {
        $this->showInvestigateModal = false;
        $this->investigatingId = null;
        $this->resolution = '';
        $this->investigationNotes = '';
    }

    /**
     * Start investigation (claim discrepancy).
     */
    public function startInvestigation(int $id): void
    {
        $this->authorize('investigate-discrepancy');

        $discrepancy = DeliveryDiscrepancy::findOrFail($id);
        
        if ($discrepancy->investigated_by && $discrepancy->investigated_by !== auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'This discrepancy is already being investigated by another user.',
            ]);
            return;
        }

        $discrepancy->update([
            'investigated_by' => auth()->id(),
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'You are now investigating this discrepancy.',
        ]);

        unset($this->discrepancies);
        unset($this->stats);
    }

    /**
     * Submit investigation resolution.
     */
    public function submitInvestigation(): void
    {
        $this->authorize('investigate-discrepancy');

        $this->isSubmitting = true;

        try {
            $this->validate([
                'resolution' => 'required|in:' . implode(',', array_keys($this->resolutions)),
                'investigationNotes' => 'nullable|string|max:1000',
            ]);

            $discrepancy = DeliveryDiscrepancy::findOrFail($this->investigatingId);
            
            $discrepancy->investigate(
                auth()->id(),
                $this->resolution,
                $this->investigationNotes ?: null
            );

            // Fire event for notifications
            event(new DiscrepancyInvestigated($discrepancy));

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Discrepancy resolved successfully.',
            ]);

            $this->closeInvestigateModal();
            unset($this->discrepancies);
            unset($this->stats);
        } finally {
            $this->isSubmitting = false;
        }
    }

    /**
     * Refresh list when discrepancy is reported.
     */
    #[On('discrepancy-reported')]
    public function refreshList(): void
    {
        unset($this->discrepancies);
        unset($this->stats);
    }

    /**
     * Get status badge class.
     */
    public function getStatusClass(DeliveryDiscrepancy $discrepancy): string
    {
        if ($discrepancy->resolution) {
            return match ($discrepancy->resolution) {
                DeliveryDiscrepancy::RESOLUTION_APPROVED => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400',
                DeliveryDiscrepancy::RESOLUTION_REJECTED => 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-400',
                DeliveryDiscrepancy::RESOLUTION_PARTIAL_LOSS => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-400',
                default => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400',
            };
        }

        if ($discrepancy->investigated_by) {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400';
        }

        return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400';
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(DeliveryDiscrepancy $discrepancy): string
    {
        if ($discrepancy->resolution) {
            return $this->resolutions[$discrepancy->resolution] ?? 'Resolved';
        }

        if ($discrepancy->investigated_by) {
            return 'Investigating';
        }

        return 'Pending';
    }

    public function render()
    {
        return view('livewire.deliveries.discrepancy-list');
    }
}
