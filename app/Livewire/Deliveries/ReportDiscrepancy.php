<?php

namespace App\Livewire\Deliveries;

use App\Events\DeliveryDiscrepancyReported;
use App\Models\Delivery;
use App\Models\DeliveryDiscrepancy;
use App\Models\DeliveryItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ReportDiscrepancy extends Component
{
    use AuthorizesRequests;

    public bool $showSlideOver = false;
    
    public ?int $deliveryId = null;
    public ?int $deliveryItemId = null;
    
    public int $qtySent = 0;
    public int $qtyReceived = 0;
    public int $qtyRejected = 0;
    public int $qtyMissing = 0;
    
    public string $notes = '';
    
    public bool $isSubmitting = false;

    protected function rules(): array
    {
        return [
            'deliveryId' => 'required|exists:deliveries,id',
            'deliveryItemId' => 'nullable|exists:delivery_items,id',
            'qtySent' => 'required|integer|min:0',
            'qtyReceived' => 'required|integer|min:0',
            'qtyRejected' => 'required|integer|min:0',
            'qtyMissing' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'deliveryId.required' => 'Please select a delivery.',
            'qtyMissing.min' => 'Missing quantity cannot be negative.',
        ];
    }

    /**
     * Get the delivery being reported.
     */
    #[Computed]
    public function delivery(): ?Delivery
    {
        if (!$this->deliveryId) {
            return null;
        }
        
        return Delivery::with(['shop', 'items.eggCategory', 'items.batch'])
            ->find($this->deliveryId);
    }

    /**
     * Get the selected delivery item.
     */
    #[Computed]
    public function deliveryItem(): ?DeliveryItem
    {
        if (!$this->deliveryItemId) {
            return null;
        }
        
        return DeliveryItem::with(['eggCategory', 'batch'])
            ->find($this->deliveryItemId);
    }

    /**
     * Open the slide-over for a specific delivery.
     */
    #[On('open-discrepancy-form')]
    public function openForm(int $deliveryId, ?int $deliveryItemId = null): void
    {
        // Check permission
        if (!auth()->user()?->can('report-discrepancy')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to report discrepancies.',
            ]);
            return;
        }

        $this->reset();
        $this->deliveryId = $deliveryId;
        $this->deliveryItemId = $deliveryItemId;
        
        // Pre-fill with delivery item data if available
        if ($deliveryItemId) {
            $item = DeliveryItem::find($deliveryItemId);
            if ($item) {
                $this->qtySent = $item->qty_sent;
                $this->qtyReceived = $item->qty_received ?? 0;
                $this->qtyRejected = $item->qty_rejected ?? 0;
                $this->qtyMissing = $item->missing_quantity;
            }
        } elseif ($this->delivery) {
            // Pre-fill with delivery totals
            $this->qtySent = $this->delivery->total_sent;
            $this->qtyReceived = $this->delivery->total_received;
            $this->qtyRejected = $this->delivery->total_rejected;
            $this->qtyMissing = max(0, $this->qtySent - $this->qtyReceived - $this->qtyRejected);
        }
        
        $this->showSlideOver = true;
    }

    /**
     * Calculate missing quantity when other quantities change.
     */
    public function updatedQtySent(): void
    {
        $this->calculateMissing();
    }

    public function updatedQtyReceived(): void
    {
        $this->calculateMissing();
    }

    public function updatedQtyRejected(): void
    {
        $this->calculateMissing();
    }

    protected function calculateMissing(): void
    {
        $this->qtyMissing = max(0, $this->qtySent - $this->qtyReceived - $this->qtyRejected);
    }

    /**
     * Select a specific delivery item.
     */
    public function selectItem(int $itemId): void
    {
        $this->deliveryItemId = $itemId;
        $item = DeliveryItem::find($itemId);
        
        if ($item) {
            $this->qtySent = $item->qty_sent;
            $this->qtyReceived = $item->qty_received ?? 0;
            $this->qtyRejected = $item->qty_rejected ?? 0;
            $this->qtyMissing = $item->missing_quantity;
        }
    }

    /**
     * Close the slide-over.
     */
    public function closeForm(): void
    {
        $this->showSlideOver = false;
        $this->reset();
    }

    /**
     * Submit the discrepancy report.
     */
    public function submit(): void
    {
        // Authorize the action
        $this->authorize('report-discrepancy');

        $this->isSubmitting = true;
        
        try {
            $this->validate();

            // Ensure there's actually a discrepancy to report
            if ($this->qtyMissing <= 0 && $this->qtyRejected <= 0) {
                $this->addError('qtyMissing', 'There must be missing or rejected quantity to report a discrepancy.');
                return;
            }

            $discrepancy = DeliveryDiscrepancy::create([
                'delivery_id' => $this->deliveryId,
                'delivery_item_id' => $this->deliveryItemId,
                'qty_sent' => $this->qtySent,
                'qty_received' => $this->qtyReceived,
                'qty_rejected' => $this->qtyRejected,
                'qty_missing' => $this->qtyMissing,
                'reported_by' => auth()->id(),
                'notes' => $this->notes ?: null,
                'reported_at' => now(),
            ]);

            // Update delivery status to disputed if not already
            $delivery = Delivery::find($this->deliveryId);
            if ($delivery && $delivery->status !== Delivery::STATUS_DISPUTED) {
                $delivery->update(['status' => Delivery::STATUS_DISPUTED]);
            }

            // Fire event for notifications
            event(new DeliveryDiscrepancyReported($discrepancy));

            $this->dispatch('discrepancy-reported');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Discrepancy reported successfully. Management has been notified.',
            ]);

            $this->closeForm();
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render()
    {
        return view('livewire.deliveries.report-discrepancy');
    }
}
