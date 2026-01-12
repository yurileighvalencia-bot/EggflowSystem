<?php

namespace App\Livewire\Reservations;

use App\Models\Reservation;
use App\Models\Shop;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Reservations')]
class ReservationList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $shopId = null;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public bool $showCancelModal = false;
    public ?int $cancellingId = null;
    public string $cancellationReason = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id ?? Shop::first()?->id;
    }

    /**
     * Get shops for filter dropdown.
     */
    #[Computed]
    public function shops(): Collection
    {
        return Shop::orderBy('name')->get();
    }

    /**
     * Get reservation stats.
     */
    #[Computed]
    public function stats(): array
    {
        $baseQuery = Reservation::query()
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId));

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', Reservation::STATUS_PENDING)->count(),
            'confirmed' => (clone $baseQuery)->where('status', Reservation::STATUS_CONFIRMED)->count(),
            'ready' => (clone $baseQuery)->where('status', Reservation::STATUS_READY)->count(),
            'today_pickup' => (clone $baseQuery)
                ->where('pickup_date', today())
                ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_READY])
                ->count(),
        ];
    }

    /**
     * Get paginated reservations.
     */
    #[Computed]
    public function reservations()
    {
        return Reservation::query()
            ->with(['shop', 'customer', 'items.eggCategory'])
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn($q) => $q->whereDate('pickup_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('pickup_date', '<=', $this->dateTo))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('reservation_code', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    /**
     * Confirm a pending reservation.
     */
    public function confirmReservation(int $id): void
    {
        $this->authorize('manage-reservations');

        $reservation = Reservation::findOrFail($id);
        
        if ($reservation->status !== Reservation::STATUS_PENDING) {
            session()->flash('error', 'Only pending reservations can be confirmed.');
            return;
        }

        $reservation->confirm();
        session()->flash('success', "Reservation {$reservation->reservation_code} confirmed.");
        unset($this->reservations);
        unset($this->stats);
    }

    /**
     * Mark a reservation as ready for pickup.
     */
    public function markReady(int $id): void
    {
        $this->authorize('manage-reservations');

        $reservation = Reservation::findOrFail($id);
        
        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            session()->flash('error', 'Only confirmed reservations can be marked ready.');
            return;
        }

        $reservation->markReady();
        session()->flash('success', "Reservation {$reservation->reservation_code} is ready for pickup.");
        unset($this->reservations);
        unset($this->stats);
    }

    /**
     * Open cancel modal.
     */
    public function openCancelModal(int $id): void
    {
        $this->cancellingId = $id;
        $this->cancellationReason = '';
        $this->showCancelModal = true;
    }

    /**
     * Cancel a reservation.
     */
    public function cancelReservation(): void
    {
        $this->authorize('manage-reservations');

        if (!$this->cancellingId) {
            return;
        }

        $reservation = Reservation::findOrFail($this->cancellingId);
        
        if (!$reservation->isActive()) {
            session()->flash('error', 'This reservation cannot be cancelled.');
            $this->closeCancelModal();
            return;
        }

        // Release reserved stock back to inventory
        foreach ($reservation->items as $item) {
            if ($item->inventoryReservation) {
                $inventory = $item->inventoryReservation->inventory;
                if ($inventory) {
                    $inventory->reserved_stock -= $item->quantity;
                    $inventory->save();
                }
                $item->inventoryReservation->delete();
            }
        }

        $reservation->cancel(auth()->id(), $this->cancellationReason);
        
        event(new \App\Events\ReservationCancelled($reservation, $this->cancellationReason));
        
        session()->flash('success', "Reservation {$reservation->reservation_code} has been cancelled.");
        $this->closeCancelModal();
        unset($this->reservations);
        unset($this->stats);
    }

    /**
     * Close cancel modal.
     */
    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->cancellingId = null;
        $this->cancellationReason = '';
    }

    /**
     * Get status badge color.
     */
    public function getStatusColor(string $status): string
    {
        return match($status) {
            Reservation::STATUS_PENDING => 'yellow',
            Reservation::STATUS_CONFIRMED => 'blue',
            Reservation::STATUS_READY => 'green',
            Reservation::STATUS_COMPLETED => 'gray',
            Reservation::STATUS_CANCELLED => 'red',
            Reservation::STATUS_EXPIRED => 'orange',
            default => 'gray',
        };
    }

    /**
     * Clear filters.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.reservations.reservation-list');
    }
}
