<?php

namespace App\Livewire\Inventory;

use App\Models\Batch;
use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\Shop;
use App\Models\WastageLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Log Wastage')]
class LogWastage extends Component
{
    public ?int $shopId = null;
    public ?int $categoryId = null;
    public ?int $batchId = null;
    public string $source = '';
    public int $quantity = 0;
    public string $reason = '';

    public bool $showConfirmModal = false;
    public bool $logged = false;
    public ?WastageLog $lastLog = null;

    protected $rules = [
        'shopId' => 'required|exists:shops,id',
        'categoryId' => 'required|exists:egg_categories,id',
        'batchId' => 'nullable|exists:batches,id',
        'source' => 'required|string',
        'quantity' => 'required|integer|min:1',
        'reason' => 'required|string|min:3|max:500',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id ?? Shop::first()?->id;
        $this->source = WastageLog::SOURCE_BREAKAGE;
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
     * Get available categories at shop.
     */
    #[Computed]
    public function categories(): Collection
    {
        if (!$this->shopId) {
            return collect();
        }

        return EggCategory::query()
            ->whereHas('inventories', function ($q) {
                $q->where('shop_id', $this->shopId)
                    ->where('available_stock', '>', 0);
            })
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get batches for selected category.
     */
    #[Computed]
    public function batches(): Collection
    {
        if (!$this->shopId || !$this->categoryId) {
            return collect();
        }

        return Batch::query()
            ->whereHas('inventories', function ($q) {
                $q->where('shop_id', $this->shopId)
                    ->where('egg_category_id', $this->categoryId)
                    ->where('available_stock', '>', 0);
            })
            ->with(['inventories' => function ($q) {
                $q->where('shop_id', $this->shopId)
                    ->where('egg_category_id', $this->categoryId);
            }])
            ->orderBy('collection_date')
            ->get()
            ->map(function ($batch) {
                $batch->available = $batch->inventories->sum('available_stock');
                return $batch;
            });
    }

    /**
     * Get wastage source options.
     */
    #[Computed]
    public function sourceOptions(): array
    {
        return WastageLog::SOURCES;
    }

    /**
     * Get max quantity available.
     */
    #[Computed]
    public function maxQuantity(): int
    {
        if (!$this->shopId || !$this->categoryId) {
            return 0;
        }

        $query = Inventory::where('shop_id', $this->shopId)
            ->where('egg_category_id', $this->categoryId);

        if ($this->batchId) {
            $query->where('batch_id', $this->batchId);
        }

        return $query->sum('available_stock');
    }

    /**
     * When shop changes, reset category and batch.
     */
    public function updatedShopId(): void
    {
        $this->categoryId = null;
        $this->batchId = null;
        $this->quantity = 0;
        unset($this->categories);
        unset($this->batches);
        unset($this->maxQuantity);
    }

    /**
     * When category changes, reset batch.
     */
    public function updatedCategoryId(): void
    {
        $this->batchId = null;
        $this->quantity = 0;
        unset($this->batches);
        unset($this->maxQuantity);
    }

    /**
     * Open confirmation modal.
     */
    public function confirmLog(): void
    {
        $this->validate();

        if ($this->quantity > $this->maxQuantity) {
            $this->addError('quantity', "Only {$this->maxQuantity} available.");
            return;
        }

        $this->showConfirmModal = true;
    }

    /**
     * Log the wastage.
     */
    public function logWastage(): void
    {
        $this->validate();

        if ($this->quantity > $this->maxQuantity) {
            session()->flash('error', 'Quantity exceeds available stock.');
            $this->showConfirmModal = false;
            return;
        }

        try {
            DB::transaction(function () {
                $remainingQty = $this->quantity;

                // Get inventories to deduct from (FIFO if no specific batch)
                $query = Inventory::where('shop_id', $this->shopId)
                    ->where('egg_category_id', $this->categoryId)
                    ->where('available_stock', '>', 0);

                if ($this->batchId) {
                    $query->where('batch_id', $this->batchId);
                } else {
                    $query->fifo();
                }

                $inventories = $query->lockForUpdate()->get();

                foreach ($inventories as $inventory) {
                    if ($remainingQty <= 0) break;

                    $deductQty = min($remainingQty, $inventory->available_stock);

                    // Create wastage log
                    $this->lastLog = WastageLog::create([
                        'shop_id' => $this->shopId,
                        'batch_id' => $inventory->batch_id,
                        'egg_category_id' => $this->categoryId,
                        'quantity' => $deductQty,
                        'source' => $this->source,
                        'reason' => $this->reason,
                        'logged_by' => auth()->id(),
                        'logged_at' => now(),
                    ]);

                    // Deduct from inventory
                    $inventory->available_stock -= $deductQty;
                    $inventory->save();

                    // Update batch
                    if ($inventory->batch) {
                        $inventory->batch->decrement('current_quantity', $deductQty);
                    }

                    $remainingQty -= $deductQty;
                }
            });

            $this->logged = true;
            $this->showConfirmModal = false;
            session()->flash('success', "Wastage of {$this->quantity} eggs logged successfully.");

        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
            $this->showConfirmModal = false;
        }
    }

    /**
     * Reset form for new entry.
     */
    public function resetForm(): void
    {
        $this->categoryId = null;
        $this->batchId = null;
        $this->source = WastageLog::SOURCE_BREAKAGE;
        $this->quantity = 0;
        $this->reason = '';
        $this->logged = false;
        $this->lastLog = null;
        unset($this->categories);
        unset($this->batches);
        unset($this->maxQuantity);
    }

    public function render()
    {
        return view('livewire.inventory.log-wastage');
    }
}
