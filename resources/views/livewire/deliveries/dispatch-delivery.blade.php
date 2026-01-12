<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dispatch Delivery</h1>
            <p class="text-gray-600 dark:text-gray-400">Send eggs to shop locations</p>
        </div>
        <a href="{{ route('deliveries.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to List
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="p-4 text-sm text-green-800 bg-green-100 border border-green-200 rounded-lg dark:bg-green-900/50 dark:text-green-400 dark:border-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg dark:bg-red-900/50 dark:text-red-400 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Delivery Details --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Delivery Details</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Shop Selection --}}
            <div>
                <label for="shopId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Destination Shop <span class="text-red-500">*</span>
                </label>
                <select wire:model.live="shopId" id="shopId" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Select a shop</option>
                    @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
                @error('shopId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Linked Restock Request --}}
            <div>
                <label for="restockRequestId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Linked Restock Request (Optional)
                </label>
                <select wire:model.live="restockRequestId" id="restockRequestId" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">No linked request</option>
                    @foreach($this->restockRequests as $request)
                        <option value="{{ $request->id }}">
                            #{{ $request->id }} - {{ $request->shop?->name }} ({{ $request->status }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Dispatch Items --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Items to Dispatch</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Batch</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Available</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($dispatchItems as $categoryId => $item)
                        @php
                            $batches = $this->availableBatches[$categoryId] ?? collect();
                            $selectedBatch = $item['batch_id'] ? \App\Models\Batch::find($item['batch_id']) : null;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                                <span class="ml-2 px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded dark:bg-amber-900/50 dark:text-amber-400">
                                    {{ $item['code'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <select 
                                        wire:model.live="dispatchItems.{{ $categoryId }}.batch_id"
                                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    >
                                        <option value="">Select batch</option>
                                        @foreach($batches as $batch)
                                            <option value="{{ $batch->id }}">
                                                {{ $batch->batch_code }} ({{ $batch->current_quantity }} avail, exp: {{ $batch->expires_at->format('M d') }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($batches->count() > 0)
                                        <button 
                                            wire:click="autoSelectBatch({{ $categoryId }})"
                                            class="px-2 py-1 text-xs font-medium text-amber-700 bg-amber-100 rounded hover:bg-amber-200 dark:bg-amber-900/50 dark:text-amber-400"
                                            title="Auto-select oldest batch (FIFO)"
                                        >
                                            FIFO
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if($selectedBatch)
                                    {{ number_format($selectedBatch->current_quantity) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="number" 
                                    wire:model.blur="dispatchItems.{{ $categoryId }}.quantity"
                                    min="0"
                                    @if(!$item['batch_id']) disabled @endif
                                    class="w-24 px-3 py-2 text-center border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-50"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <td colspan="3" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">
                            Total to Dispatch:
                        </td>
                        <td class="px-4 py-3 text-center text-lg font-bold text-amber-600 dark:text-amber-400">
                            {{ number_format($this->totalQuantity) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Notes --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
        <textarea 
            wire:model="notes" 
            id="notes" 
            rows="3" 
            placeholder="Add any delivery notes..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        ></textarea>
    </div>

    {{-- Submit Button --}}
    <div class="flex justify-end">
        <button 
            wire:click="showConfirm"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-75 cursor-not-allowed"
            @if($this->totalQuantity <= 0 || !$shopId) disabled @endif
            class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Dispatch Delivery
        </button>
    </div>

    {{-- Confirmation Modal --}}
    @if($showConfirmation)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelConfirm"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/50 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    Confirm Dispatch
                                </h3>
                                <div class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Destination:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $this->shops->find($shopId)?->name }}
                                        </span>
                                    </div>
                                    <hr class="border-gray-200 dark:border-gray-600">
                                    @foreach($this->itemsWithData as $data)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{ $data['name'] }}:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($data['quantity']) }}</span>
                                        </div>
                                    @endforeach
                                    <hr class="border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-between text-base font-semibold">
                                        <span class="text-gray-900 dark:text-white">Total:</span>
                                        <span class="text-amber-600 dark:text-amber-400">{{ number_format($this->totalQuantity) }} eggs</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="submit"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="submit">Confirm Dispatch</span>
                            <span wire:loading wire:target="submit">Dispatching...</span>
                        </button>
                        <button 
                            wire:click="cancelConfirm"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
