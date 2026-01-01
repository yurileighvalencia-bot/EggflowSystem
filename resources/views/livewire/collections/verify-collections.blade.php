<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Verify Collections</h1>
            <p class="text-gray-600 dark:text-gray-400">Manager verification workflow for egg collections</p>
        </div>
        @if($totalSelected > 0)
            <button wire:click="confirmVerify" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Verify Selected ({{ $totalSelected }})
            </button>
        @endif
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

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            {{-- Farm Filter --}}
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Farm</label>
                <select wire:model.live="farmId" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Farms</option>
                    @foreach($this->farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Filter --}}
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Collection Date</label>
                <input type="date" wire:model.live="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    @if($this->summary->count() > 0)
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($this->summary as $item)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $item->eggCategory?->code }}</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($item->total_quantity) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->record_count }} record(s)</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Selection Controls --}}
    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg px-4 py-2">
        <div class="flex items-center space-x-4">
            <button wire:click="selectAll" class="text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400">
                Select All
            </button>
            <button wire:click="deselectAll" class="text-sm text-gray-600 hover:text-gray-700 dark:text-gray-400">
                Deselect All
            </button>
        </div>
        <span class="text-sm text-gray-600 dark:text-gray-400">
            {{ $totalSelected }} selected
        </span>
    </div>

    {{-- Collections Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">
                            <span class="sr-only">Select</span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date/Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Farm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Batch</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Collected By</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->collections as $collection)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ in_array($collection->id, $selectedCollections) ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}">
                            <td class="px-4 py-3">
                                <input 
                                    type="checkbox" 
                                    wire:click="toggleSelection({{ $collection->id }})"
                                    @if(in_array($collection->id, $selectedCollections)) checked @endif
                                    class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700"
                                >
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $collection->collection_date->format('M d, Y') }}
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $collection->collection_time?->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $collection->farm?->name }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded dark:bg-amber-900/50 dark:text-amber-400">
                                    {{ $collection->eggCategory?->name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                {{ $collection->batch?->batch_code }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white">
                                {{ number_format($collection->quantity) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $collection->staff?->name }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button 
                                    wire:click="verifySingle({{ $collection->id }})"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded hover:bg-green-200 dark:bg-green-900/50 dark:text-green-400 dark:hover:bg-green-900"
                                >
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Verify
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="mt-2 text-lg font-medium">All caught up!</p>
                                <p class="text-sm">No pending collections to verify.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->collections->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->collections->links() }}
            </div>
        @endif
    </div>

    {{-- Verify Confirmation Modal --}}
    @if($showVerifyModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelVerify"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/50 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Confirm Verification
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        You are about to verify <strong>{{ $totalSelected }}</strong> collection record(s). 
                                        This action confirms that the recorded quantities are accurate.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            wire:click="verifySelected"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="verifySelected">Verify All</span>
                            <span wire:loading wire:target="verifySelected">Verifying...</span>
                        </button>
                        <button 
                            wire:click="cancelVerify"
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
