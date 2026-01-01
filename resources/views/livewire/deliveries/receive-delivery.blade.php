<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Receive Delivery #{{ $delivery->id }}</h1>
            <p class="text-gray-600 dark:text-gray-400">
                From: {{ $delivery->dispatcher?->name }} • To: {{ $delivery->shop?->name }}
            </p>
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

    {{-- Delivery Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Delivery Information</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dispatched</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $delivery->dispatched_at?->format('M d, Y H:i') }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dispatcher</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $delivery->dispatcher?->name }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</p>
                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400">
                    {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                </span>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Items</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($this->totalDispatched) }} eggs</p>
            </div>
        </div>
    </div>

    {{-- Quick Action --}}
    <div class="flex gap-4">
        <button 
            wire:click="confirmAll"
            class="flex-1 inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Confirm All Received (No Issues)
        </button>
    </div>

    {{-- Items Table (Discrepancy Form) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Or Report Discrepancy
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Batch</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dispatched</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Received</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rejected</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rejection Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($delivery->items as $item)
                        @php $data = $receiveData[$item->id]; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $data['rejected'] > 0 ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $item->eggCategory?->name }}</span>
                                <span class="ml-2 px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded dark:bg-amber-900/50 dark:text-amber-400">
                                    {{ $item->eggCategory?->code }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                {{ $item->batch?->batch_code }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($data['dispatched']) }}
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="number" 
                                    wire:model.blur="receiveData.{{ $item->id }}.received"
                                    wire:change="updateReceived({{ $item->id }}, $event.target.value)"
                                    min="0"
                                    max="{{ $data['dispatched'] }}"
                                    class="w-20 px-3 py-2 text-center border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm font-medium {{ $data['rejected'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ number_format($data['rejected']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($data['rejected'] > 0)
                                    <input 
                                        type="text" 
                                        wire:model="receiveData.{{ $item->id }}.reason"
                                        placeholder="Reason for rejection..."
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    >
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <td colspan="3" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">
                            Totals:
                        </td>
                        <td class="px-4 py-3 text-center text-lg font-bold text-green-600 dark:text-green-400">
                            {{ number_format($this->totalReceived) }}
                        </td>
                        <td class="px-4 py-3 text-center text-lg font-bold text-red-600 dark:text-red-400">
                            {{ number_format($this->totalRejected) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Notes --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Receiving Notes (Optional)</label>
        <textarea 
            wire:model="notes" 
            id="notes" 
            rows="3" 
            placeholder="Add any notes about this delivery..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        ></textarea>
    </div>

    {{-- Submit with Discrepancy --}}
    @if($this->hasAnyDiscrepancy)
        <div class="flex justify-end">
            <button 
                wire:click="openDiscrepancyForm"
                class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Submit with Discrepancy
            </button>
        </div>
    @endif

    {{-- Confirmation Modal --}}
    @if($showConfirmation)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelConfirm"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full {{ $this->hasAnyDiscrepancy ? 'bg-orange-100 dark:bg-orange-900/50' : 'bg-green-100 dark:bg-green-900/50' }} sm:mx-0 sm:h-10 sm:w-10">
                                @if($this->hasAnyDiscrepancy)
                                    <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    {{ $this->hasAnyDiscrepancy ? 'Confirm with Discrepancy' : 'Confirm Receipt' }}
                                </h3>
                                <div class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Dispatched:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($this->totalDispatched) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Received:</span>
                                        <span class="font-medium text-green-600 dark:text-green-400">{{ number_format($this->totalReceived) }}</span>
                                    </div>
                                    @if($this->totalRejected > 0)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Rejected:</span>
                                            <span class="font-medium text-red-600 dark:text-red-400">{{ number_format($this->totalRejected) }}</span>
                                        </div>
                                    @endif
                                </div>
                                @if($this->hasAnyDiscrepancy)
                                    <p class="mt-4 text-sm text-orange-600 dark:text-orange-400">
                                        ⚠️ This will create a discrepancy report and log wastage for rejected items.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="submit"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 {{ $this->hasAnyDiscrepancy ? 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500' : 'bg-green-600 hover:bg-green-700 focus:ring-green-500' }} text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="submit">Confirm</span>
                            <span wire:loading wire:target="submit">Processing...</span>
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
