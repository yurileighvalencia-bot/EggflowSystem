<?php

namespace App\Livewire\Collections;

use App\Models\DailyCollection;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Collection Records')]
class CollectionList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $farmId = null;

    #[Url]
    public ?int $categoryId = null;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $verificationStatus = '';

    #[Url]
    public string $sortBy = 'collection_date';

    #[Url]
    public string $sortDir = 'desc';

    public int $perPage = 15;

    // Edit modal properties
    public bool $showEditModal = false;
    public ?DailyCollection $editingCollection = null;
    public int $editQuantity = 0;
    public string $editReason = '';

    // View history modal
    public bool $showHistoryModal = false;
    public ?DailyCollection $viewingCollection = null;

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
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
     * Get all egg categories for filter.
     */
    #[Computed]
    public function categories()
    {
        return EggCategory::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Get paginated collections.
     */
    #[Computed]
    public function collections()
    {
        return DailyCollection::query()
            ->with(['staff', 'farm', 'eggCategory', 'batch', 'verifier', 'revisions'])
            ->when($this->search, function ($query) {
                $query->whereHas('staff', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('batch', function ($q) {
                    $q->where('batch_code', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->farmId, fn($q) => $q->where('farm_id', $this->farmId))
            ->when($this->categoryId, fn($q) => $q->where('egg_category_id', $this->categoryId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('collection_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('collection_date', '<=', $this->dateTo))
            ->when($this->verificationStatus !== '', function ($query) {
                if ($this->verificationStatus === 'verified') {
                    $query->verified();
                } elseif ($this->verificationStatus === 'unverified') {
                    $query->unverified();
                }
            })
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);
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
            $this->sortDir = 'asc';
        }
    }

    /**
     * Reset filters.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'farmId', 'categoryId', 'verificationStatus']);
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    /**
     * Open edit modal.
     */
    public function edit(int $collectionId): void
    {
        $this->editingCollection = DailyCollection::find($collectionId);
        if ($this->editingCollection) {
            $this->editQuantity = $this->editingCollection->quantity;
            $this->editReason = '';
            $this->showEditModal = true;
        }
    }

    /**
     * Save edit.
     */
    public function saveEdit(): void
    {
        $this->validate([
            'editQuantity' => 'required|integer|min:1',
            'editReason' => 'required|string|min:5|max:500',
        ]);

        if (!$this->editingCollection) {
            return;
        }

        // Create revision record
        $this->editingCollection->createRevision(
            ['quantity' => $this->editQuantity],
            $this->editReason,
            auth()->id()
        );

        // Update the collection
        $oldQuantity = $this->editingCollection->quantity;
        $this->editingCollection->update([
            'quantity' => $this->editQuantity,
            'is_verified' => false, // Reset verification on edit
            'verified_by' => null,
            'verified_at' => null,
        ]);

        // Update batch quantities
        if ($this->editingCollection->batch) {
            $difference = $this->editQuantity - $oldQuantity;
            $this->editingCollection->batch->increment('initial_quantity', $difference);
            $this->editingCollection->batch->increment('current_quantity', $difference);
        }

        $this->showEditModal = false;
        $this->editingCollection = null;
        session()->flash('success', 'Collection record updated successfully.');
    }

    /**
     * Cancel edit.
     */
    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingCollection = null;
        $this->reset(['editQuantity', 'editReason']);
    }

    /**
     * View revision history.
     */
    public function viewHistory(int $collectionId): void
    {
        $this->viewingCollection = DailyCollection::with(['revisions.changer'])->find($collectionId);
        $this->showHistoryModal = true;
    }

    /**
     * Close history modal.
     */
    public function closeHistory(): void
    {
        $this->showHistoryModal = false;
        $this->viewingCollection = null;
    }

    public function render()
    {
        return view('livewire.collections.collection-list');
    }
}
