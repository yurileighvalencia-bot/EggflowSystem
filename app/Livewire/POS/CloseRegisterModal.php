<?php

namespace App\Livewire\POS;

use App\Models\Shift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CloseRegisterModal extends Component
{
    use AuthorizesRequests;
    public bool $show = false;
    public ?Shift $shift = null;
    public bool $closed = false;

    /**
     * Denomination counter - Philippine Peso.
     */
    public array $denominations = [
        '1000' => 0,
        '500' => 0,
        '200' => 0,
        '100' => 0,
        '50' => 0,
        '20' => 0,
        '10' => 0,
        '5' => 0,
        '1' => 0,
        '0.25' => 0, // 25 centavos
    ];

    protected $listeners = ['openCloseRegister' => 'show'];

    /**
     * Denomination labels for display.
     */
    public array $denominationLabels = [
        '1000' => '₱1,000',
        '500' => '₱500',
        '200' => '₱200',
        '100' => '₱100',
        '50' => '₱50',
        '20' => '₱20',
        '10' => '₱10',
        '5' => '₱5',
        '1' => '₱1',
        '0.25' => '25¢',
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

        $this->shift = Shift::with(['sales', 'adjustments'])->find($shiftId);

        if (!$this->shift || !$this->shift->isOpen()) {
            session()->flash('error', 'Shift is not open.');
            return;
        }

        $this->resetDenominations();
        $this->closed = false;
        $this->show = true;
    }

    /**
     * Hide the modal.
     */
    public function hide(): void
    {
        $this->show = false;
        $this->resetDenominations();
        $this->closed = false;
    }

    /**
     * Reset denomination counts.
     */
    private function resetDenominations(): void
    {
        foreach ($this->denominations as $key => $value) {
            $this->denominations[$key] = 0;
        }
    }

    /**
     * Calculate total from denominations.
     */
    #[Computed]
    public function closingCash(): float
    {
        $total = 0;
        foreach ($this->denominations as $value => $count) {
            $total += (float) $value * (int) $count;
        }
        return $total;
    }

    /**
     * Increment denomination count.
     */
    public function increment(string $denomination): void
    {
        $this->denominations[$denomination]++;
    }

    /**
     * Decrement denomination count.
     */
    public function decrement(string $denomination): void
    {
        if ($this->denominations[$denomination] > 0) {
            $this->denominations[$denomination]--;
        }
    }

    /**
     * Get shift summary for staff.
     */
    #[Computed]
    public function shiftSummary(): array
    {
        if (!$this->shift) {
            return [];
        }

        return [
            'opened_at' => $this->shift->opened_at->format('M d, Y g:i A'),
            'opening_cash' => $this->shift->opening_cash,
            'total_sales' => $this->shift->sales()->count(),
            'cash_in_count' => $this->shift->adjustments()->where('type', 'cash_in')->count(),
            'cash_out_count' => $this->shift->adjustments()->where('type', 'cash_out')->count(),
        ];
    }

    /**
     * Close the shift (blind close).
     * Staff only sees "Shift Closed" - discrepancy visible to managers only.
     */
    public function closeShift(): void
    {
        $this->authorize('create-sale');

        if (!$this->shift || !$this->shift->isOpen()) {
            session()->flash('error', 'Shift is not open.');
            $this->hide();
            return;
        }

        // Calculate expected cash using the formula:
        // expected = opening_cash + SUM(cash sales) + SUM(cash_in) - SUM(cash_out)
        $expectedCash = $this->shift->calculateExpectedCash();

        // Close the shift
        $this->shift->close($this->closingCash);

        // Clear session
        session()->forget('active_shift_id');

        // Mark as closed for display
        $this->closed = true;

        // Dispatch event for parent component
        $this->dispatch('shiftClosed');
    }

    /**
     * Check if current user can see discrepancy (managers only).
     */
    #[Computed]
    public function canSeeDiscrepancy(): bool
    {
        return auth()->user()->hasRole('manager') || auth()->user()->hasRole('admin');
    }

    public function render()
    {
        return view('livewire.pos.close-register-modal');
    }
}
