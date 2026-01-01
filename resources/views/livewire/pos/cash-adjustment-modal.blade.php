<div>
    @if($show && $shift)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" wire:click="hide"></div>

            <!-- Modal Panel -->
            <div class="relative inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                        Cash Adjustment
                    </h3>
                    <button wire:click="hide" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Adjustment Form -->
                <form wire:submit.prevent="recordAdjustment" class="space-y-4">
                    <!-- Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Type
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" wire:model="type" value="cash_in" class="sr-only peer">
                                <div class="px-4 py-2 rounded-lg border-2 transition-all peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/30 border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cash In</span>
                                    </div>
                                </div>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" wire:model="type" value="cash_out" class="sr-only peer">
                                <div class="px-4 py-2 rounded-lg border-2 transition-all peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/30 border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cash Out</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Amount
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                            <input 
                                type="number" 
                                id="amount"
                                wire:model="amount" 
                                step="0.01"
                                min="0.01"
                                class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="0.00"
                            >
                        </div>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reason -->
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Reason
                        </label>
                        <input 
                            type="text" 
                            id="reason"
                            wire:model="reason" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="e.g., Change for customer, Petty cash withdrawal"
                        >
                        @error('reason')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button 
                            type="submit" 
                            class="w-full px-4 py-2 text-white rounded-lg transition-colors {{ $type === 'cash_in' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}"
                        >
                            Record {{ $type === 'cash_in' ? 'Cash In' : 'Cash Out' }}
                        </button>
                    </div>
                </form>

                <!-- Adjustment History -->
                @if($this->adjustments->isNotEmpty())
                    <div class="mt-6 pt-6 border-t dark:border-gray-700">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Adjustment History (This Shift)
                        </h4>
                        <div class="max-h-40 overflow-y-auto space-y-2">
                            @foreach($this->adjustments as $adjustment)
                                <div class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-700/50 text-sm">
                                    <div class="flex items-center gap-2">
                                        @if($adjustment->type === 'cash_in')
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
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ $adjustment->type === 'cash_in' ? '+' : '-' }}₱{{ number_format($adjustment->amount, 2) }}
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400 ml-2">{{ $adjustment->reason }}</span>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400">
                                        {{ $adjustment->adjusted_at->format('g:i A') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Close Button -->
                <div class="mt-4 pt-4 border-t dark:border-gray-700">
                    <button 
                        wire:click="hide" 
                        class="w-full px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
