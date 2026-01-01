<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Settings')]
class SettingsPage extends Component
{
    public string $activeTab = 'general';

    // General Settings
    public string $businessName = '';
    public string $businessAddress = '';
    public string $businessPhone = '';
    public string $businessEmail = '';
    public string $taxRate = '12';
    public string $currency = 'PHP';
    public string $timezone = 'Asia/Manila';

    // POS Settings
    public bool $requireShiftForSales = true;
    public bool $allowNegativeStock = false;
    public bool $printReceiptByDefault = true;
    public string $receiptFooterMessage = 'Thank you for your purchase!';
    public int $receiptPaperWidth = 80;

    // Inventory Settings
    public int $defaultLowStockThreshold = 30;
    public int $defaultBatchExpiryDays = 21;
    public bool $enableFIFO = true;
    public bool $autoExpireReservations = true;
    public int $reservationExpiryHours = 24;

    // Notification Settings
    public bool $emailLowStockAlerts = true;
    public bool $emailExpiryAlerts = true;
    public bool $emailDiscrepancyAlerts = true;
    public bool $emailDailyReport = false;

    protected array $tabs = [
        'general' => 'General',
        'pos' => 'POS & Sales',
        'inventory' => 'Inventory',
        'notifications' => 'Notifications',
    ];

    public function mount(): void
    {
        // Load settings from config/database
        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.settings.settings-page');
    }

    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function loadSettings(): void
    {
        // In a real app, load from database or config
        // For now, using defaults
        $this->businessName = config('app.name', 'EggFlow');
        $this->timezone = config('app.timezone', 'Asia/Manila');
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'businessName' => 'required|string|max:255',
            'businessAddress' => 'nullable|string|max:500',
            'businessPhone' => 'nullable|string|max:20',
            'businessEmail' => 'nullable|email|max:255',
            'taxRate' => 'required|numeric|min:0|max:100',
            'currency' => 'required|string|max:10',
            'timezone' => 'required|string',
        ]);

        // Save to database/config (implementation depends on how settings are stored)
        // For now, just show success message
        session()->flash('message', 'General settings saved successfully.');
    }

    public function savePOS(): void
    {
        $this->validate([
            'receiptFooterMessage' => 'nullable|string|max:255',
            'receiptPaperWidth' => 'required|integer|in:58,80',
        ]);

        session()->flash('message', 'POS settings saved successfully.');
    }

    public function saveInventory(): void
    {
        $this->validate([
            'defaultLowStockThreshold' => 'required|integer|min:1',
            'defaultBatchExpiryDays' => 'required|integer|min:1',
            'reservationExpiryHours' => 'required|integer|min:1',
        ]);

        session()->flash('message', 'Inventory settings saved successfully.');
    }

    public function saveNotifications(): void
    {
        session()->flash('message', 'Notification settings saved successfully.');
    }

    #[Computed]
    public function timezones(): array
    {
        return [
            'Asia/Manila' => 'Asia/Manila (PHT)',
            'Asia/Singapore' => 'Asia/Singapore (SGT)',
            'Asia/Tokyo' => 'Asia/Tokyo (JST)',
            'Asia/Shanghai' => 'Asia/Shanghai (CST)',
            'America/New_York' => 'America/New_York (EST)',
            'America/Los_Angeles' => 'America/Los_Angeles (PST)',
            'Europe/London' => 'Europe/London (GMT)',
            'UTC' => 'UTC',
        ];
    }
}
