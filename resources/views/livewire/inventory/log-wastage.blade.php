<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Log Wastage</h1>
            <p class="text-gray-600 dark:text-gray-400">Record damaged, spoiled, or lost eggs</p>
        </div>
        <a href="{{ route('inventory.wastage-history') }}" 
            class="px-4 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            View History
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if($logged && $lastLog)
        {{-- Success View --}}
        <div class="max-w-md mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Wastage Logged</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Record has been saved</p>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6 text-left space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Quantity</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $lastLog->quantity }} eggs</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Source</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ WastageLog::SOURCES[$lastLog->source] ?? $lastLog->source }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Logged At</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $lastLog->logged_at->format('M d, Y g:i A') }}</span>
                    </div>
                </div>

                <button wire:click="resetForm"
                    class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition-colors">
                    Log Another
                </button>
            </div>
        </div>
    @else
        {{-- Wastage Form --}}
        <div class="max-w-2xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="space-y-6">
                    {{-- Shop --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shop / Location</label>
                        <select 
                            wire:model.live="shopId"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            @foreach($this->shops as $shop)
                                <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                            @endforeach
                        </select>
                        @error('shopId') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Egg Category</label>
                        <select 
                            wire:model.live="categoryId"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            <option value="">Select category...</option>
                            @foreach($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->code }})</option>
                            @endforeach
                        </select>
                        @error('categoryId') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Batch (Optional) --}}
                    @if($categoryId && $this->batches->isNotEmpty())
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Batch (Optional - leave blank for FIFO)
                            </label>
                            <select 
                                wire:model.live="batchId"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="">Any batch (FIFO)</option>
                                @foreach($this->batches as $batch)
                                    <option value="{{ $batch->id }}">
                                        {{ $batch->batch_code }} - {{ $batch->collection_date->format('M d') }} ({{ $batch->available }} available)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Source --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wastage Source</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach($this->sourceOptions as $value => $label)
                                <button 
                                    type="button"
                                    wire:click="$set('source', '{{ $value }}')"
                                    class="py-2 px-3 text-sm border-2 rounded-lg transition-colors {{ $source === $value 
                                        ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' 
                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 text-gray-700 dark:text-gray-300' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        @error('source') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Quantity --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Quantity
                            @if($this->maxQuantity > 0)
                                <span class="text-gray-400 font-normal">(max: {{ $this->maxQuantity }})</span>
                            @endif
                        </label>
                        <div class="flex items-center gap-4">
                            <input 
                                type="number"
                                wire:model="quantity"
                                min="1"
                                max="{{ $this->maxQuantity }}"
                                class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="Enter quantity..."
                            >
                            {{-- Quick buttons --}}
                            <div class="flex gap-1">
                                @foreach([10, 30, 100] as $qty)
                                    @if($qty <= $this->maxQuantity)
                                        <button 
                                            type="button"
                                            wire:click="$set('quantity', {{ $qty }})"
                                            class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
                                        >
                                            {{ $qty }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @error('quantity') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Reason --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason / Notes</label>
                        <textarea 
                            wire:model="reason"
                            rows="3"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Describe what happened..."
                        ></textarea>
                        @error('reason') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="pt-4">
                        <button 
                            wire:click="confirmLog"
                            @if(!$categoryId || $quantity < 1 || !$reason) disabled @endif
                            class="w-full py-3 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
                        >
                            Log Wastage
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Confirmation Modal --}}
    @if($showConfirmModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="$set('showConfirmModal', false)"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirm Wastage Log</h3>
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        This will permanently remove <strong>{{ $quantity }}</strong> eggs from inventory. This action cannot be undone.
                    </p>

                    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-red-700 dark:text-red-300">Source:</span>
                                <span class="font-medium text-red-800 dark:text-red-200">{{ WastageLog::SOURCES[$source] ?? $source }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-red-700 dark:text-red-300">Quantity:</span>
                                <span class="font-medium text-red-800 dark:text-red-200">{{ $quantity }} eggs</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="$set('showConfirmModal', false)"
                            class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button wire:click="logWastage"
                            class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
                            Confirm Log
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
