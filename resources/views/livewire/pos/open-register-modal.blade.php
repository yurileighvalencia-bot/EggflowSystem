<div>
    @if($show)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                {{-- Modal Content --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/50 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Open Register
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Count your opening cash to start your shift.
                                </p>

                                {{-- Denomination Counter --}}
                                <div class="mt-4 space-y-3">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Denomination Count</h4>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach([1000, 500, 200, 100, 50, 20] as $denom)
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">₱{{ number_format($denom) }}</span>
                                                <input 
                                                    type="number" 
                                                    wire:model.live="denominations.{{ $denom }}"
                                                    min="0"
                                                    class="w-16 px-2 py-1 text-center text-sm border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                                >
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="grid grid-cols-4 gap-2">
                                        @foreach([10, 5, 1, '0.25'] as $denom)
                                            <div class="flex flex-col items-center bg-gray-50 dark:bg-gray-700 rounded-lg px-2 py-2">
                                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    @if($denom == '0.25')
                                                        25¢
                                                    @else
                                                        ₱{{ $denom }}
                                                    @endif
                                                </span>
                                                <input 
                                                    type="number" 
                                                    wire:model.live="denominations.{{ $denom }}"
                                                    min="0"
                                                    class="w-12 px-1 py-1 text-center text-sm border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Total --}}
                                <div class="mt-4 bg-green-50 dark:bg-green-900/30 rounded-lg p-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Opening Cash Total:</span>
                                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">
                                            ₱{{ number_format($this->openingCash, 2) }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div class="mt-4">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (Optional)</label>
                                    <textarea 
                                        wire:model="notes" 
                                        id="notes" 
                                        rows="2" 
                                        placeholder="Any notes about starting the shift..."
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="openShift"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="openShift">Open Register</span>
                            <span wire:loading wire:target="openShift">Opening...</span>
                        </button>
                        <button 
                            wire:click="hide"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
