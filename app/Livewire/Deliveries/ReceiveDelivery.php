<?php

namespace App\Livewire\Deliveries;

use App\Events\DeliveryReceived;
use App\Models\Delivery;
use App\Models\DeliveryDiscrepancy;
use App\Models\Inventory;
use App\Models\WastageLog;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Receive Delivery')]
class ReceiveDelivery extends Component
{
    public Delivery $delivery;

    /**
     * Received quantities: [item_id => ['received' => x, 'rejected' => y, 'reason' => '']]
     */
    public array $receiveData = [];

    public string $notes = '';
    public bool $showConfirmation = false;
    public bool $hasDiscrepancy = false;

    public function mount(Delivery $delivery): void
    {
        $this->delivery = $delivery->load(['items.eggCategory', 'items.batch', 'shop']);

        // Initialize receive data from delivery items
        foreach ($this->delivery->items as $item) {
            $this->receiveData[$item->id] = [
                'item_id' => $item->id,
                'category_name' => $item->eggCategory?->name,
                'dispatched' => $item->qty_dispatched,
                'received' => $item->qty_dispatched, // Default to full receive
                'rejected' => 0,
                'reason' => '',
            ];
        }
    }

    /**
     * Check if there are any discrepancies.
     */
    #[Computed]
    public function hasAnyDiscrepancy(): bool
    {
        foreach ($this->receiveData as $data) {
            if ($data['received'] != $data['dispatched'] || $data['rejected'] > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get total received.
     */
    #[Computed]
    public function totalReceived(): int
    {
        return collect($this->receiveData)->sum('received');
    }

    /**
     * Get total rejected.
     */
    #[Computed]
    public function totalRejected(): int
    {
        return collect($this->receiveData)->sum('rejected');
    }

    /**
     * Get total dispatched.
     */
    #[Computed]
    public function totalDispatched(): int
    {
        return $this->delivery->items->sum('qty_dispatched');
    }

    /**
     * Update received quantity.
     */
    public function updateReceived(int $itemId, int $received): void
    {
        if (!isset($this->receiveData[$itemId])) {
            return;
        }

        $dispatched = $this->receiveData[$itemId]['dispatched'];
        $received = max(0, min($received, $dispatched));

        $this->receiveData[$itemId]['received'] = $received;
        $this->receiveData[$itemId]['rejected'] = $dispatched - $received;
    }

    /**
     * Mark all as received (one-click confirm).
     */
    public function confirmAll(): void
    {
        foreach ($this->receiveData as $itemId => $data) {
            $this->receiveData[$itemId]['received'] = $data['dispatched'];
            $this->receiveData[$itemId]['rejected'] = 0;
            $this->receiveData[$itemId]['reason'] = '';
        }

        $this->showConfirmation = true;
    }

    /**
     * Open discrepancy form.
     */
    public function openDiscrepancyForm(): void
    {
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
     * Submit receive.
     */
    public function submit(): void
    {
        // Validate rejection reasons if there are rejected items
        foreach ($this->receiveData as $data) {
            if ($data['rejected'] > 0 && empty($data['reason'])) {
                session()->flash('error', 'Please provide a reason for all rejected items.');
                return;
            }
        }

        try {
            DB::transaction(function () {
                $user = auth()->user();
                $hasDiscrepancy = false;

                foreach ($this->delivery->items as $item) {
                    $data = $this->receiveData[$item->id];

                    // Update delivery item
                    $item->update([
                        'qty_received' => $data['received'],
                        'qty_rejected' => $data['rejected'],
                        'rejection_reason' => $data['reason'] ?: null,
                    ]);

                    // Add to shop inventory
                    if ($data['received'] > 0) {
                        $inventory = Inventory::firstOrCreate(
                            [
                                'shop_id' => $this->delivery->shop_id,
                                'batch_id' => $item->batch_id,
                                'egg_category_id' => $item->egg_category_id,
                            ],
                            [
                                'available_stock' => 0,
                                'reserved_stock' => 0,
                                'unit_price' => $item->unit_price,
                            ]
                        );

                        $inventory->increment('available_stock', $data['received']);
                    }

                    // Log wastage for rejected items
                    if ($data['rejected'] > 0) {
                        $hasDiscrepancy = true;

                        WastageLog::createFromDeliveryRejection($item, $user->id, $this->delivery->shop_id);

                        // Create discrepancy record
                        DeliveryDiscrepancy::create([
                            'delivery_id' => $this->delivery->id,
                            'delivery_item_id' => $item->id,
                            'reported_by' => $user->id,
                            'qty_expected' => $data['dispatched'],
                            'qty_actual' => $data['received'],
                            'reason' => $data['reason'],
                            'status' => 'pending',
                            'reported_at' => now(),
                        ]);
                    }
                }

                // Update delivery status
                $status = Delivery::STATUS_RECEIVED;
                if ($hasDiscrepancy) {
                    if ($this->totalReceived > 0) {
                        $status = Delivery::STATUS_PARTIAL;
                    } else {
                        $status = Delivery::STATUS_DISPUTED;
                    }
                }

                $this->delivery->update([
                    'status' => $status,
                    'received_by' => $user->id,
                    'received_at' => now(),
                    'notes' => $this->delivery->notes . ($this->notes ? "\n\nReceive notes: " . $this->notes : ''),
                ]);

                // Dispatch event
                event(new DeliveryReceived($this->delivery));

                session()->flash('success', 'Delivery received successfully.');
                $this->redirect(route('deliveries.index'));
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to receive delivery: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.deliveries.receive-delivery');
    }
}
