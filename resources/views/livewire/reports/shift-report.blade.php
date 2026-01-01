<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Shift Report</h1>
            <p class="text-gray-600 dark:text-gray-400">Review closed shifts and cash discrepancies</p>
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
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            {{-- Shop --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shop</label>
                <select wire:model.live="shopId"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Shops</option>
                    @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Staff --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Staff</label>
                <select wire:model.live="userId"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Staff</option>
                    @foreach($this->staff as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            {{-- Discrepancy Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discrepancy</label>
                <select wire:model.live="discrepancyFilter"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All</option>
                    <option value="shortage">Shortage Only</option>
                    <option value="overage">Overage Only</option>
                    <option value="balanced">Balanced Only</option>
                </select>
            </div>

            {{-- Clear --}}
            <div class="flex items-end">
                <button wire:click="clearFilters"
                    class="w-full px-3 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Reset
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->summary['total_shifts'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Shifts</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-yellow-200 dark:border-yellow-700 p-4">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->summary['with_discrepancy'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">With Discrepancy</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ number_format($this->summary['discrepancy_rate'], 1) }}%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Discrepancy Rate</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-700 p-4">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">₱{{ number_format(abs($this->summary['total_shortage']), 2) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Shortage</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-700 p-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($this->summary['total_overage'], 2) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Overage</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border {{ $this->summary['net_discrepancy'] >= 0 ? 'border-green-200 dark:border-green-700' : 'border-red-200 dark:border-red-700' }} p-4">
            <div class="text-2xl font-bold {{ $this->summary['net_discrepancy'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $this->summary['net_discrepancy'] >= 0 ? '+' : '' }}₱{{ number_format($this->summary['net_discrepancy'], 2) }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Net Discrepancy</div>
        </div>
    </div>

    {{-- Shifts Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Staff</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Shop</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Opening</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Expected</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actual</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Discrepancy</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->shifts as $shift)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $shift->opened_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $shift->opened_at->format('g:i A') }} - {{ $shift->closed_at?->format('g:i A') ?? 'Open' }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $shift->user->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $shift->shop->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">
                            ₱{{ number_format($shift->opening_cash, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                            ₱{{ number_format($shift->expected_cash, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                            ₱{{ number_format($shift->closing_cash, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($shift->discrepancy == 0)
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    Balanced
                                </span>
                            @elseif($shift->discrepancy > 0)
                                <span class="text-sm font-medium text-green-600 dark:text-green-400">
                                    +₱{{ number_format($shift->discrepancy, 2) }}
                                </span>
                            @else
                                <span class="text-sm font-medium text-red-600 dark:text-red-400">
                                    -₱{{ number_format(abs($shift->discrepancy), 2) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="viewDetails({{ $shift->id }})"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded"
                                title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                            No closed shifts found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($this->shifts->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->shifts->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedShift)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="closeDetailModal"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Shift Details</h3>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Shift Info --}}
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Staff:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $selectedShift->user->name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Shop:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $selectedShift->shop->name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Opened:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $selectedShift->opened_at->format('M d, Y g:i A') }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Closed:</span>
                            <span class="ml-2 text-gray-900 dark:text-white">{{ $selectedShift->closed_at?->format('M d, Y g:i A') ?? 'Still Open' }}</span>
                        </div>
                    </div>

                    {{-- Cash Summary --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Cash Summary</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Opening Cash:</span>
                                <span class="font-medium text-gray-900 dark:text-white">₱{{ number_format($selectedShift->opening_cash, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Cash Sales:</span>
                                <span class="font-medium text-green-600 dark:text-green-400">
                                    +₱{{ number_format($selectedShift->sales()->where('payment_method', 'cash')->where('status', 'completed')->sum('total'), 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Cash In:</span>
                                <span class="font-medium text-green-600 dark:text-green-400">
                                    +₱{{ number_format($selectedShift->adjustments()->where('type', 'cash_in')->sum('amount'), 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Cash Out:</span>
                                <span class="font-medium text-red-600 dark:text-red-400">
                                    -₱{{ number_format($selectedShift->adjustments()->where('type', 'cash_out')->sum('amount'), 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-600 mt-4 pt-4 space-y-2">
                            <div class="flex justify-between font-medium">
                                <span class="text-gray-700 dark:text-gray-300">Expected:</span>
                                <span class="text-gray-900 dark:text-white">₱{{ number_format($selectedShift->expected_cash, 2) }}</span>
                            </div>
                            <div class="flex justify-between font-medium">
                                <span class="text-gray-700 dark:text-gray-300">Actual:</span>
                                <span class="text-gray-900 dark:text-white">₱{{ number_format($selectedShift->closing_cash, 2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold">
                                <span class="text-gray-700 dark:text-gray-300">Discrepancy:</span>
                                <span class="{{ $selectedShift->discrepancy >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $selectedShift->discrepancy >= 0 ? '+' : '' }}₱{{ number_format($selectedShift->discrepancy, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Adjustments --}}
                    @if($selectedShift->adjustments->isNotEmpty())
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Cash Adjustments</h4>
                            <div class="space-y-2">
                                @foreach($selectedShift->adjustments as $adj)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                                        <div class="flex items-center gap-2">
                                            @if($adj->type === 'cash_in')
                                                <span class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                    </svg>
                                                </span>
                                            @endif
                                            <div>
                                                <span class="text-sm font-medium {{ $adj->type === 'cash_in' ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $adj->type === 'cash_in' ? '+' : '-' }}₱{{ number_format($adj->amount, 2) }}
                                                </span>
                                                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ $adj->reason }}</span>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-400">
                                            {{ $adj->adjusted_at->format('g:i A') }} by {{ $adj->user->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <button wire:click="closeDetailModal"
                        class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
