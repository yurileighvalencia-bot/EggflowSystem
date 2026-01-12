<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Notification Preferences')]
class NotificationPreferences extends Component
{
    public array $preferences = [];

    protected array $notificationTypes = [
        'low_stock_alert' => [
            'label' => 'Low Stock Alerts',
            'description' => 'Get notified when stock levels fall below threshold',
            'icon' => 'exclamation-triangle',
        ],
        'batch_expired' => [
            'label' => 'Batch Expiration',
            'description' => 'Notifications about expired or expiring batches',
            'icon' => 'clock',
        ],
        'delivery_dispatched' => [
            'label' => 'Delivery Dispatched',
            'description' => 'When a delivery leaves the farm',
            'icon' => 'truck',
        ],
        'delivery_received' => [
            'label' => 'Delivery Received',
            'description' => 'When a delivery arrives at the shop',
            'icon' => 'check-circle',
        ],
        'discrepancy_reported' => [
            'label' => 'Discrepancy Reported',
            'description' => 'When someone reports a delivery discrepancy',
            'icon' => 'exclamation',
        ],
        'discrepancy_resolved' => [
            'label' => 'Discrepancy Resolved',
            'description' => 'When a discrepancy investigation is completed',
            'icon' => 'check',
        ],
        'new_restock_request' => [
            'label' => 'Restock Requests',
            'description' => 'When a shop requests more inventory',
            'icon' => 'refresh',
        ],
        'restock_acknowledged' => [
            'label' => 'Restock Acknowledged',
            'description' => 'When farm acknowledges a restock request',
            'icon' => 'thumb-up',
        ],
        'reservation_created' => [
            'label' => 'Reservation Created',
            'description' => 'When a new reservation is placed',
            'icon' => 'calendar',
        ],
        'reservation_confirmed' => [
            'label' => 'Reservation Confirmed',
            'description' => 'When a reservation is confirmed',
            'icon' => 'calendar-check',
        ],
        'reservation_cancelled' => [
            'label' => 'Reservation Cancelled',
            'description' => 'When a reservation is cancelled',
            'icon' => 'calendar-x',
        ],
        'reservation_expired' => [
            'label' => 'Reservation Expired',
            'description' => 'When a reservation expires',
            'icon' => 'calendar-exclamation',
        ],
        'pickup_reminder' => [
            'label' => 'Pickup Reminders',
            'description' => 'Reminders for pending pickups',
            'icon' => 'bell',
        ],
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $this->preferences = $user->notification_preferences ?? User::defaultNotificationPreferences();
    }

    public function togglePreference(string $channel, string $type): void
    {
        $this->preferences[$channel][$type] = !($this->preferences[$channel][$type] ?? true);
    }

    public function enableAll(string $channel): void
    {
        foreach ($this->notificationTypes as $type => $config) {
            $this->preferences[$channel][$type] = true;
        }
    }

    public function disableAll(string $channel): void
    {
        foreach ($this->notificationTypes as $type => $config) {
            $this->preferences[$channel][$type] = false;
        }
    }

    public function save(): void
    {
        $user = auth()->user();
        $user->update(['notification_preferences' => $this->preferences]);
        
        session()->flash('message', 'Notification preferences saved successfully!');
    }

    public function getNotificationTypes(): array
    {
        return $this->notificationTypes;
    }

    public function render()
    {
        return view('livewire.settings.notification-preferences');
    }
}
