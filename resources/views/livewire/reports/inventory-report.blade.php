<div class="space-y-6">
    {{-- Header --}}
    <x-page-header 
        title="Inventory Report" 
        description="View current stock levels, low stock alerts, and expiring batches"
    >
        <x-slot:actions>
            <button 
                wire:click="exportPdf"
                wire:loading.attr="disabled"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-lg flex items-center gap-2 transition-colors"
            >
                <span wire:loading.remove wire:target="exportPdf">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <x-loading-spinner wire:loading wire:target="exportPdf" size="sm" color="white" />
                Export PDF
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Shop Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shop</label>
                <select 
                    wire:model.live="shopId"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All Shops</option>
                    @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- View Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Report View</label>
                <select 
                    wire:model.live="view"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="snapshot">Stock Snapshot</option>
                    <option value="low-stock">Low Stock Alerts</option>
                    <option value="expiring">Expiring Soon</option>
                    <option value="valuation">Stock Valuation</option>
                </select>
            </div>

            {{-- Expiring Days (only show for expiring view) --}}
            @if($view === 'expiring')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expiring Within</label>
                    <select 
                        wire:model.live="expiringDays"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option value="3">3 Days</option>
                        <option value="7">7 Days</option>
                        <option value="14">14 Days</option>
                        <option value="30">30 Days</option>
                    </select>
                </div>
            @endif
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-700 p-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->snapshot['total_available']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Available Stock</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700 p-4">
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->snapshot['total_reserved']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Reserved Stock</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-700 p-4">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->lowStockItems->count() }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Low Stock Alerts</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-orange-200 dark:border-orange-700 p-4">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $this->expiringItems->count() }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Expiring Soon</div>
        </div>
    </div>

    {{-- Stock Snapshot View --}}
    @if($view === 'snapshot')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Stock by Category</h2>
            
            @if($this->snapshot['categories']->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="mt-2">No inventory data found</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Category</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Available</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Reserved</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Total</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Batches</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->snapshot['categories'] as $category)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $category['category_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">
                                        {{ number_format($category['available_stock']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">
                                        {{ number_format($category['reserved_stock']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($category['total_stock']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                        {{ $category['batch_count'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                            <tr class="font-bold">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">Total</td>
                                <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($this->snapshot['total_available']) }}</td>
                                <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">{{ number_format($this->snapshot['total_reserved']) }}</td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($this->snapshot['total_stock']) }}</td>
                                <td class="px-4 py-3 text-right"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- Low Stock View --}}
    @if($view === 'low-stock')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Low Stock Alerts</h2>
            
            @if($this->lowStockItems->isEmpty())
                <x-empty-state 
                    icon="chart"
                    title="All stock levels are healthy!"
                    description="No categories are currently below their low stock threshold."
                />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Category</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Current Stock</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Threshold</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Shortage</th>
                                <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->lowStockItems as $item)
                                @php
                                    $shortage = max(0, $item->low_stock_threshold - ($item->available_stock ?? 0));
                                    $isCritical = ($item->available_stock ?? 0) <= ($item->low_stock_threshold * 0.5);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $item->name }}
                                    </td>
                                    <td class="px-4 py-3 text-right {{ $isCritical ? 'text-red-600 dark:text-red-400 font-bold' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ number_format($item->available_stock ?? 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($item->low_stock_threshold) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">
                                        -{{ number_format($shortage) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($isCritical)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                Critical
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                Low
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- Expiring Soon View --}}
    @if($view === 'expiring')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Batches Expiring Within {{ $expiringDays }} Days
            </h2>
            
            @if($this->expiringItems->isEmpty())
                <x-empty-state 
                    icon="calendar"
                    title="No batches expiring soon!"
                    description="All batches are well within their expiry dates."
                />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Batch</th>
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Category</th>
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Shop</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Available</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Reserved</th>
                                <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-medium">Expires</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->expiringItems as $item)
                                @php
                                    $isUrgent = $item['days_left'] <= 3;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $isUrgent ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                    <td class="px-4 py-3 font-mono text-sm text-gray-900 dark:text-white">
                                        {{ $item['batch_code'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">
                                        {{ $item['category_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {{ $item['shop_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                        {{ number_format($item['available_stock']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">
                                        {{ number_format($item['reserved_stock']) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $isUrgent ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' }}">
                                            {{ $item['days_left'] }} day{{ $item['days_left'] !== 1 ? 's' : '' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- Stock Valuation View --}}
    @if($view === 'valuation')
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Stock Valuation</h2>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Stock Value</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($this->valuation['total_value'], 2) }}</p>
                </div>
            </div>
            
            @if($this->valuation['categories']->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No inventory data for valuation</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Category</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Quantity</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Avg. Price</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Value</th>
                                <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">% of Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->valuation['categories'] as $category)
                                @php
                                    $percentage = $this->valuation['total_value'] > 0 
                                        ? ($category['value'] / $this->valuation['total_value']) * 100 
                                        : 0;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $category['category_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($category['quantity']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                                        ₱{{ number_format($category['avg_price'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-green-600 dark:text-green-400">
                                        ₱{{ number_format($category['value'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                        {{ number_format($percentage, 1) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200 dark:border-gray-600">
                            <tr class="font-bold">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">Total</td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ number_format($this->valuation['total_quantity']) }}</td>
                                <td class="px-4 py-3 text-right"></td>
                                <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">₱{{ number_format($this->valuation['total_value'], 2) }}</td>
                                <td class="px-4 py-3 text-right">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
