<?php

namespace App\Livewire\Components;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public bool $isOpen = false;

    /**
     * Get recent notifications.
     */
    #[Computed]
    public function notifications()
    {
        $user = auth()->user();
        
        if (!$user) {
            return collect();
        }

        return $user->notifications()->latest()->take(10)->get();
    }

    /**
     * Get unread count.
     */
    #[Computed]
    public function unreadCount(): int
    {
        $user = auth()->user();
        
        return $user ? $user->unreadNotifications()->count() : 0;
    }

    /**
     * Toggle dropdown.
     */
    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    /**
     * Close dropdown.
     */
    public function close(): void
    {
        $this->isOpen = false;
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $id): void
    {
        $user = auth()->user();
        if (!$user) return;

        $notification = $user->notifications()->find($id);
        $notification?->markAsRead();
    }

    /**
     * Mark all as read.
     */
    public function markAllAsRead(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $user->unreadNotifications->markAsRead();
    }

    /**
     * Listen for real-time notifications.
     */
    #[On('echo-private:App.Models.User.{userId},notification')]
    public function onNewNotification(): void
    {
        // Refresh the notifications
        unset($this->notifications);
        unset($this->unreadCount);
    }

    /**
     * Get icon path for notification type.
     */
    public function getIcon(array $data): string
    {
        $type = $data['type'] ?? '';

        return match ($type) {
            'low_stock_alert' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            'batch_expired' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'delivery_dispatched', 'delivery_received' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
            'discrepancy_reported' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            'discrepancy_resolved' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'new_restock_request', 'restock_acknowledged' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            'reservation_created', 'reservation_confirmed' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'reservation_cancelled', 'reservation_expired' => 'M6 18L18 6M6 6l12 12',
            'pickup_reminder' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    /**
     * Get color class for notification type.
     */
    public function getColor(array $data): string
    {
        $type = $data['type'] ?? '';

        return match ($type) {
            'low_stock_alert', 'batch_expired' => 'text-red-500',
            'delivery_dispatched' => 'text-purple-500',
            'delivery_received', 'discrepancy_resolved', 'restock_acknowledged' => 'text-green-500',
            'discrepancy_reported' => 'text-yellow-500',
            'new_restock_request' => 'text-blue-500',
            'reservation_created', 'reservation_confirmed' => 'text-blue-500',
            'reservation_cancelled', 'reservation_expired' => 'text-gray-500',
            'pickup_reminder' => 'text-amber-500',
            default => 'text-gray-500',
        };
    }

    public function render()
    {
        return view('livewire.components.notification-dropdown');
    }
}
