<div>
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transaction History</h1>
        <p class="text-gray-600 dark:text-gray-400">View and manage today's sales</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/50 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Today's Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Today's Sales</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->todaySummary['total_sales'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Revenue</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($this->todaySummary['total_revenue'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Average Sale</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">₱{{ number_format($this->todaySummary['average_sale'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Voided</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->todaySummary['voided_count'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Voided Amount</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">₱{{ number_format($this->todaySummary['voided_amount'], 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Sale code or customer..."
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                <select wire:model.live="dateFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                    <option value="">All</option>
                    <option value="completed">Completed</option>
                    <option value="voided">Voided</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            <button wire:click="resetFilters" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                Reset
            </button>
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Items</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payment</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors
                            @if($sale->status === 'voided') bg-red-50/50 dark:bg-red-900/10 @endif">
                            <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900 dark:text-white">
                                {{ $sale->sale_code }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $sale->sold_at->format('h:i A') }}
                                <span class="text-xs text-gray-500 block">{{ $sale->sold_at->format('M d') }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $sale->items->count() }} items
                                <span class="text-xs text-gray-500 block">{{ $sale->items->sum('quantity') }} eggs</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 dark:text-white">
                                ₱{{ number_format($sale->total, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    @if($sale->payment_method === 'cash') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300
                                    @elseif($sale->payment_method === 'card') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    {{ ucfirst($sale->payment_method) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($sale->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                        Completed
                                    </span>
                                @elseif($sale->status === 'voided')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300">
                                        Voided
                                    </span>
                                @elseif($sale->status === 'refunded')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300">
                                        Refunded
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="viewSale({{ $sale->id }})"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    @if($sale->status === 'completed')
                                        <button wire:click="confirmVoid({{ $sale->id }})"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="mt-2">No transactions found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sales->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    {{-- Void Confirmation Modal --}}
    @if($showVoidModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showVoidModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Void Sale</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    This will void the sale and restore inventory. This action cannot be undone.
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason for voiding *</label>
                    <textarea wire:model="voidReason" rows="3"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="Enter reason..."></textarea>
                    @error('voidReason') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3">
                    <button wire:click="$set('showVoidModal', false)"
                        class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button wire:click="voidSale"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                        Void Sale
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Sale Detail Modal --}}
    @if($showDetailModal && $selectedSale)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showDetailModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full mx-4 p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedSale->sale_code }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedSale->sold_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <button wire:click="$set('showDetailModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Sale Items --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Items</h4>
                    <div class="space-y-2">
                        @foreach($selectedSale->items as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $item->quantity }}x {{ $item->eggCategory?->name ?? 'Unknown' }}
                                    @if($item->batch)
                                        <span class="text-xs text-gray-500">({{ $item->batch->batch_code }})</span>
                                    @endif
                                </span>
                                <span class="font-medium">₱{{ number_format($item->line_total, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Totals --}}
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Subtotal</span>
                        <span>₱{{ number_format($selectedSale->subtotal, 2) }}</span>
                    </div>
                    @if($selectedSale->tax > 0)
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Tax</span>
                            <span>₱{{ number_format($selectedSale->tax, 2) }}</span>
                        </div>
                    @endif
                    @if($selectedSale->discount > 0)
                        <div class="flex justify-between text-sm text-green-600">
                            <span>Discount</span>
                            <span>-₱{{ number_format($selectedSale->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-200 dark:border-gray-600">
                        <span>Total</span>
                        <span>₱{{ number_format($selectedSale->total, 2) }}</span>
                    </div>
                </div>

                {{-- Details --}}
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <p><span class="font-medium">Payment:</span> {{ ucfirst($selectedSale->payment_method) }}</p>
                    <p><span class="font-medium">Staff:</span> {{ $selectedSale->staff?->name ?? 'Unknown' }}</p>
                    <p><span class="font-medium">Status:</span> 
                        <span class="@if($selectedSale->status === 'voided') text-red-600 @else text-green-600 @endif">
                            {{ ucfirst($selectedSale->status) }}
                        </span>
                    </p>
                    @if($selectedSale->notes)
                        <p><span class="font-medium">Notes:</span> {{ $selectedSale->notes }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
