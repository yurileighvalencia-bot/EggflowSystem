<div>
    @if($show && $shift)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" wire:click="hide"></div>

            <!-- Modal Panel -->
            <div class="relative inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
                @if(!$closed)
                    <!-- Close Register Form -->
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                                Close Register
                            </h3>
                            <button wire:click="hide" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Flash Messages -->
                        @if(session('error'))
                            <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
                                {{ session('error') }}
                            </div>
                        @endif

                        <!-- Shift Summary -->
                        <div class="mb-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Shift Summary</h4>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Opened:</span>
                                    <span class="ml-1 text-gray-900 dark:text-white">{{ $this->shiftSummary['opened_at'] ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Opening Cash:</span>
                                    <span class="ml-1 text-gray-900 dark:text-white">₱{{ number_format($this->shiftSummary['opening_cash'] ?? 0, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Total Sales:</span>
                                    <span class="ml-1 text-gray-900 dark:text-white">{{ $this->shiftSummary['total_sales'] ?? 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Adjustments:</span>
                                    <span class="ml-1 text-gray-900 dark:text-white">
                                        {{ $this->shiftSummary['cash_in_count'] ?? 0 }} in / {{ $this->shiftSummary['cash_out_count'] ?? 0 }} out
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Denomination Counter -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Count Cash in Drawer
                            </h4>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($denominationLabels as $value => $label)
                                    <div class="flex items-center justify-between p-2 rounded-lg border dark:border-gray-600 {{ $denominations[$value] > 0 ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700' : 'bg-gray-50 dark:bg-gray-700' }}">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-16">{{ $label }}</span>
                                        <div class="flex items-center gap-1">
                                            <button 
                                                type="button"
                                                wire:click="decrement('{{ $value }}')"
                                                class="w-7 h-7 flex items-center justify-center rounded bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                </svg>
                                            </button>
                                            <input 
                                                type="number" 
                                                wire:model.live="denominations.{{ $value }}"
                                                min="0"
                                                class="w-12 text-center py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                            >
                                            <button 
                                                type="button"
                                                wire:click="increment('{{ $value }}')"
                                                class="w-7 h-7 flex items-center justify-center rounded bg-amber-500 text-white hover:bg-amber-600 transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Total Display -->
                        <div class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-amber-800 dark:text-amber-300">
                                    Cash in Drawer:
                                </span>
                                <span class="text-2xl font-bold text-amber-900 dark:text-amber-200">
                                    ₱{{ number_format($this->closingCash, 2) }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button 
                                wire:click="hide" 
                                class="flex-1 px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                wire:click="closeShift"
                                wire:confirm="Are you sure you want to close this shift? This action cannot be undone."
                                class="flex-1 px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                            >
                                Close Shift
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Shift Closed Confirmation -->
                    <div class="text-center py-6">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            Shift Closed
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Your shift has been closed successfully.
                        </p>

                        <!-- Show discrepancy to managers only -->
                        @if($this->canSeeDiscrepancy && $shift->discrepancy != 0)
                            <div class="mb-6 p-4 rounded-lg {{ $shift->discrepancy > 0 ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700' }}">
                                <h4 class="text-sm font-medium {{ $shift->discrepancy > 0 ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }} mb-2">
                                    Discrepancy Detected
                                </h4>
                                <div class="grid grid-cols-3 gap-2 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400 block">Expected</span>
                                        <span class="font-medium text-gray-900 dark:text-white">₱{{ number_format($shift->expected_cash, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400 block">Actual</span>
                                        <span class="font-medium text-gray-900 dark:text-white">₱{{ number_format($shift->closing_cash, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400 block">Difference</span>
                                        <span class="font-bold {{ $shift->discrepancy > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $shift->discrepancy > 0 ? '+' : '' }}₱{{ number_format($shift->discrepancy, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <button 
                            wire:click="hide" 
                            class="w-full px-4 py-2 text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors"
                        >
                            Done
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
