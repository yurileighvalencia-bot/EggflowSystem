<?php

namespace App\Livewire\RestockRequests;

use App\Events\RestockRequestCreated;
use App\Models\EggCategory;
use App\Models\RestockRequest;
use App\Models\Shop;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateRestockRequest extends Component
{
    use AuthorizesRequests;
    public ?int $shopId = null;
    public ?int $categoryId = null;
    public int $quantity = 0;
    public string $notes = '';

    protected $rules = [
        'shopId' => 'required|exists:shops,id',
        'categoryId' => 'required|exists:egg_categories,id',
        'quantity' => 'required|integer|min:1',
        'notes' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'shopId.required' => 'Please select a shop.',
        'categoryId.required' => 'Please select an egg category.',
        'quantity.required' => 'Please enter the requested quantity.',
        'quantity.min' => 'Quantity must be at least 1.',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id ?? Shop::first()?->id;
    }

    /**
     * Get shops for dropdown.
     */
    #[Computed]
    public function shops(): Collection
    {
        return Shop::orderBy('name')->get();
    }

    /**
     * Get egg categories for dropdown.
     */
    #[Computed]
    public function categories(): Collection
    {
        return EggCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Check if there's already an active request for this shop/category.
     */
    #[Computed]
    public function hasActiveRequest(): bool
    {
        if (!$this->shopId || !$this->categoryId) {
            return false;
        }

        return RestockRequest::hasActiveRequest($this->shopId, $this->categoryId);
    }

    /**
     * Create a new restock request.
     */
    public function save(): void
    {
        $this->authorize('create-restock-request');

        $this->validate();

        // Check for active request
        if ($this->hasActiveRequest) {
            $this->addError('categoryId', 'An active request already exists for this category at this shop.');
            return;
        }

        $request = RestockRequest::create([
            'shop_id' => $this->shopId,
            'egg_category_id' => $this->categoryId,
            'quantity_requested' => $this->quantity,
            'quantity_remaining' => $this->quantity,
            'status' => RestockRequest::STATUS_PENDING,
            'requested_by' => auth()->id(),
            'notes' => $this->notes ?: null,
        ]);

        event(new RestockRequestCreated($request));

        session()->flash('success', 'Restock request has been created.');
        
        $this->dispatch('restock-request-created');
        $this->reset(['categoryId', 'quantity', 'notes']);
    }

    /**
     * Cancel and close the modal.
     */
    public function cancel(): void
    {
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.restock-requests.create-restock-request');
    }
}
