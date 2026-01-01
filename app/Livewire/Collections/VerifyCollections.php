<?php

namespace App\Livewire\Collections;

use App\Models\DailyCollection;
use App\Models\Farm;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Verify Collections')]
class VerifyCollections extends Component
{
    use WithPagination;

    #[Url]
    public ?int $farmId = null;

    #[Url]
    public string $date = '';

    public int $perPage = 20;

    // Verification modal
    public bool $showVerifyModal = false;
    public array $selectedCollections = [];
    public int $totalSelected = 0;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Get all farms for filter.
     */
    #[Computed]
    public function farms()
    {
        return Farm::orderBy('name')->get();
    }

    /**
     * Get unverified collections.
     */
    #[Computed]
    public function collections()
    {
        return DailyCollection::query()
            ->with(['staff', 'farm', 'eggCategory', 'batch'])
            ->unverified()
            ->when($this->farmId, fn($q) => $q->where('farm_id', $this->farmId))
            ->when($this->date, fn($q) => $q->whereDate('collection_date', $this->date))
            ->orderBy('collection_date', 'desc')
            ->orderBy('egg_category_id')
            ->paginate($this->perPage);
    }

    /**
     * Get summary by category for the selected date/farm.
     */
    #[Computed]
    public function summary()
    {
        return DailyCollection::query()
            ->selectRaw('egg_category_id, SUM(quantity) as total_quantity, COUNT(*) as record_count')
            ->with('eggCategory')
            ->unverified()
            ->when($this->farmId, fn($q) => $q->where('farm_id', $this->farmId))
            ->when($this->date, fn($q) => $q->whereDate('collection_date', $this->date))
            ->groupBy('egg_category_id')
            ->get();
    }

    /**
     * Toggle selection of a collection.
     */
    public function toggleSelection(int $collectionId): void
    {
        if (in_array($collectionId, $this->selectedCollections)) {
            $this->selectedCollections = array_values(array_diff($this->selectedCollections, [$collectionId]));
        } else {
            $this->selectedCollections[] = $collectionId;
        }
        $this->totalSelected = count($this->selectedCollections);
    }

    /**
     * Select all visible collections.
     */
    public function selectAll(): void
    {
        $this->selectedCollections = $this->collections->pluck('id')->toArray();
        $this->totalSelected = count($this->selectedCollections);
    }

    /**
     * Deselect all.
     */
    public function deselectAll(): void
    {
        $this->selectedCollections = [];
        $this->totalSelected = 0;
    }

    /**
     * Show verification confirmation.
     */
    public function confirmVerify(): void
    {
        if (empty($this->selectedCollections)) {
            session()->flash('error', 'Please select at least one collection to verify.');
            return;
        }
        $this->showVerifyModal = true;
    }

    /**
     * Cancel verification.
     */
    public function cancelVerify(): void
    {
        $this->showVerifyModal = false;
    }

    /**
     * Verify selected collections.
     */
    public function verifySelected(): void
    {
        if (empty($this->selectedCollections)) {
            session()->flash('error', 'No collections selected.');
            return;
        }

        $user = auth()->user();
        $count = 0;

        foreach ($this->selectedCollections as $collectionId) {
            $collection = DailyCollection::find($collectionId);
            if ($collection && !$collection->is_verified) {
                $collection->verify($user->id);
                $count++;
            }
        }

        $this->selectedCollections = [];
        $this->totalSelected = 0;
        $this->showVerifyModal = false;

        session()->flash('success', "{$count} collection(s) verified successfully.");
    }

    /**
     * Verify a single collection.
     */
    public function verifySingle(int $collectionId): void
    {
        $collection = DailyCollection::find($collectionId);
        if ($collection && !$collection->is_verified) {
            $collection->verify(auth()->id());
            session()->flash('success', 'Collection verified successfully.');
        }
    }

    public function render()
    {
        return view('livewire.collections.verify-collections');
    }
}
