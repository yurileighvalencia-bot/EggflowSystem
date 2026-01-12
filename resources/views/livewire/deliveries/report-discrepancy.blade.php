<div>
    {{-- Slide-over backdrop --}}
    <div 
        x-data="{ open: @entangle('showSlideOver') }"
        x-show="open"
        x-cloak
        class="relative z-50"
        aria-labelledby="slide-over-title" 
        role="dialog" 
        aria-modal="true"
    >
        {{-- Background overlay --}}
        <div 
            x-show="open"
            x-transition:enter="ease-in-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity"
            @click="$wire.closeForm()"
        ></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    {{-- Slide-over panel --}}
                    <div 
                        x-show="open"
                        x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-300"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-full"
                        class="pointer-events-auto w-screen max-w-lg"
                    >
                        <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-gray-800 shadow-xl">
                            {{-- Header --}}
                            <div class="bg-red-600 px-4 py-6 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <h2 id="slide-over-title" class="text-lg font-semibold text-white">
                                        <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        Report Discrepancy
                                    </h2>
                                    <button 
                                        type="button" 
                                        wire:click="closeForm"
                                        class="rounded-md text-red-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-white"
                                    >
                                        <span class="sr-only">Close panel</span>
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-red-100">
                                    Report missing or rejected eggs from this delivery.
                                </p>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 px-4 py-6 sm:px-6 space-y-6">
                                @if($this->delivery)
                                    {{-- Delivery Info Card --}}
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Delivery Details</h3>
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">ID:</span>
                                                <span class="ml-1 font-mono text-gray-900 dark:text-white">#{{ $this->delivery->id }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Shop:</span>
                                                <span class="ml-1 text-gray-900 dark:text-white">{{ $this->delivery->shop?->name }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Dispatched:</span>
                                                <span class="ml-1 text-gray-900 dark:text-white">{{ $this->delivery->dispatched_at?->format('M d, Y H:i') }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Total Sent:</span>
                                                <span class="ml-1 font-semibold text-gray-900 dark:text-white">{{ $this->delivery->total_sent }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Select Item (if delivery has multiple items) --}}
                                    @if($this->delivery->items->count() > 1)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Select Item (Optional)
                                            </label>
                                            <div class="space-y-2">
                                                <button 
                                                    type="button"
                                                    wire:click="$set('deliveryItemId', null)"
                                                    class="w-full text-left px-3 py-2 rounded-lg border transition {{ !$deliveryItemId ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                                                >
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Entire Delivery</span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 block">Report for all items combined</span>
                                                </button>
                                                @foreach($this->delivery->items as $item)
                                                    <button 
                                                        type="button"
                                                        wire:click="selectItem({{ $item->id }})"
                                                        class="w-full text-left px-3 py-2 rounded-lg border transition {{ $deliveryItemId === $item->id ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                                                    >
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $item->eggCategory?->name ?? 'Unknown' }}
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">
                                                            Batch #{{ $item->batch_id }} • Sent: {{ $item->qty_sent }}
                                                        </span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Quantity Fields --}}
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="qtySent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Qty Sent
                                            </label>
                                            <input 
                                                type="number" 
                                                id="qtySent"
                                                wire:model.live="qtySent" 
                                                min="0"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            >
                                            @error('qtySent')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="qtyReceived" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Qty Received
                                            </label>
                                            <input 
                                                type="number" 
                                                id="qtyReceived"
                                                wire:model.live="qtyReceived" 
                                                min="0"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            >
                                            @error('qtyReceived')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="qtyRejected" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Qty Rejected
                                            </label>
                                            <input 
                                                type="number" 
                                                id="qtyRejected"
                                                wire:model.live="qtyRejected" 
                                                min="0"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            >
                                            @error('qtyRejected')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="qtyMissing" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Qty Missing
                                            </label>
                                            <input 
                                                type="number" 
                                                id="qtyMissing"
                                                wire:model="qtyMissing" 
                                                min="0"
                                                readonly
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-gray-50 dark:bg-gray-600 dark:border-gray-600 dark:text-white cursor-not-allowed"
                                            >
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Auto-calculated</p>
                                        </div>
                                    </div>

                                    {{-- Missing Quantity Alert --}}
                                    @if($qtyMissing > 0)
                                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                            <div class="flex items-start">
                                                <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                                <div>
                                                    <h4 class="text-sm font-medium text-red-800 dark:text-red-300">
                                                        {{ $qtyMissing }} eggs unaccounted for
                                                    </h4>
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                                        This discrepancy will be flagged for investigation.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Notes --}}
                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Notes
                                        </label>
                                        <textarea 
                                            id="notes"
                                            wire:model="notes" 
                                            rows="3"
                                            placeholder="Describe the discrepancy (e.g., cracked eggs, missing tray, wrong count)..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                                        ></textarea>
                                        @error('notes')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @else
                                    {{-- No delivery selected --}}
                                    <div class="text-center py-8">
                                        <svg class="mx-auto w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <p class="mt-2 text-gray-500 dark:text-gray-400">No delivery selected.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Footer --}}
                            <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 px-4 py-4 sm:px-6">
                                <div class="flex justify-end space-x-3">
                                    <button 
                                        type="button"
                                        wire:click="closeForm"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                                    >
                                        Cancel
                                    </button>
                                    <button 
                                        type="button"
                                        wire:click="submit"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-75 cursor-not-allowed"
                                        {{ $qtyMissing <= 0 && $qtyRejected <= 0 ? 'disabled' : '' }}
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span wire:loading.remove wire:target="submit">
                                            <svg class="w-4 h-4 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            Report Discrepancy
                                        </span>
                                        <span wire:loading wire:target="submit">
                                            <svg class="w-4 h-4 mr-2 -ml-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Submitting...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
