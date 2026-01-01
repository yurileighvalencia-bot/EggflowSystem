<div>
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Expiring Batches</h1>
        <p class="text-gray-600 dark:text-gray-400">FIFO expiration timeline - prioritize selling older batches first</p>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-red-200 dark:border-red-900 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-600 dark:text-red-400 font-medium">Already Expired</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->summary['expired']['count'] }} batches</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($this->summary['expired']['quantity']) }} eggs at risk</p>
                </div>
                <svg class="w-10 h-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-orange-200 dark:border-orange-900 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-orange-600 dark:text-orange-400 font-medium">Expires Today/Tomorrow</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $this->summary['critical']['count'] }} batches</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($this->summary['critical']['quantity']) }} eggs</p>
                </div>
                <svg class="w-10 h-10 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-amber-200 dark:border-amber-900 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-amber-600 dark:text-amber-400 font-medium">Expires in 2-3 Days</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->summary['warning']['count'] }} batches</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($this->summary['warning']['quantity']) }} eggs</p>
                </div>
                <svg class="w-10 h-10 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-48">
                <label for="farm-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Farm</label>
                <select wire:model.live="selectedFarmId" id="farm-filter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">All Farms</option>
                    @foreach($this->farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-32">
                <label for="days-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Show Next</label>
                <select wire:model.live="daysAhead" id="days-filter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="3">3 days</option>
                    <option value="7">7 days</option>
                    <option value="14">14 days</option>
                    <option value="28">28 days</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Expired Batches (if any) --}}
    @if($this->expiredBatches->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Expired Batches - Action Required
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-red-50 dark:bg-red-900/20">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Batch Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Farm</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qty Remaining</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Expired</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->expiredBatches as $batch)
                                <tr class="bg-red-50/50 dark:bg-red-900/10">
                                    <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ $batch->batch_code }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $batch->farm->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $batch->eggCategory->name }}
                                        <span class="text-gray-500 text-xs">({{ $batch->eggCategory->code }})</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-medium text-red-600 dark:text-red-400">
                                        {{ number_format($batch->current_quantity) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300">
                                            {{ $batch->days_expired }} day(s) ago
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Expiring Soon Timeline --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Expiration Timeline</h2>
        
        @if($this->expiringBatches->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-gray-500 dark:text-gray-400">No batches expiring in the next {{ $daysAhead }} days!</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th wire:click="sortBy('batch_code')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">
                                    Batch Code
                                    @if($sortBy === 'batch_code')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Farm</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                <th wire:click="sortBy('current_quantity')" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">
                                    Qty
                                    @if($sortBy === 'current_quantity')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th wire:click="sortBy('expires_at')" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">
                                    Expires
                                    @if($sortBy === 'expires_at')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Days Left</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->expiringBatches as $batch)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors
                                    @if($batch->urgency === 'critical') bg-orange-50/50 dark:bg-orange-900/10 @endif
                                    @if($batch->urgency === 'warning') bg-amber-50/50 dark:bg-amber-900/10 @endif
                                ">
                                    <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ $batch->batch_code }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $batch->farm->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $batch->eggCategory->name }}
                                        <span class="text-gray-500 text-xs">({{ $batch->eggCategory->code }})</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-medium text-gray-900 dark:text-white">
                                        {{ number_format($batch->current_quantity) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-center text-gray-700 dark:text-gray-300">
                                        {{ $batch->expires_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($batch->urgency === 'critical')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/50 text-orange-800 dark:text-orange-300">
                                                {{ $batch->days_until_expiry }} day(s)
                                            </span>
                                        @elseif($batch->urgency === 'warning')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300">
                                                {{ $batch->days_until_expiry }} days
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                                {{ $batch->days_until_expiry }} days
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
