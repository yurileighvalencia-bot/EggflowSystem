<div>
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Shop Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ App\Models\Shop::find($shopId)?->name ?? 'Your Shop' }} - Daily Operations</p>
    </div>

    {{-- Quick Action Buttons --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('pos.index') }}" class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl p-4 text-white hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg hover:shadow-xl">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <div>
                    <p class="font-semibold">New Sale</p>
                    <p class="text-xs opacity-80">POS</p>
                </div>
            </div>
        </a>
        <a href="{{ route('reservations') }}" class="bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl p-4 text-white hover:from-blue-600 hover:to-indigo-600 transition-all shadow-lg hover:shadow-xl">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div>
                    <p class="font-semibold">Reservations</p>
                    <p class="text-xs opacity-80">Manage</p>
                </div>
            </div>
        </a>
        <a href="{{ route('inventory.stock') }}" class="bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl p-4 text-white hover:from-green-600 hover:to-emerald-600 transition-all shadow-lg hover:shadow-xl">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <div>
                    <p class="font-semibold">Stock</p>
                    <p class="text-xs opacity-80">Check</p>
                </div>
            </div>
        </a>
        <a href="{{ route('inventory.low-stock') }}" class="bg-gradient-to-br from-red-500 to-pink-500 rounded-xl p-4 text-white hover:from-red-600 hover:to-pink-600 transition-all shadow-lg hover:shadow-xl">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="font-semibold">Restock</p>
                    <p class="text-xs opacity-80">Request</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Today's Sales Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Sales Today</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->todaySales['count'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Revenue</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($this->todaySales['revenue'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Eggs Sold</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->todaySales['items_sold']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Stock by Category --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Stock</h2>
            @if($this->stockByCategory->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">No inventory data</p>
            @else
                <div class="space-y-3">
                    @foreach($this->stockByCategory as $stock)
                        @php
                            $threshold = $stock->eggCategory->low_stock_threshold ?? 0;
                            $isLow = $stock->available <= $threshold && $stock->available > 0;
                        @endphp
                        <div class="flex items-center justify-between p-3 rounded-lg 
                            @if($isLow) bg-amber-50 dark:bg-amber-900/20 @else bg-gray-50 dark:bg-gray-700/50 @endif">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $stock->eggCategory->name }}</span>
                                @if($isLow)
                                    <span class="ml-2 text-xs text-amber-600 dark:text-amber-400">Low Stock</span>
                                @endif
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($stock->available) }}</span>
                                @if($stock->reserved > 0)
                                    <span class="text-sm text-blue-600 dark:text-blue-400 ml-2">(+{{ $stock->reserved }} reserved)</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Today's Pickups --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Today's Pickups</h2>
            @if($this->todayPickups->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2">No pending pickups today</p>
                </div>
            @else
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($this->todayPickups as $reservation)
                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $reservation->customer?->name ?? 'Walk-in' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $reservation->items->sum('quantity') }} eggs • {{ $reservation->pickup_time?->format('h:i A') ?? 'Anytime' }}
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                @if($reservation->status === 'ready') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300
                                @elseif($reservation->status === 'confirmed') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                {{ ucfirst($reservation->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Low Stock Alerts --}}
    @if($this->lowStockAlerts->isNotEmpty())
        <div class="mt-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <h3 class="font-semibold text-amber-800 dark:text-amber-200 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Low Stock Warnings
            </h3>
            <div class="flex flex-wrap gap-2">
                @foreach($this->lowStockAlerts as $alert)
                    <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 rounded-full text-sm">
                        {{ $alert->eggCategory->name }}: {{ $alert->available_stock }} left
                    </span>
                @endforeach
            </div>
            <a href="{{ route('inventory.low-stock') }}" class="inline-block mt-3 text-sm text-amber-700 dark:text-amber-300 hover:underline">
                Request Restock →
            </a>
        </div>
    @endif
</div>
