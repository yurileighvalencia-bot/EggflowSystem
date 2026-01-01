<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Wastage History</h1>
            <p class="text-gray-600 dark:text-gray-400">Review all recorded wastage entries</p>
        </div>
        <a href="{{ route('inventory.log-wastage') }}" 
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Log Wastage
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->stats['total_entries']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Entries</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-700 p-4">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->stats['total_quantity']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Eggs Wasted</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">By Source</div>
            <div class="space-y-1">
                @foreach($this->stats['by_source'] as $source => $qty)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ \App\Models\WastageLog::SOURCES[$source] ?? $source }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($qty) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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

            {{-- Source --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                <select 
                    wire:model.live="source"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All Sources</option>
                    @foreach($this->sourceOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
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
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    {{-- Wastage Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date/Time</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Shop</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Batch</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Source</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Logged By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->wastageLogs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $log->logged_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->logged_at->format('g:i A') }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $log->shop->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $log->eggCategory->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($log->batch)
                                <span class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ $log->batch->batch_code }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php $color = $this->getSourceColor($log->source); @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 text-{{ $color }}-800 dark:text-{{ $color }}-300">
                                {{ \App\Models\WastageLog::SOURCES[$log->source] ?? $log->source }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($log->quantity) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $log->loggedBy->name ?? 'System' }}
                        </td>
                    </tr>
                    @if($log->reason)
                        <tr class="bg-gray-50 dark:bg-gray-700/30">
                            <td colspan="7" class="px-4 py-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Reason:</span>
                                <span class="text-sm text-gray-700 dark:text-gray-300 ml-2">{{ $log->reason }}</span>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="mt-2">No wastage records found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($this->wastageLogs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->wastageLogs->links() }}
            </div>
        @endif
    </div>
</div>
