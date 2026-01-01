<div>
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Low Stock Alerts</h1>
        <p class="text-gray-600 dark:text-gray-400">Monitor inventory levels and trigger restock requests</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex items-end gap-4">
            <div class="flex-1 max-w-xs">
                <label for="shop-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter by Shop</label>
                <select wire:model.live="selectedShopId" id="shop-filter" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">All Shops</option>
                    @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Out of Stock Section --}}
    @if($this->outOfStockAlerts->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Out of Stock ({{ $this->outOfStockAlerts->count() }})
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-red-50 dark:bg-red-900/20">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Shop</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Suggested Qty</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->outOfStockAlerts as $alert)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $alert->shop->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $alert->eggCategory->name }}
                                        <span class="text-gray-500 text-xs">({{ $alert->eggCategory->code }})</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-700 dark:text-gray-300">
                                        {{ number_format($alert->suggested_restock) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($alert->has_pending_request)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Request Pending</span>
                                        @else
                                            <button wire:click="openRequestModal({{ $alert->shop_id }}, {{ $alert->egg_category_id }}, {{ $alert->suggested_restock }})"
                                                class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                                Request Restock
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Low Stock Section --}}
    <div>
        <h2 class="text-lg font-semibold text-amber-600 dark:text-amber-400 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Low Stock Warnings ({{ $this->lowStockAlerts->count() }})
        </h2>
        
        @if($this->lowStockAlerts->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-gray-500 dark:text-gray-400">All stock levels are healthy!</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-amber-200 dark:border-amber-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-amber-50 dark:bg-amber-900/20">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Shop</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Current</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Threshold</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Deficit</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->lowStockAlerts as $alert)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $alert->shop->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $alert->eggCategory->name }}
                                        <span class="text-gray-500 text-xs">({{ $alert->eggCategory->code }})</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-amber-600 dark:text-amber-400 font-medium">
                                        {{ number_format($alert->available_stock) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-700 dark:text-gray-300">
                                        {{ number_format($alert->threshold) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-red-600 dark:text-red-400 font-medium">
                                        -{{ number_format($alert->deficit) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($alert->has_pending_request)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Request Pending</span>
                                        @else
                                            <button wire:click="openRequestModal({{ $alert->shop_id }}, {{ $alert->egg_category_id }}, {{ $alert->suggested_restock }})"
                                                class="px-3 py-1 text-sm bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors">
                                                Request Restock
                                            </button>
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

    {{-- Restock Request Modal --}}
    @if($showRequestModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showRequestModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Submit Restock Request</h3>
                
                <form wire:submit="submitRestockRequest">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                            <input type="number" wire:model="requestQuantity" min="1"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            @error('requestQuantity') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (Optional)</label>
                            <textarea wire:model="requestNotes" rows="3"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Any additional notes..."></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" wire:click="$set('showRequestModal', false)"
                            class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
