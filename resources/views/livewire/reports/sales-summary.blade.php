<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales Summary</h1>
            <p class="text-gray-600 dark:text-gray-400">View sales performance by date, category, and payment method</p>
        </div>
        <button 
            wire:click="exportPdf"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center gap-2 transition-colors"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export PDF
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Shop --}}
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

            {{-- Date From --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                <input 
                    type="date" 
                    wire:model.live="dateFrom"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                <input 
                    type="date" 
                    wire:model.live="dateTo"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
            </div>

            {{-- Clear --}}
            <div class="flex items-end">
                <button 
                    wire:click="clearFilters"
                    class="w-full px-3 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-700 p-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($this->summary['total_sales'], 2) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Sales</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700 p-4">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->summary['total_transactions']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Transactions</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700 p-4">
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">₱{{ number_format($this->summary['average_sale'], 2) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Avg. Sale</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-purple-200 dark:border-purple-700 p-4">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">₱{{ number_format($this->summary['total_discount'], 2) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Discounts</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">₱{{ number_format($this->summary['total_tax'], 2) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Tax Collected</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Sales by Date --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sales by Date</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-3 py-2 text-right text-gray-500 dark:text-gray-400">Trans.</th>
                            <th class="px-3 py-2 text-right text-gray-500 dark:text-gray-400">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($this->salesByDate as $row)
                            <tr>
                                <td class="px-3 py-2 text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($row->date)->format('M d, Y') }}</td>
                                <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">{{ $row->count }}</td>
                                <td class="px-3 py-2 text-right font-medium text-green-600 dark:text-green-400">₱{{ number_format($row->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-gray-500">No sales data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sales by Category --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sales by Category</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-400">Category</th>
                            <th class="px-3 py-2 text-right text-gray-500 dark:text-gray-400">Qty Sold</th>
                            <th class="px-3 py-2 text-right text-gray-500 dark:text-gray-400">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($this->salesByCategory as $row)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="text-gray-900 dark:text-white">{{ $row->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $row->code }}</div>
                                </td>
                                <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($row->quantity) }}</td>
                                <td class="px-3 py-2 text-right font-medium text-green-600 dark:text-green-400">₱{{ number_format($row->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-gray-500">No sales data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Sales by Payment Method --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sales by Payment Method</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @forelse($this->salesByPayment as $row)
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="text-2xl mb-1">
                        @switch($row->payment_method)
                            @case('cash') 💵 @break
                            @case('card') 💳 @break
                            @case('transfer') 🏦 @break
                            @default 📝
                        @endswitch
                    </div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ ucfirst($row->payment_method) }}</div>
                    <div class="text-lg font-bold text-green-600 dark:text-green-400">₱{{ number_format($row->total, 2) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row->count }} transactions</div>
                </div>
            @empty
                <div class="col-span-4 text-center py-4 text-gray-500">No sales data</div>
            @endforelse
        </div>
    </div>
</div>
