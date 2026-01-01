<?php

namespace App\Livewire\Inventory;

use App\Models\Batch;
use App\Models\Farm;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Carbon\Carbon;

#[Layout('components.layouts.dashboard')]
#[Title('Expiring Batches')]
class ExpiringBatches extends Component
{
    public ?int $selectedFarmId = null;
    public int $daysAhead = 7;
    public string $sortBy = 'expires_at';
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->farm_id) {
            $this->selectedFarmId = $user->farm_id;
        }
    }

    /**
     * Get all farms for the filter dropdown.
     */
    #[Computed]
    public function farms(): Collection
    {
        return Farm::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get batches expiring within the selected timeframe.
     */
    #[Computed]
    public function expiringBatches(): Collection
    {
        return Batch::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($this->daysAhead))
            ->when($this->selectedFarmId, fn ($q) => $q->where('farm_id', $this->selectedFarmId))
            ->with(['farm:id,name', 'eggCategory:id,name,code'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get()
            ->map(function ($batch) {
                $batch->days_until_expiry = Carbon::parse($batch->expires_at)->diffInDays(now());
                $batch->urgency = $this->calculateUrgency($batch->days_until_expiry);
                return $batch;
            });
    }

    /**
     * Get already expired batches with remaining stock.
     */
    #[Computed]
    public function expiredBatches(): Collection
    {
        return Batch::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('expires_at', '<=', now())
            ->when($this->selectedFarmId, fn ($q) => $q->where('farm_id', $this->selectedFarmId))
            ->with(['farm:id,name', 'eggCategory:id,name,code'])
            ->orderBy('expires_at', 'asc')
            ->get()
            ->map(function ($batch) {
                $batch->days_expired = Carbon::parse($batch->expires_at)->diffInDays(now());
                return $batch;
            });
    }

    /**
     * Get summary statistics.
     */
    #[Computed]
    public function summary(): array
    {
        $baseQuery = Batch::where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->when($this->selectedFarmId, fn ($q) => $q->where('farm_id', $this->selectedFarmId));

        $expiredCount = (clone $baseQuery)->where('expires_at', '<=', now())->count();
        $expiredQty = (clone $baseQuery)->where('expires_at', '<=', now())->sum('current_quantity');

        $criticalCount = (clone $baseQuery)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDay())
            ->count();
        $criticalQty = (clone $baseQuery)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDay())
            ->sum('current_quantity');

        $warningCount = (clone $baseQuery)
            ->where('expires_at', '>', now()->addDay())
            ->where('expires_at', '<=', now()->addDays(3))
            ->count();
        $warningQty = (clone $baseQuery)
            ->where('expires_at', '>', now()->addDay())
            ->where('expires_at', '<=', now()->addDays(3))
            ->sum('current_quantity');

        return [
            'expired' => ['count' => $expiredCount, 'quantity' => $expiredQty],
            'critical' => ['count' => $criticalCount, 'quantity' => $criticalQty],
            'warning' => ['count' => $warningCount, 'quantity' => $warningQty],
        ];
    }

    /**
     * Calculate urgency level based on days until expiry.
     */
    private function calculateUrgency(int $days): string
    {
        if ($days <= 1) {
            return 'critical';
        }
        if ($days <= 3) {
            return 'warning';
        }
        return 'normal';
    }

    /**
     * Set sort column and toggle direction.
     */
    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        unset($this->expiringBatches);
    }

    /**
     * Handle real-time batch updates.
     */
    #[On('echo:batches,BatchExpired')]
    public function handleBatchUpdate(): void
    {
        unset($this->expiringBatches);
        unset($this->expiredBatches);
        unset($this->summary);
    }

    public function render()
    {
        return view('livewire.inventory.expiring-batches');
    }
}
