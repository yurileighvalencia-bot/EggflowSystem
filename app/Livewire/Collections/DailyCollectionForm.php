<?php

namespace App\Livewire\Collections;

use App\Models\Batch;
use App\Models\DailyCollection;
use App\Models\EggCategory;
use App\Models\Farm;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Daily Collection Form')]
class DailyCollectionForm extends Component
{
    public ?int $farmId = null;
    public string $collectionDate = '';
    public string $collectionTime = '';
    public string $notes = '';

    /**
     * Matrix input: [category_id => quantity]
     */
    public array $collectionData = [];

    public bool $showConfirmation = false;
    public array $createdCollections = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->farmId = $user?->farm_id ?? Farm::first()?->id;
        $this->collectionDate = now()->format('Y-m-d');
        $this->collectionTime = now()->format('H:i');

        // Initialize collection data for all active categories
        $this->initializeCollectionData();
    }

    /**
     * Initialize collection data matrix with all active egg categories.
     */
    protected function initializeCollectionData(): void
    {
        $categories = EggCategory::where('is_active', true)->orderBy('sort_order')->get();
        
        foreach ($categories as $category) {
            $this->collectionData[$category->id] = [
                'category_id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'quantity' => 0,
            ];
        }
    }

    /**
     * Get the current farm.
     */
    #[Computed]
    public function farm(): ?Farm
    {
        return Farm::find($this->farmId);
    }

    /**
     * Get all active farms.
     */
    #[Computed]
    public function farms()
    {
        return Farm::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get all active egg categories.
     */
    #[Computed]
    public function categories()
    {
        return EggCategory::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Update quantity for a category.
     */
    public function updateQuantity(int $categoryId, int $quantity): void
    {
        if (isset($this->collectionData[$categoryId])) {
            $this->collectionData[$categoryId]['quantity'] = max(0, (int) $quantity);
        }
    }

    /**
     * Get total eggs collected.
     */
    #[Computed]
    public function totalQuantity(): int
    {
        return collect($this->collectionData)->sum('quantity');
    }

    /**
     * Get categories with quantities > 0.
     */
    #[Computed]
    public function categoriesWithData(): array
    {
        return collect($this->collectionData)
            ->filter(fn($item) => $item['quantity'] > 0)
            ->toArray();
    }

    /**
     * Validate and show confirmation.
     */
    public function showConfirm(): void
    {
        $this->validate([
            'farmId' => 'required|exists:farms,id',
            'collectionDate' => 'required|date|before_or_equal:today',
            'collectionTime' => 'required|date_format:H:i',
        ]);

        if ($this->totalQuantity <= 0) {
            session()->flash('error', 'Please enter at least one collection quantity.');
            return;
        }

        $this->showConfirmation = true;
    }

    /**
     * Cancel confirmation.
     */
    public function cancelConfirm(): void
    {
        $this->showConfirmation = false;
    }

    /**
     * Submit all collections.
     */
    public function submit(): void
    {
        $this->validate([
            'farmId' => 'required|exists:farms,id',
            'collectionDate' => 'required|date|before_or_equal:today',
            'collectionTime' => 'required|date_format:H:i',
        ]);

        if ($this->totalQuantity <= 0) {
            session()->flash('error', 'Please enter at least one collection quantity.');
            return;
        }

        try {
            DB::transaction(function () {
                $user = auth()->user();
                $this->createdCollections = [];

                foreach ($this->collectionData as $categoryId => $data) {
                    if ($data['quantity'] <= 0) {
                        continue;
                    }

                    // Find or create a batch for this date, farm, and category
                    $batch = Batch::firstOrCreate(
                        [
                            'farm_id' => $this->farmId,
                            'egg_category_id' => $categoryId,
                            'collection_date' => $this->collectionDate,
                        ],
                        [
                            'initial_quantity' => 0,
                            'current_quantity' => 0,
                            'status' => 'active',
                            'created_by' => $user->id,
                        ]
                    );

                    // Update batch quantities
                    $batch->increment('initial_quantity', $data['quantity']);
                    $batch->increment('current_quantity', $data['quantity']);

                    // Create the daily collection record
                    $collection = DailyCollection::create([
                        'staff_id' => $user->id,
                        'batch_id' => $batch->id,
                        'egg_category_id' => $categoryId,
                        'farm_id' => $this->farmId,
                        'quantity' => $data['quantity'],
                        'collection_date' => $this->collectionDate,
                        'collection_time' => $this->collectionDate . ' ' . $this->collectionTime,
                        'notes' => $this->notes,
                        'is_verified' => false,
                    ]);

                    $this->createdCollections[] = [
                        'id' => $collection->id,
                        'category' => $data['name'],
                        'quantity' => $data['quantity'],
                        'batch_code' => $batch->batch_code,
                    ];
                }
            });

            session()->flash('success', count($this->createdCollections) . ' collection records created successfully.');
            $this->showConfirmation = false;
            $this->reset(['collectionData', 'notes']);
            $this->initializeCollectionData();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save collections: ' . $e->getMessage());
        }
    }

    /**
     * Reset form.
     */
    public function resetForm(): void
    {
        $this->reset(['notes', 'showConfirmation', 'createdCollections']);
        $this->collectionDate = now()->format('Y-m-d');
        $this->collectionTime = now()->format('H:i');
        $this->initializeCollectionData();
    }

    public function render()
    {
        return view('livewire.collections.daily-collection-form');
    }
}
