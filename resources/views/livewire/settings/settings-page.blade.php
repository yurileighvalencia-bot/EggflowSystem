<div class="space-y-6">
    {{-- Header --}}
    <x-page-header 
        title="Settings" 
        description="Configure system preferences"
    />

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <x-alert type="success" dismissible>
            {{ session('message') }}
        </x-alert>
    @endif

    <div class="flex flex-col md:flex-row gap-6">
        {{-- Tabs Sidebar --}}
        <div class="w-full md:w-48 flex-shrink-0">
            <nav class="space-y-1">
                @foreach($this->getTabs() as $key => $label)
                    <button wire:click="setTab('{{ $key }}')"
                        class="w-full text-left px-4 py-2 rounded-lg text-sm font-medium transition-colors
                            {{ $activeTab === $key 
                                ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' 
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Settings Content --}}
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            {{-- General Settings --}}
            @if($activeTab === 'general')
                <form wire:submit="saveGeneral" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">General Settings</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Business Name</label>
                            <input type="text" wire:model="businessName"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Business Address</label>
                            <textarea wire:model="businessAddress" rows="2"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                            <input type="text" wire:model="businessPhone"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" wire:model="businessEmail"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tax Rate (%)</label>
                            <input type="number" wire:model="taxRate" step="0.01" min="0" max="100"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                            <select wire:model="currency"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="PHP">PHP - Philippine Peso</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="SGD">SGD - Singapore Dollar</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                            <select wire:model="timezone"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach($this->timezones as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">
                            Save Changes
                        </button>
                    </div>
                </form>
            @endif

            {{-- POS Settings --}}
            @if($activeTab === 'pos')
                <form wire:submit="savePOS" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">POS & Sales Settings</h3>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="requireShiftForSales"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Require Shift for Sales</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Staff must open a cash register shift before making sales</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="allowNegativeStock"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Allow Negative Stock</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Allow sales even when stock is insufficient</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="printReceiptByDefault"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Print Receipt by Default</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Automatically open print dialog after sale</p>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Receipt Paper Width</label>
                            <select wire:model="receiptPaperWidth"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="58">58mm (Small)</option>
                                <option value="80">80mm (Standard)</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Receipt Footer Message</label>
                            <input type="text" wire:model="receiptFooterMessage"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">
                            Save Changes
                        </button>
                    </div>
                </form>
            @endif

            {{-- Inventory Settings --}}
            @if($activeTab === 'inventory')
                <form wire:submit="saveInventory" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Inventory Settings</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Low Stock Threshold</label>
                            <input type="number" wire:model="defaultLowStockThreshold" min="1"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">For new categories</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Batch Expiry (Days)</label>
                            <input type="number" wire:model="defaultBatchExpiryDays" min="1"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Eggs shelf life from collection</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reservation Expiry (Hours)</label>
                            <input type="number" wire:model="reservationExpiryHours" min="1"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">After pickup time passes</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="enableFIFO"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Enable FIFO</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Sell oldest batches first (First-In, First-Out)</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="autoExpireReservations"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Auto-Expire Reservations</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Automatically expire unclaimed reservations</p>
                            </div>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">
                            Save Changes
                        </button>
                    </div>
                </form>
            @endif

            {{-- Notification Settings --}}
            @if($activeTab === 'notifications')
                <form wire:submit="saveNotifications" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notification Settings</h3>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="emailLowStockAlerts"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Low Stock Alerts</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Email when stock falls below threshold</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="emailExpiryAlerts"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Expiry Alerts</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Email when batches are expiring soon</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="emailDiscrepancyAlerts"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Discrepancy Alerts</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Email when delivery discrepancies are reported</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer">
                            <input type="checkbox" wire:model="emailDailyReport"
                                class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Daily Summary Report</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Receive daily email with sales and inventory summary</p>
                            </div>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">
                            Save Changes
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
