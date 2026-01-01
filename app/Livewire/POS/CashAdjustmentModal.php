<?php

namespace App\Livewire\POS;

use App\Models\Shift;
use App\Models\ShiftAdjustment;
use Livewire\Attributes\On;
use Livewire\Component;

class CashAdjustmentModal extends Component
{
    public bool $show = false;
    public ?Shift $shift = null;

    public string $type = 'cash_in';
    public float $amount = 0;
    public string $reason = '';

    protected $listeners = ['openCashAdjustment' => 'show'];

    protected $rules = [
        'type' => 'required|in:cash_in,cash_out',
        'amount' => 'required|numeric|min:0.01',
        'reason' => 'required|string|min:3|max:255',
    ];

    /**
     * Show the modal.
     */
    public function show(?int $shiftId = null): void
    {
        $shiftId = $shiftId ?? session('active_shift_id');
        
        if (!$shiftId) {
            session()->flash('error', 'No active shift found.');
            return;
        }

        $this->shift = Shift::find($shiftId);

        if (!$this->shift || !$this->shift->isOpen()) {
            session()->flash('error', 'Shift is not open.');
            return;
        }

        $this->reset(['type', 'amount', 'reason']);
        $this->type = 'cash_in';
        $this->amount = 0;
        $this->show = true;
    }

    /**
     * Hide the modal.
     */
    public function hide(): void
    {
        $this->show = false;
        $this->reset(['type', 'amount', 'reason']);
    }

    /**
     * Get adjustment history for current shift.
     */
    public function getAdjustmentsProperty()
    {
        if (!$this->shift) {
            return collect();
        }

        return $this->shift->adjustments()
            ->with('user')
            ->orderByDesc('adjusted_at')
            ->get();
    }

    /**
     * Record the adjustment.
     */
    public function recordAdjustment(): void
    {
        $this->validate();

        if (!$this->shift || !$this->shift->isOpen()) {
            session()->flash('error', 'Shift is not open.');
            $this->hide();
            return;
        }

        ShiftAdjustment::create([
            'shift_id' => $this->shift->id,
            'user_id' => auth()->id(),
            'type' => $this->type,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'adjusted_at' => now(),
        ]);

        $typeLabel = $this->type === 'cash_in' ? 'Cash In' : 'Cash Out';
        session()->flash('success', "{$typeLabel} of ₱" . number_format($this->amount, 2) . " recorded.");

        // Dispatch event for parent component
        $this->dispatch('adjustmentRecorded');

        $this->reset(['amount', 'reason']);
    }

    public function render()
    {
        return view('livewire.pos.cash-adjustment-modal');
    }
}
