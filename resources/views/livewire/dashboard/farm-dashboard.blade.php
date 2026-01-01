<div>
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Farm Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ App\Models\Farm::find($farmId)?->name ?? 'Your Farm' }} - Production Overview</p>
    </div>

    {{-- Today's Collection Summary --}}
    <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white shadow-lg mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-medium opacity-90">Today's Collection</h2>
                <p class="text-4xl font-bold mt-1">{{ number_format($this->todayCollections['total']) }} eggs</p>
            </div>
            <div class="text-6xl">🥚</div>
        </div>
        @if(!empty($this->todayCollections['by_category']))
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach($this->todayCollections['by_category'] as $category => $qty)
                    <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                        {{ $category }}: {{ number_format($qty) }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('collections') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 hover:border-green-500 dark:hover:border-green-500 transition-colors text-center">
            <svg class="w-8 h-8 mx-auto mb-2 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Record Collection</span>
        </a>
        <a href="{{ route('deliveries') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 transition-colors text-center">
            <svg class="w-8 h-8 mx-auto mb-2 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Dispatch Delivery</span>
        </a>
        <a href="{{ route('inventory.expiring') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 hover:border-orange-500 dark:hover:border-orange-500 transition-colors text-center">
            <svg class="w-8 h-8 mx-auto mb-2 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Expiring Batches</span>
        </a>
        <a href="{{ route('restock-requests') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 hover:border-amber-500 dark:hover:border-amber-500 transition-colors text-center">
            <svg class="w-8 h-8 mx-auto mb-2 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Restock Requests</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Active Batches --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Active Batches</h2>
            @if($this->activeBatches->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">No active batches</p>
            @else
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach($this->activeBatches as $batch)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div>
                                <p class="font-mono text-sm font-medium text-gray-900 dark:text-white">{{ $batch->batch_code }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $batch->eggCategory->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900 dark:text-white">{{ number_format($batch->current_quantity) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Exp: {{ $batch->expires_at->format('M d') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Pending Restock Requests --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pending Restock Requests</h2>
            @if($this->pendingRestockRequests->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2">No pending requests</p>
                </div>
            @else
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach($this->pendingRestockRequests as $request)
                        <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $request->shop->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $request->eggCategory->name }} • {{ number_format($request->requested_quantity) }} eggs
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                @if($request->status === 'acknowledged') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300
                                @else bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 @endif">
                                {{ ucfirst($request->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Expiring Soon Alert --}}
    @if($this->expiringBatches->isNotEmpty())
        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-4">
            <h3 class="font-semibold text-orange-800 dark:text-orange-200 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Batches Expiring Within 3 Days
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($this->expiringBatches as $batch)
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-orange-200 dark:border-orange-800">
                        <p class="font-mono text-sm font-medium text-gray-900 dark:text-white">{{ $batch->batch_code }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $batch->eggCategory->name }}</p>
                        <div class="flex justify-between mt-2">
                            <span class="text-orange-600 dark:text-orange-400 font-medium">{{ number_format($batch->current_quantity) }} eggs</span>
                            <span class="text-xs text-gray-500">{{ $batch->expires_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
