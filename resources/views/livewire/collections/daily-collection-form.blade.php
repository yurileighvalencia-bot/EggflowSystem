<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Daily Egg Collection</h1>
            <p class="text-gray-600 dark:text-gray-400">Record egg collections by category</p>
        </div>
        <button wire:click="resetForm" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Reset Form
        </button>
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

    {{-- Collection Details --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Collection Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Farm Selection --}}
            <div>
                <label for="farmId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Farm</label>
                <select wire:model.live="farmId" id="farmId" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @foreach($this->farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
                @error('farmId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Collection Date --}}
            <div>
                <label for="collectionDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Collection Date</label>
                <input type="date" wire:model="collectionDate" id="collectionDate" max="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                @error('collectionDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Collection Time --}}
            <div>
                <label for="collectionTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Collection Time</label>
                <input type="time" wire:model="collectionTime" id="collectionTime" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                @error('collectionTime') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    {{-- Collection Matrix --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Egg Collection by Category</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quick Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($collectionData as $categoryId => $data)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $data['name'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                <span class="px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded dark:bg-amber-900/50 dark:text-amber-400">
                                    {{ $data['code'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center">
                                    <input 
                                        type="number" 
                                        wire:model.blur="collectionData.{{ $categoryId }}.quantity"
                                        min="0" 
                                        class="w-24 px-3 py-2 text-center border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    >
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center space-x-2">
                                    <button 
                                        wire:click="updateQuantity({{ $categoryId }}, {{ ($data['quantity'] ?? 0) + 30 }})"
                                        class="px-2 py-1 text-xs font-medium text-amber-700 bg-amber-100 rounded hover:bg-amber-200 dark:bg-amber-900/50 dark:text-amber-400 dark:hover:bg-amber-900"
                                    >
                                        +30
                                    </button>
                                    <button 
                                        wire:click="updateQuantity({{ $categoryId }}, {{ ($data['quantity'] ?? 0) + 100 }})"
                                        class="px-2 py-1 text-xs font-medium text-amber-700 bg-amber-100 rounded hover:bg-amber-200 dark:bg-amber-900/50 dark:text-amber-400 dark:hover:bg-amber-900"
                                    >
                                        +100
                                    </button>
                                    <button 
                                        wire:click="updateQuantity({{ $categoryId }}, 0)"
                                        class="px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">Total</td>
                        <td class="px-4 py-3 text-center text-lg font-bold text-amber-600 dark:text-amber-400">
                            {{ number_format($this->totalQuantity) }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">eggs</td>
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
            placeholder="Add any notes about this collection..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
        ></textarea>
    </div>

    {{-- Submit Button --}}
    <div class="flex justify-end">
        <button 
            wire:click="showConfirm"
            @if($this->totalQuantity <= 0) disabled @endif
            class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Submit Collection
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Confirm Collection Submission
                                </h3>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        You are about to record the following collections:
                                    </p>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Farm:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $this->farm?->name }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Date:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($collectionDate)->format('M d, Y') }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Time:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $collectionTime }}</span>
                                        </div>
                                        <hr class="border-gray-200 dark:border-gray-600">
                                        @foreach($this->categoriesWithData as $data)
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">{{ $data['name'] }}:</span>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($data['quantity']) }} eggs</span>
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
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="submit"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="submit">Confirm & Save</span>
                            <span wire:loading wire:target="submit">Saving...</span>
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
