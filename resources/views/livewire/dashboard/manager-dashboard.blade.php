<div>
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manager Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400">System-wide overview and analytics</p>
    </div>

    {{-- Overview Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Farms</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total_farms'] }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Shops</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total_shops'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total_users'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Categories</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total_categories'] }}</p>
                </div>
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-full">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Inventory Health & Today's Metrics --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Inventory Health --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Inventory Health</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Batches</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $this->inventoryHealth['active_batches'] }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Stock</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($this->inventoryHealth['total_stock']) }}</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
                    <p class="text-sm text-amber-600 dark:text-amber-400">Low Stock Alerts</p>
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $this->inventoryHealth['low_stock_alerts'] }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <p class="text-sm text-red-600 dark:text-red-400">Expiring Soon</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ $this->inventoryHealth['expiring_soon'] }}</p>
                </div>
            </div>
        </div>

        {{-- Today's Metrics --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Today's Performance</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sales Today</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $this->todayMetrics['sales_count'] }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Revenue</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($this->todayMetrics['revenue'], 2) }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Eggs Sold</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($this->todayMetrics['eggs_sold']) }}</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pending Pickups</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $this->todayMetrics['pending_pickups'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Supply Chain & Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Supply Chain Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Supply Chain</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Pending Restock Requests</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400">{{ $this->supplyChainStatus['pending_restock_requests'] }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Acknowledged Requests</span>
                    <span class="font-bold text-blue-600 dark:text-blue-400">{{ $this->supplyChainStatus['acknowledged_requests'] }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Deliveries In Transit</span>
                    <span class="font-bold text-purple-600 dark:text-purple-400">{{ $this->supplyChainStatus['deliveries_in_transit'] }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Deliveries Today</span>
                    <span class="font-bold text-green-600 dark:text-green-400">{{ $this->supplyChainStatus['deliveries_today'] }}</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('inventory.stock') }}" class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors text-center">
                    <svg class="w-8 h-8 mx-auto mb-2 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Stock Overview</span>
                </a>
                <a href="{{ route('inventory.low-stock') }}" class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors text-center">
                    <svg class="w-8 h-8 mx-auto mb-2 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Low Stock Alerts</span>
                </a>
                <a href="{{ route('inventory.expiring') }}" class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors text-center">
                    <svg class="w-8 h-8 mx-auto mb-2 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Expiring Batches</span>
                </a>
                <a href="{{ route('pos.history') }}" class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors text-center">
                    <svg class="w-8 h-8 mx-auto mb-2 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sales Report</span>
                </a>
            </div>
        </div>
    </div>
</div>
