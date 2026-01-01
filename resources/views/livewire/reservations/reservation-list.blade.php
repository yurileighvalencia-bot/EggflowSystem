<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reservations</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage customer reservations and pickups</p>
        </div>
        <a href="{{ route('reservations.create') }}" 
            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Reservation
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->stats['total']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-yellow-200 dark:border-yellow-700 p-4">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($this->stats['pending']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700 p-4">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->stats['confirmed']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Confirmed</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-700 p-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->stats['ready']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Ready</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700 p-4">
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['today_pickup']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Today's Pickup</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Code or customer..."
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select 
                    wire:model.live="status"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="ready">Ready</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="expired">Expired</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pickup From</label>
                <input 
                    type="date" 
                    wire:model.live="dateFrom"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pickup To</label>
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
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    {{-- Reservations Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Items</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pickup</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->reservations as $reservation)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <span class="font-mono text-sm text-gray-900 dark:text-white">
                                {{ $reservation->reservation_code }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $reservation->customer->name ?? 'Walk-in' }}</div>
                            @if($reservation->customer?->phone)
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reservation->customer->phone }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $reservation->items->count() }} item(s)
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $reservation->items->sum('quantity') }} eggs
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-900 dark:text-white">
                                ₱{{ number_format($reservation->total, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $reservation->pickup_date->format('M d, Y') }}
                            </div>
                            @if($reservation->pickup_time)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $reservation->pickup_time->format('g:i A') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php $color = $this->getStatusColor($reservation->status); @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 text-{{ $color }}-800 dark:text-{{ $color }}-300">
                                {{ ucfirst($reservation->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                {{-- Status-based actions --}}
                                @if($reservation->status === 'pending')
                                    <button 
                                        wire:click="confirmReservation({{ $reservation->id }})"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded"
                                        title="Confirm"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                @endif

                                @if($reservation->status === 'confirmed')
                                    <button 
                                        wire:click="markReady({{ $reservation->id }})"
                                        class="p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded"
                                        title="Mark Ready"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                @endif

                                @if($reservation->status === 'ready')
                                    <a 
                                        href="{{ route('reservations.convert', $reservation) }}"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded"
                                        title="Convert to Sale"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </a>
                                @endif

                                @if($reservation->isActive())
                                    <button 
                                        wire:click="openCancelModal({{ $reservation->id }})"
                                        class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded"
                                        title="Cancel"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-2">No reservations found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($this->reservations->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->reservations->links() }}
            </div>
        @endif
    </div>

    {{-- Cancel Modal --}}
    @if($showCancelModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="closeCancelModal"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cancel Reservation</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Reason for cancellation
                        </label>
                        <textarea 
                            wire:model="cancellationReason"
                            rows="3"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Optional: Provide a reason..."
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            wire:click="closeCancelModal"
                            class="flex-1 px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Keep Reservation
                        </button>
                        <button 
                            wire:click="cancelReservation"
                            class="flex-1 px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700"
                        >
                            Confirm Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
