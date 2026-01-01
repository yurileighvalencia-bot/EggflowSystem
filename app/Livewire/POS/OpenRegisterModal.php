<?php

namespace App\Livewire\POS;

use App\Models\Shift;
use App\Models\Shop;
use Livewire\Attributes\On;
use Livewire\Component;

class OpenRegisterModal extends Component
{
    public bool $show = false;
    public ?int $shopId = null;

    // Denomination counter
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

    public string $notes = '';

    protected $listeners = ['openRegister' => 'show'];

    public function mount(?int $shopId = null): void
    {
        $user = auth()->user();
        $this->shopId = $shopId ?? $user?->shop_id ?? Shop::first()?->id;
    }

    /**
     * Show the modal.
     */
    public function show(): void
    {
        $this->resetDenominations();
        $this->show = true;
    }

    /**
     * Hide the modal.
     */
    public function hide(): void
    {
        $this->show = false;
        $this->resetDenominations();
    }

    /**
     * Reset denominations.
     */
    protected function resetDenominations(): void
    {
        foreach ($this->denominations as $key => $value) {
            $this->denominations[$key] = 0;
        }
        $this->notes = '';
    }

    /**
     * Calculate total from denominations.
     */
    public function getOpeningCashProperty(): float
    {
        $total = 0;
        foreach ($this->denominations as $denomination => $count) {
            $total += (float) $denomination * (int) $count;
        }
        return $total;
    }

    /**
     * Open the register/shift.
     */
    public function openShift(): void
    {
        $user = auth()->user();

        // Check if user already has an open shift
        if ($user->hasOpenShift()) {
            session()->flash('error', 'You already have an open shift.');
            $this->hide();
            return;
        }

        // Create the shift
        $shift = Shift::create([
            'user_id' => $user->id,
            'shop_id' => $this->shopId,
            'opening_cash' => $this->openingCash,
            'status' => Shift::STATUS_OPEN,
            'opened_at' => now(),
            'notes' => $this->notes ?: null,
        ]);

        // Store shift ID in session
        session()->put('active_shift_id', $shift->id);

        // Dispatch event for parent component
        $this->dispatch('shiftOpened', shiftId: $shift->id);

        session()->flash('success', 'Register opened successfully with ₱' . number_format($this->openingCash, 2));
        $this->hide();
    }

    public function render()
    {
        return view('livewire.pos.open-register-modal');
    }
}
