<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New Restock Request</h3>
        <button 
            wire:click="$parent.closeCreateModal"
            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <form wire:submit="save" class="space-y-4">
        {{-- Shop Selection --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shop</label>
            <select 
                wire:model.live="shopId"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('shopId') border-red-500 @enderror"
                @if(auth()->user()?->isShopStaff()) disabled @endif
            >
                <option value="">Select Shop</option>
                @foreach($this->shops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                @endforeach
            </select>
            @error('shopId')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Category Selection --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Egg Category</label>
            <select 
                wire:model.live="categoryId"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('categoryId') border-red-500 @enderror"
            >
                <option value="">Select Category</option>
                @foreach($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->code }})</option>
                @endforeach
            </select>
            @error('categoryId')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            
            @if($this->hasActiveRequest)
                <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    An active request already exists for this category.
                </p>
            @endif
        </div>

        {{-- Quantity --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity Requested</label>
            <input 
                type="number" 
                wire:model="quantity"
                min="1"
                placeholder="Enter quantity"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('quantity') border-red-500 @enderror"
            >
            @error('quantity')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Notes --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (Optional)</label>
            <textarea 
                wire:model="notes"
                rows="3"
                placeholder="Any additional notes..."
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('notes') border-red-500 @enderror"
            ></textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button 
                type="button"
                wire:click="$parent.closeCreateModal"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
                Cancel
            </button>
            <button 
                type="submit"
                class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($this->hasActiveRequest) disabled @endif
            >
                <span wire:loading.remove wire:target="save">Create Request</span>
                <span wire:loading wire:target="save">Creating...</span>
            </button>
        </div>
    </form>
</div>
