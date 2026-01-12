<?php

namespace App\Livewire\Dashboard;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Notification Center')]
class NotificationCenter extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $type = '';

    public bool $showDetailModal = false;
    public ?DatabaseNotification $selectedNotification = null;

    protected array $filterOptions = [
        'all' => 'All',
        'unread' => 'Unread',
        'read' => 'Read',
    ];

    protected array $typeLabels = [
        'low_stock_alert' => 'Low Stock',
        'batch_expired' => 'Batch Expired',
        'delivery_dispatched' => 'Delivery Dispatched',
        'delivery_received' => 'Delivery Received',
        'discrepancy_reported' => 'Discrepancy Reported',
        'discrepancy_resolved' => 'Discrepancy Resolved',
        'new_restock_request' => 'Restock Request',
        'restock_acknowledged' => 'Restock Acknowledged',
        'reservation_created' => 'Reservation Created',
        'reservation_confirmed' => 'Reservation Confirmed',
        'reservation_cancelled' => 'Reservation Cancelled',
        'reservation_expired' => 'Reservation Expired',
        'pickup_reminder' => 'Pickup Reminder',
    ];

    public function render()
    {
        return view('livewire.dashboard.notification-center');
    }

    #[Computed]
    public function notifications()
    {
        $user = auth()->user();
        
        if (!$user) {
            return collect();
        }

        $query = $user->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($this->type) {
            $query->where('data->type', $this->type);
        }

        return $query->orderByDesc('created_at')->paginate(15);
    }

    #[Computed]
    public function stats(): array
    {
        $user = auth()->user();

        if (!$user) {
            return ['total' => 0, 'unread' => 0, 'today' => 0];
        }

        return [
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'today' => $user->notifications()->whereDate('created_at', today())->count(),
        ];
    }

    #[Computed]
    public function notificationTypes(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        $types = $user->notifications()
            ->distinct()
            ->pluck('data')
            ->map(fn ($data) => $data['type'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return array_intersect_key($this->typeLabels, array_flip($types));
    }

    public function getFilterOptions(): array
    {
        return $this->filterOptions;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function viewDetails(string $id): void
    {
        $user = auth()->user();
        if (!$user) return;

        $this->selectedNotification = $user->notifications()->find($id);
        
        if ($this->selectedNotification && !$this->selectedNotification->read_at) {
            $this->selectedNotification->markAsRead();
        }
        
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedNotification = null;
    }

    public function markAsRead(string $id): void
    {
        $user = auth()->user();
        if (!$user) return;

        $notification = $user->notifications()->find($id);
        $notification?->markAsRead();
    }

    public function markAsUnread(string $id): void
    {
        $user = auth()->user();
        if (!$user) return;

        $notification = $user->notifications()->find($id);
        if ($notification) {
            $notification->update(['read_at' => null]);
        }
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $user->unreadNotifications->markAsRead();
        session()->flash('message', 'All notifications marked as read.');
    }

    public function deleteNotification(string $id): void
    {
        $user = auth()->user();
        if (!$user) return;

        $user->notifications()->where('id', $id)->delete();
        session()->flash('message', 'Notification deleted.');
    }

    public function deleteAllRead(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $count = $user->readNotifications()->delete();
        session()->flash('message', "{$count} read notifications deleted.");
    }

    public function getIcon(array $data): string
    {
        $type = $data['type'] ?? '';

        return match ($type) {
            'low_stock_alert' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
            'batch_expired' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'delivery_dispatched', 'delivery_received' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
            'discrepancy_reported', 'discrepancy_resolved' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'new_restock_request', 'restock_acknowledged' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
            'reservation_created', 'reservation_confirmed', 'reservation_cancelled', 'reservation_expired' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            'pickup_reminder' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
            default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        };
    }

    public function getColor(array $data): string
    {
        $type = $data['type'] ?? '';

        return match ($type) {
            'low_stock_alert', 'batch_expired' => 'red',
            'delivery_dispatched' => 'purple',
            'delivery_received' => 'green',
            'discrepancy_reported' => 'yellow',
            'discrepancy_resolved' => 'green',
            'new_restock_request' => 'blue',
            'restock_acknowledged' => 'green',
            'reservation_created', 'reservation_confirmed' => 'blue',
            'reservation_cancelled', 'reservation_expired' => 'gray',
            'pickup_reminder' => 'amber',
            default => 'gray',
        };
    }

    public function getTypeLabel(string $type): string
    {
        return $this->typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
