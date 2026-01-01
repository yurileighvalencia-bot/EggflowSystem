<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Reservation</h1>
            <p class="text-gray-600 dark:text-gray-400">Reserve eggs for customer pickup</p>
        </div>
        <a href="{{ route('reservations.index') }}" 
            class="px-4 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            Back to List
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Product Selection --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Reservation Details --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reservation Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Shop --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shop</label>
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

                    {{-- Customer --}}
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Customer (Optional)</label>
                        <div class="relative">
                            <input 
                                type="text"
                                wire:model.live.debounce.300ms="customerSearch"
                                wire:focus="$set('showCustomerDropdown', true)"
                                placeholder="Search by name, email, or phone..."
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            @if($customerId)
                                <button wire:click="clearCustomer" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        
                        {{-- Customer Dropdown --}}
                        @if($showCustomerDropdown && $this->customers->isNotEmpty())
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @foreach($this->customers as $customer)
                                    <button 
                                        wire:click="selectCustomer({{ $customer->id }})"
                                        class="w-full px-4 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $customer->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->email }} • {{ $customer->phone }}</div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Pickup Date --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pickup Date</label>
                        <input 
                            type="date"
                            wire:model="pickupDate"
                            min="{{ now()->format('Y-m-d') }}"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                        @error('pickupDate') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Pickup Time --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pickup Time (Optional)</label>
                        <input 
                            type="time"
                            wire:model="pickupTime"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                </div>

                {{-- Notes --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (Optional)</label>
                    <textarea 
                        wire:model="notes"
                        rows="2"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Special instructions..."
                    ></textarea>
                </div>
            </div>

            {{-- Product Selection --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Products</h2>
                
                @if($this->availableCategories->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>No products available for reservation</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($this->availableCategories as $category)
                            <button 
                                wire:click="addItem({{ $category->id }})"
                                @if($category->reservable_stock <= 0) disabled @endif
                                class="p-4 border-2 rounded-lg transition-all text-left {{ $category->reservable_stock > 0 ? 'border-gray-200 dark:border-gray-600 hover:border-amber-400 dark:hover:border-amber-600' : 'border-gray-100 dark:border-gray-700 opacity-50 cursor-not-allowed' }}"
                            >
                                <div class="text-2xl mb-1">🥚</div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $category->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $category->code }}</p>
                                <p class="text-lg font-bold text-amber-600 dark:text-amber-400 mt-1">
                                    ₱{{ number_format($category->default_price, 2) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($category->reservable_stock) }} available
                                </p>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Cart --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 sticky top-6">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Reservation Items</h2>
                </div>

                {{-- Items List --}}
                <div class="p-4 space-y-3 max-h-80 overflow-y-auto">
                    @forelse($items as $index => $item)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">₱{{ number_format($item['price'], 2) }} each</p>
                                </div>
                                <button wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button 
                                        wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                        class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 flex items-center justify-center"
                                    >
                                        <span class="text-lg font-bold">−</span>
                                    </button>
                                    <span class="w-10 text-center font-medium text-gray-900 dark:text-white">{{ $item['quantity'] }}</span>
                                    <button 
                                        wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                        class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 flex items-center justify-center"
                                    >
                                        <span class="text-lg font-bold">+</span>
                                    </button>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    ₱{{ number_format($item['subtotal'], 2) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="mt-2 text-sm">No items added</p>
                        </div>
                    @endforelse
                </div>

                @error('items')
                    <div class="px-4 pb-2">
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    </div>
                @enderror

                {{-- Totals --}}
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Subtotal</span>
                        <span>₱{{ number_format($this->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span>Total</span>
                        <span>₱{{ number_format($this->total, 2) }}</span>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <button 
                        wire:click="createReservation"
                        @if(empty($items)) disabled @endif
                        class="w-full py-3 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
                    >
                        Create Reservation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
