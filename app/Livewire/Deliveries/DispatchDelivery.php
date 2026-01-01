<?php

namespace App\Livewire\Deliveries;

use App\Events\DeliveryDispatched;
use App\Models\Batch;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\RestockRequest;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Dispatch Delivery')]
class DispatchDelivery extends Component
{
    public ?int $shopId = null;
    public ?int $restockRequestId = null;
    public string $notes = '';

    /**
     * Items to dispatch: [category_id => ['batch_id' => x, 'quantity' => y]]
     */
    public array $dispatchItems = [];

    public bool $showConfirmation = false;

    public function mount(?int $restockRequestId = null): void
    {
        $this->restockRequestId = $restockRequestId;

        if ($this->restockRequestId) {
            $request = RestockRequest::with('shop')->find($this->restockRequestId);
            if ($request) {
                $this->shopId = $request->shop_id;
            }
        }

        $this->initializeDispatchItems();
    }

    /**
     * Initialize dispatch items from available batches.
     */
    protected function initializeDispatchItems(): void
    {
        $categories = EggCategory::where('is_active', true)->orderBy('sort_order')->get();

        foreach ($categories as $category) {
            $this->dispatchItems[$category->id] = [
                'category_id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'batch_id' => null,
                'quantity' => 0,
            ];
        }
    }

    /**
     * Get all shops.
     */
    #[Computed]
    public function shops()
    {
        return Shop::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get pending restock requests.
     */
    #[Computed]
    public function restockRequests()
    {
        return RestockRequest::with('shop')
            ->where('status', 'pending')
            ->orWhere('status', 'acknowledged')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get available batches for dispatch (FIFO - oldest first).
     */
    #[Computed]
    public function availableBatches()
    {
        return Batch::with('eggCategory')
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->orderBy('collection_date')
            ->orderBy('expires_at')
            ->get()
            ->groupBy('egg_category_id');
    }

    /**
     * Get total quantity to dispatch.
     */
    #[Computed]
    public function totalQuantity(): int
    {
        return collect($this->dispatchItems)->sum('quantity');
    }

    /**
     * Get items with quantities.
     */
    #[Computed]
    public function itemsWithData(): array
    {
        return collect($this->dispatchItems)
            ->filter(fn($item) => $item['quantity'] > 0 && $item['batch_id'])
            ->toArray();
    }

    /**
     * Update quantity for a category.
     */
    public function updateQuantity(int $categoryId, int $quantity): void
    {
        if (!isset($this->dispatchItems[$categoryId])) {
            return;
        }

        // Check available stock in selected batch
        $batchId = $this->dispatchItems[$categoryId]['batch_id'];
        if ($batchId) {
            $batch = Batch::find($batchId);
            if ($batch && $quantity > $batch->current_quantity) {
                session()->flash('error', "Only {$batch->current_quantity} available in selected batch.");
                return;
            }
        }

        $this->dispatchItems[$categoryId]['quantity'] = max(0, $quantity);
    }

    /**
     * Auto-select oldest batch for category (FIFO).
     */
    public function autoSelectBatch(int $categoryId): void
    {
        $batches = $this->availableBatches[$categoryId] ?? collect();
        $oldestBatch = $batches->first();

        if ($oldestBatch) {
            $this->dispatchItems[$categoryId]['batch_id'] = $oldestBatch->id;
        }
    }

    /**
     * Show confirmation modal.
     */
    public function showConfirm(): void
    {
        $this->validate([
            'shopId' => 'required|exists:shops,id',
        ]);

        if ($this->totalQuantity <= 0) {
            session()->flash('error', 'Please add at least one item to dispatch.');
            return;
        }

        // Validate each item has a batch selected
        foreach ($this->itemsWithData as $item) {
            if (!$item['batch_id']) {
                session()->flash('error', 'Please select a batch for all items.');
                return;
            }
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
     * Submit delivery.
     */
    public function submit(): void
    {
        $this->validate([
            'shopId' => 'required|exists:shops,id',
        ]);

        if ($this->totalQuantity <= 0) {
            session()->flash('error', 'Please add at least one item to dispatch.');
            return;
        }

        try {
            DB::transaction(function () {
                $user = auth()->user();

                // Create delivery
                $delivery = Delivery::create([
                    'restock_request_id' => $this->restockRequestId,
                    'shop_id' => $this->shopId,
                    'dispatched_by' => $user->id,
                    'status' => Delivery::STATUS_IN_TRANSIT,
                    'dispatched_at' => now(),
                    'notes' => $this->notes,
                ]);

                // Create delivery items and update batch quantities
                foreach ($this->itemsWithData as $item) {
                    $batch = Batch::find($item['batch_id']);
                    if (!$batch) {
                        continue;
                    }

                    // Create delivery item
                    DeliveryItem::create([
                        'delivery_id' => $delivery->id,
                        'batch_id' => $item['batch_id'],
                        'egg_category_id' => $item['category_id'],
                        'qty_dispatched' => $item['quantity'],
                        'qty_received' => null, // Set on receive
                        'qty_rejected' => null,
                        'unit_price' => $batch->eggCategory->default_price ?? 0,
                    ]);

                    // Decrement batch quantity
                    $batch->decrement('current_quantity', $item['quantity']);

                    // Check if batch is depleted
                    if ($batch->current_quantity <= 0) {
                        $batch->update(['status' => 'depleted']);
                    }
                }

                // Update restock request if linked
                if ($this->restockRequestId) {
                    RestockRequest::where('id', $this->restockRequestId)
                        ->update(['status' => 'fulfilled']);
                }

                // Dispatch event
                event(new DeliveryDispatched($delivery));

                session()->flash('success', 'Delivery dispatched successfully.');
                $this->redirect(route('deliveries.index'));
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to dispatch delivery: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.deliveries.dispatch-delivery');
    }
}
