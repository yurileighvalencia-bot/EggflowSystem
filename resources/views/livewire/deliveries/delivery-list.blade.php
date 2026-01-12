<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Deliveries</h1>
            <p class="text-gray-600 dark:text-gray-400">Track and manage egg deliveries</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('deliveries.discrepancies') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Discrepancies
                @if($this->stats['disputed'] > 0)
                    <span class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full">{{ $this->stats['disputed'] }}</span>
                @endif
            </a>
            <a href="{{ route('deliveries.dispatch') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Dispatch Delivery
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-yellow-500 uppercase">In Transit</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->stats['in_transit'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-green-500 uppercase">Received</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->stats['received'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-red-500 uppercase">Disputed</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->stats['disputed'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by shop or dispatcher..." class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            {{-- Shop Filter --}}
            @if(!auth()->user()?->isShopStaff())
            <div>
                <select wire:model.live="shopId" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Shops</option>
                    @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Status Filter --}}
            <div>
                <select wire:model.live="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Statuses</option>
                    @foreach($this->statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="flex gap-2">
                <input type="date" wire:model.live="dateFrom" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <input type="date" wire:model.live="dateTo" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button wire:click="resetFilters" class="text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400">
                Reset Filters
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                        <th wire:click="sortBy('dispatched_at')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                            Dispatched
                            @if($sortBy === 'dispatched_at')
                                <span class="ml-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Shop</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dispatcher</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Received</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->deliveries as $delivery)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">
                                #{{ $delivery->id }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $delivery->dispatched_at?->format('M d, Y') }}
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $delivery->dispatched_at?->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $delivery->shop?->name }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $delivery->items->sum('qty_dispatched') }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block">
                                    {{ $delivery->items->count() }} types
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $this->getStatusClass($delivery->status) }}">
                                    {{ $this->statuses[$delivery->status] ?? $delivery->status }}
                                </span>
                                @if($delivery->discrepancies->count() > 0)
                                    <span class="block text-xs text-red-500 mt-1">
                                        {{ $delivery->discrepancies->count() }} discrepancy
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $delivery->dispatcher?->name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                @if($delivery->received_at)
                                    {{ $delivery->received_at->format('M d, H:i') }}
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        by {{ $delivery->receiver?->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="{{ route('deliveries.show', $delivery) }}" class="p-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    @if($delivery->status === 'in_transit' || $delivery->status === 'dispatched')
                                        <a href="{{ route('deliveries.receive', $delivery) }}" class="p-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Receive">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($delivery->status === 'received' || $delivery->status === 'partial')
                                        <button 
                                            wire:click="$dispatch('open-discrepancy-form', { deliveryId: {{ $delivery->id }} })"
                                            class="p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" 
                                            title="Report Issue"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No deliveries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->deliveries->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->deliveries->links() }}
            </div>
        @endif
    </div>

    {{-- Report Discrepancy Slide-over --}}
    @livewire('deliveries.report-discrepancy')
</div>
