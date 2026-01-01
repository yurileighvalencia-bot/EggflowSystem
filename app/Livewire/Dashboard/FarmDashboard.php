<?php

namespace App\Livewire\Dashboard;

use App\Models\Batch;
use App\Models\DailyCollection;
use App\Models\Delivery;
use App\Models\RestockRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Farm Dashboard')]
class FarmDashboard extends Component
{
    public ?int $farmId = null;

    public function mount(): void
    {
        // Get farm from authenticated user or default to first farm for testing
        $this->farmId = auth()->user()?->farm_id ?? \App\Models\Farm::first()?->id;
    }

    /**
     * Today's collection summary.
     */
    #[Computed]
    public function todayCollections(): array
    {
        if (!$this->farmId) {
            return ['total' => 0, 'by_category' => []];
        }

        $collections = DailyCollection::where('farm_id', $this->farmId)
            ->whereDate('collection_date', today())
            ->select('egg_category_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('egg_category_id')
            ->with('eggCategory:id,name')
            ->get();

        return [
            'total' => $collections->sum('total'),
            'by_category' => $collections->mapWithKeys(
                fn ($item) => [$item->eggCategory->name ?? 'Unknown' => $item->total]
            )->toArray(),
        ];
    }

    /**
     * Active batches at this farm.
     */
    #[Computed]
    public function activeBatches(): Collection
    {
        if (!$this->farmId) {
            return collect();
        }

        return Batch::where('farm_id', $this->farmId)
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->with('eggCategory:id,name')
            ->orderBy('expires_at')
            ->take(10)
            ->get();
    }

    /**
     * Batches expiring soon (within 3 days).
     */
    #[Computed]
    public function expiringBatches(): Collection
    {
        if (!$this->farmId) {
            return collect();
        }

        return Batch::where('farm_id', $this->farmId)
            ->where('status', 'active')
            ->where('expires_at', '<=', now()->addDays(3))
            ->where('expires_at', '>', now())
            ->where('current_quantity', '>', 0)
            ->with('eggCategory:id,name')
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Pending restock requests to fulfill.
     */
    #[Computed]
    public function pendingRestockRequests(): Collection
    {
        if (!$this->farmId) {
            return collect();
        }

        // Get requests from shops that belong to this farm
        return RestockRequest::whereHas('shop', fn ($q) => $q->where('farm_id', $this->farmId))
            ->where('status', 'pending')
            ->with(['shop:id,name', 'eggCategory:id,name', 'requester:id,name'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Acknowledged requests ready for dispatch.
     */
    #[Computed]
    public function acknowledgedRequests(): Collection
    {
        if (!$this->farmId) {
            return collect();
        }

        return RestockRequest::whereHas('shop', fn ($q) => $q->where('farm_id', $this->farmId))
            ->where('status', 'acknowledged')
            ->with(['shop:id,name', 'eggCategory:id,name'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Recent deliveries dispatched.
     */
    #[Computed]
    public function recentDeliveries(): Collection
    {
        if (!$this->farmId) {
            return collect();
        }

        return Delivery::whereHas('shop', fn ($q) => $q->where('farm_id', $this->farmId))
            ->orderBy('created_at', 'desc')
            ->with(['shop:id,name', 'items.eggCategory:id,name'])
            ->take(5)
            ->get();
    }

    /**
     * Weekly collection trend.
     */
    #[Computed]
    public function weeklyCollectionTrend(): array
    {
        if (!$this->farmId) {
            return [];
        }

        return DailyCollection::where('farm_id', $this->farmId)
            ->where('collection_date', '>=', now()->subDays(7))
            ->select('collection_date', DB::raw('SUM(quantity) as total'))
            ->groupBy('collection_date')
            ->orderBy('collection_date')
            ->pluck('total', 'collection_date')
            ->toArray();
    }

    /**
     * Quick action: Record new collection.
     */
    public function goToCollections(): void
    {
        $this->redirect(route('collections'), navigate: true);
    }

    /**
     * Quick action: View restock requests.
     */
    public function goToRestockRequests(): void
    {
        $this->redirect(route('restock-requests'), navigate: true);
    }

    public function render()
    {
        return view('livewire.dashboard.farm-dashboard');
    }
}
