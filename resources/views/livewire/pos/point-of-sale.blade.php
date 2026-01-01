<div class="h-full">
    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="fixed top-4 right-4 z-50 p-4 bg-red-100 dark:bg-red-900/50 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 rounded-lg shadow-lg">
            {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="fixed top-4 right-4 z-50 p-4 bg-green-100 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg shadow-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex h-full gap-6">
        {{-- Left Panel: Category Selection --}}
        <div class="flex-1 flex flex-col">
            <div class="mb-4 flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Point of Sale</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $this->shop?->name ?? 'Select a shop' }}</p>
                </div>
                
                {{-- Shift Controls --}}
                <div class="flex items-center gap-2">
                    @if($this->activeShift)
                        {{-- Shift Active Indicator --}}
                        <div class="flex items-center gap-3 px-4 py-2 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                <span class="text-sm font-medium text-green-700 dark:text-green-300">Shift Active</span>
                            </div>
                            <span class="text-xs text-green-600 dark:text-green-400">
                                Since {{ $this->activeShift->opened_at->format('g:i A') }}
                            </span>
                        </div>

                        {{-- Cash Adjustment Button --}}
                        <button 
                            wire:click="$dispatch('openCashAdjustment')"
                            class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-1"
                            title="Cash In/Out"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Adjust</span>
                        </button>

                        {{-- Close Shift Button --}}
                        <button 
                            wire:click="$dispatch('openCloseRegister')"
                            class="px-3 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-800/30 transition-colors flex items-center gap-1"
                            title="Close Shift"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Close</span>
                        </button>
                    @else
                        {{-- Open Shift Button --}}
                        <button 
                            wire:click="$dispatch('openRegister')"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Open Register
                        </button>
                    @endif
                </div>
            </div>

            {{-- No Active Shift Warning --}}
            @if(!$this->activeShift)
                <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-yellow-800 dark:text-yellow-200">No Active Shift</p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">You must open a register shift before processing sales.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Category Grid --}}
            <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 overflow-y-auto">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Products</h2>
                
                @if($this->availableCategories->isEmpty())
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="mt-2">No products available</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($this->availableCategories as $category)
                            <button wire:click="addToCart({{ $category->id }})"
                                class="p-4 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-2 border-amber-200 dark:border-amber-800 rounded-xl hover:border-amber-400 dark:hover:border-amber-600 transition-all hover:shadow-md group">
                                <div class="text-center">
                                    <div class="text-3xl mb-2">🥚</div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                        {{ $category->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $category->code }}</p>
                                    <p class="text-lg font-bold text-amber-600 dark:text-amber-400 mt-1">
                                        ₱{{ number_format($category->default_price, 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ number_format($category->available_stock ?? 0) }} in stock
                                    </p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Panel: Cart --}}
        <div class="w-96 flex flex-col">
            <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col">
                {{-- Cart Header --}}
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cart</h2>
                    @if(!empty($cart))
                        <button wire:click="clearCart" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400">
                            Clear All
                        </button>
                    @endif
                </div>

                {{-- Cart Items --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    @forelse($cart as $categoryId => $item)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">₱{{ number_format($item['price'], 2) }} each</p>
                                </div>
                                <button wire:click="removeFromCart({{ $categoryId }})" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button wire:click="updateQuantity({{ $categoryId }}, {{ $item['quantity'] - 1 }})"
                                        class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 flex items-center justify-center">
                                        <span class="text-lg font-bold">−</span>
                                    </button>
                                    <input type="number" 
                                        wire:change="updateQuantity({{ $categoryId }}, $event.target.value)"
                                        value="{{ $item['quantity'] }}"
                                        min="1"
                                        class="w-16 text-center rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <button wire:click="updateQuantity({{ $categoryId }}, {{ $item['quantity'] + 1 }})"
                                        class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 flex items-center justify-center">
                                        <span class="text-lg font-bold">+</span>
                                    </button>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    ₱{{ number_format($item['subtotal'], 2) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="mt-2">Cart is empty</p>
                            <p class="text-sm">Click on products to add them</p>
                        </div>
                    @endforelse
                </div>

                {{-- Cart Totals --}}
                <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Subtotal</span>
                        <span>₱{{ number_format($this->subtotal, 2) }}</span>
                    </div>
                    @if($this->tax > 0)
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Tax</span>
                            <span>₱{{ number_format($this->tax, 2) }}</span>
                        </div>
                    @endif
                    @if($discount > 0)
                        <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                            <span>Discount</span>
                            <span>-₱{{ number_format($discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span>Total</span>
                        <span>₱{{ number_format($this->total, 2) }}</span>
                    </div>
                </div>

                {{-- Checkout Button --}}
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="openCheckout"
                        @if(empty($cart)) disabled @endif
                        class="w-full py-3 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-colors">
                        Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Checkout Modal --}}
    @if($showCheckoutModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showCheckoutModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Complete Payment</h3>
                
                <div class="space-y-4">
                    {{-- Total --}}
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Amount</p>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">₱{{ number_format($this->total, 2) }}</p>
                    </div>

                    {{-- Payment Method --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(['cash' => '💵 Cash', 'card' => '💳 Card', 'transfer' => '🏦 Transfer', 'other' => '📝 Other'] as $method => $label)
                                <button wire:click="$set('paymentMethod', '{{ $method }}')"
                                    class="py-2 px-4 rounded-lg border-2 transition-colors
                                        {{ $paymentMethod === $method 
                                            ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' 
                                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Amount Tendered (for cash) --}}
                    @if($paymentMethod === 'cash')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount Tendered</label>
                            <input type="number" wire:model.live="amountTendered" step="0.01" min="{{ $this->total }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-lg font-semibold text-right">
                        </div>
                        @if($this->changeDue > 0)
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Change Due</p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">₱{{ number_format($this->changeDue, 2) }}</p>
                            </div>
                        @endif
                    @endif

                    {{-- Quick Cash Buttons --}}
                    @if($paymentMethod === 'cash')
                        <div class="grid grid-cols-4 gap-2">
                            @foreach([20, 50, 100, 200, 500, 1000] as $amount)
                                <button wire:click="$set('amountTendered', {{ $amount }})"
                                    class="py-2 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                    ₱{{ $amount }}
                                </button>
                            @endforeach
                            <button wire:click="$set('amountTendered', {{ $this->total }})"
                                class="py-2 text-sm bg-amber-100 dark:bg-amber-900/30 hover:bg-amber-200 dark:hover:bg-amber-800/30 rounded-lg transition-colors col-span-2">
                                Exact
                            </button>
                        </div>
                    @endif

                    {{-- Discount --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount (Optional)</label>
                        <input type="number" wire:model.live="discount" step="0.01" min="0"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button wire:click="$set('showCheckoutModal', false)"
                        class="flex-1 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="processSale"
                        @if($paymentMethod === 'cash' && $amountTendered < $this->total) disabled @endif
                        class="flex-1 py-3 bg-green-500 hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-colors">
                        Complete Sale
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Receipt Modal --}}
    @if($showReceiptModal && $lastSale)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full mx-4 p-6">
                <div class="text-center mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full mb-3">
                        <svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Sale Complete!</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $lastSale->sale_code }}</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-4">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span>₱{{ number_format($lastSale->subtotal, 2) }}</span>
                        </div>
                        @if($lastSale->tax > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Tax</span>
                                <span>₱{{ number_format($lastSale->tax, 2) }}</span>
                            </div>
                        @endif
                        @if($lastSale->discount > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-₱{{ number_format($lastSale->discount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-200 dark:border-gray-600">
                            <span>Total</span>
                            <span>₱{{ number_format($lastSale->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <p>{{ $lastSale->sold_at->format('M d, Y h:i A') }}</p>
                    <p>Payment: {{ ucfirst($lastSale->payment_method) }}</p>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('print.receipt', $lastSale) }}" target="_blank"
                        class="flex-1 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </a>
                    <button wire:click="newSale"
                        class="flex-1 py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl transition-colors">
                        New Sale
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Shift Management Modals --}}
    <livewire:p-o-s.open-register-modal />
    <livewire:p-o-s.cash-adjustment-modal />
    <livewire:p-o-s.close-register-modal />
</div>
