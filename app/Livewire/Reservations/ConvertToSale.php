<?php

namespace App\Livewire\Reservations;

use App\Events\SaleCompleted;
use App\Events\StockUpdated;
use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Convert Reservation to Sale')]
class ConvertToSale extends Component
{
    use AuthorizesRequests;
    public Reservation $reservation;
    
    public string $paymentMethod = 'cash';
    public float $discount = 0;
    public float $amountTendered = 0;
    public string $notes = '';

    public bool $showConfirmModal = false;
    public ?Sale $completedSale = null;

    public function mount(Reservation $reservation): void
    {
        $this->reservation = $reservation->load(['shop', 'customer', 'items.eggCategory', 'items.inventoryReservation']);
        
        // Check if reservation is ready for pickup
        if ($this->reservation->status !== Reservation::STATUS_READY) {
            session()->flash('error', 'This reservation is not ready for pickup.');
            $this->redirect(route('reservations.index'));
        }

        $this->amountTendered = $this->total;
    }

    /**
     * Get active shift.
     */
    #[Computed]
    public function activeShift(): ?Shift
    {
        $shiftId = session('active_shift_id');
        
        if ($shiftId) {
            $shift = Shift::where('id', $shiftId)->where('status', 'open')->first();
            if ($shift) return $shift;
            session()->forget('active_shift_id');
        }

        return auth()->user()?->currentShift();
    }

    /**
     * Calculate total after discount.
     */
    #[Computed]
    public function total(): float
    {
        return max(0, $this->reservation->total - $this->discount);
    }

    /**
     * Calculate change due.
     */
    #[Computed]
    public function changeDue(): float
    {
        return max(0, $this->amountTendered - $this->total);
    }

    /**
     * Open confirmation modal.
     */
    public function openConfirmModal(): void
    {
        if (!$this->activeShift) {
            session()->flash('error', 'Please open a shift before processing sales.');
            return;
        }

        if ($this->paymentMethod === 'cash' && $this->amountTendered < $this->total) {
            session()->flash('error', 'Amount tendered is less than total.');
            return;
        }

        $this->showConfirmModal = true;
    }

    /**
     * Convert reservation to sale.
     */
    public function convertToSale(): void
    {
        $this->authorize('create-sale');

        if (!$this->activeShift) {
            session()->flash('error', 'No active shift. Please open a shift first.');
            $this->showConfirmModal = false;
            return;
        }

        try {
            DB::transaction(function () {
                // Create the sale
                $sale = Sale::create([
                    'shop_id' => $this->reservation->shop_id,
                    'staff_id' => auth()->id(),
                    'shift_id' => $this->activeShift->id,
                    'reservation_id' => $this->reservation->id,
                    'subtotal' => $this->reservation->subtotal,
                    'tax' => $this->reservation->tax,
                    'discount' => $this->discount,
                    'total' => $this->total,
                    'payment_method' => $this->paymentMethod,
                    'status' => Sale::STATUS_COMPLETED,
                    'notes' => $this->notes ?: "Converted from {$this->reservation->reservation_code}",
                ]);

                // Convert reservation items to sale items
                foreach ($this->reservation->items as $item) {
                    // Get the inventory reservation
                    $invReservation = $item->inventoryReservation;
                    
                    if ($invReservation && $invReservation->inventory) {
                        $inventory = $invReservation->inventory;
                        
                        // Create sale item
                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'batch_id' => $inventory->batch_id,
                            'egg_category_id' => $item->egg_category_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'line_total' => $item->line_total,
                        ]);

                        // Deduct from available stock (was reserved, now sold)
                        $inventory->available_stock -= $item->quantity;
                        $inventory->reserved_stock -= $item->quantity;
                        $inventory->save();

                        // Update batch
                        if ($inventory->batch) {
                            $inventory->batch->decrement('current_quantity', $item->quantity);
                        }

                        // Dispatch stock update event
                        $totalStock = Inventory::getTotalStockForCategory(
                            $this->reservation->shop_id,
                            $item->egg_category_id
                        );
                        
                        event(new StockUpdated(
                            $this->reservation->shop_id,
                            $item->egg_category_id,
                            $totalStock['available'],
                            $totalStock['reserved'],
                            'sold'
                        ));

                        // Delete the reservation link
                        $invReservation->delete();
                    }
                }

                // Mark reservation as completed
                $this->reservation->complete();

                // Fire sale completed event
                event(new SaleCompleted($sale));

                $this->completedSale = $sale;
            });

            session()->flash('success', 'Reservation converted to sale successfully!');
            $this->showConfirmModal = false;

        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
            $this->showConfirmModal = false;
        }
    }

    /**
     * Go to new sale in POS.
     */
    public function newSale(): void
    {
        $this->redirect(route('pos.index'));
    }

    /**
     * Go back to reservations list.
     */
    public function backToList(): void
    {
        $this->redirect(route('reservations.index'));
    }

    public function render()
    {
        return view('livewire.reservations.convert-to-sale');
    }
}
