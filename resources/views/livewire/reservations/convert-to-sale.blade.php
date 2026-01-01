<div class="space-y-6">
    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($completedSale)
        {{-- Sale Complete View --}}
        <div class="max-w-md mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Sale Complete!</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $completedSale->sale_code }}</p>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        ₱{{ number_format($completedSale->total, 2) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ ucfirst($completedSale->payment_method) }} Payment
                    </div>
                    @if($paymentMethod === 'cash' && $this->changeDue > 0)
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Change Due:</span>
                            <span class="ml-2 text-lg font-bold text-amber-600 dark:text-amber-400">
                                ₱{{ number_format($this->changeDue, 2) }}
                            </span>
                        </div>
                    @endif
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('print.receipt', $completedSale) }}" target="_blank"
                        class="flex-1 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </a>
                    <button wire:click="backToList"
                        class="flex-1 py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition-colors">
                        Done
                    </button>
                </div>
            </div>
        </div>
    @else
        {{-- Convert to Sale Form --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pickup: {{ $reservation->reservation_code }}</h1>
                <p class="text-gray-600 dark:text-gray-400">Convert reservation to sale</p>
            </div>
            <a href="{{ route('reservations.index') }}" 
                class="px-4 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                Back to List
            </a>
        </div>

        {{-- No Active Shift Warning --}}
        @if(!$this->activeShift)
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-yellow-800 dark:text-yellow-200">No Active Shift</p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">Please open a shift in POS before processing this pickup.</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Left: Reservation Details --}}
            <div class="space-y-6">
                {{-- Customer Info --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer Details</h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Customer</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $reservation->customer->name ?? 'Walk-in' }}</span>
                        </div>
                        @if($reservation->customer?->phone)
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Phone</span>
                                <span class="text-gray-900 dark:text-white">{{ $reservation->customer->phone }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Pickup Date</span>
                            <span class="text-gray-900 dark:text-white">{{ $reservation->pickup_date->format('M d, Y') }}</span>
                        </div>
                        @if($reservation->pickup_time)
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Pickup Time</span>
                                <span class="text-gray-900 dark:text-white">{{ $reservation->pickup_time->format('g:i A') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Reserved Items --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reserved Items</h2>
                    
                    <div class="space-y-3">
                        @foreach($reservation->items as $item)
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $item->eggCategory->name ?? 'Item' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->quantity }} × ₱{{ number_format($item->unit_price, 2) }}
                                    </div>
                                </div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    ₱{{ number_format($item->line_total, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right: Payment --}}
            <div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment</h2>

                    {{-- Summary --}}
                    <div class="space-y-2 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                            <span class="text-gray-900 dark:text-white">₱{{ number_format($reservation->subtotal, 2) }}</span>
                        </div>
                        @if($reservation->tax > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Tax</span>
                                <span class="text-gray-900 dark:text-white">₱{{ number_format($reservation->tax, 2) }}</span>
                            </div>
                        @endif
                        @if($discount > 0)
                            <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                                <span>Discount</span>
                                <span>-₱{{ number_format($discount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-200 dark:border-gray-600">
                            <span class="text-gray-900 dark:text-white">Total</span>
                            <span class="text-amber-600 dark:text-amber-400">₱{{ number_format($this->total, 2) }}</span>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(['cash' => '💵 Cash', 'card' => '💳 Card', 'transfer' => '🏦 Transfer', 'other' => '📝 Other'] as $method => $label)
                                <button wire:click="$set('paymentMethod', '{{ $method }}')"
                                    class="py-2 px-4 rounded-lg border-2 transition-colors {{ $paymentMethod === $method 
                                        ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' 
                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Amount Tendered (for cash) --}}
                    @if($paymentMethod === 'cash')
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount Tendered</label>
                            <input type="number" wire:model.live="amountTendered" step="0.01" min="{{ $this->total }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-lg font-semibold text-right">
                        </div>
                        @if($this->changeDue > 0)
                            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Change Due:</span>
                                <span class="text-xl font-bold text-green-600 dark:text-green-400 ml-2">₱{{ number_format($this->changeDue, 2) }}</span>
                            </div>
                        @endif
                    @endif

                    {{-- Discount --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount (Optional)</label>
                        <input type="number" wire:model.live="discount" step="0.01" min="0" max="{{ $reservation->total }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    {{-- Complete Button --}}
                    <button 
                        wire:click="openConfirmModal"
                        @if(!$this->activeShift || ($paymentMethod === 'cash' && $amountTendered < $this->total)) disabled @endif
                        class="w-full py-3 bg-green-500 hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
                    >
                        Complete Pickup
                    </button>
                </div>
            </div>
        </div>

        {{-- Confirm Modal --}}
        @if($showConfirmModal)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="$set('showConfirmModal', false)"></div>
                    
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Confirm Sale</h3>
                        
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Complete pickup for <strong>{{ $reservation->reservation_code }}</strong>?
                        </p>

                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                            <div class="flex justify-between font-bold">
                                <span>Total</span>
                                <span>₱{{ number_format($this->total, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mt-1">
                                <span>Payment</span>
                                <span>{{ ucfirst($paymentMethod) }}</span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button wire:click="$set('showConfirmModal', false)"
                                class="flex-1 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button wire:click="convertToSale"
                                class="flex-1 py-2 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
