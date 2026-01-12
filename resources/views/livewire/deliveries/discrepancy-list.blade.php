<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Delivery Discrepancies</h1>
            <p class="text-gray-600 dark:text-gray-400">Investigate and resolve delivery issues</p>
        </div>
        <a href="{{ route('deliveries.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
            Back to Deliveries
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 cursor-pointer hover:ring-2 hover:ring-red-500" wire:click="$set('status', 'pending')">
            <p class="text-xs font-medium text-red-500 uppercase">Pending</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->stats['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 cursor-pointer hover:ring-2 hover:ring-yellow-500" wire:click="$set('status', 'investigating')">
            <p class="text-xs font-medium text-yellow-500 uppercase">Investigating</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->stats['investigating'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 cursor-pointer hover:ring-2 hover:ring-green-500" wire:click="$set('status', 'resolved')">
            <p class="text-xs font-medium text-green-500 uppercase">Resolved</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->stats['resolved'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-orange-500 uppercase">Total Missing</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($this->stats['total_missing']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by shop, reporter, or notes..." class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            {{-- Shop Filter --}}
            @if(!auth()->user()?->isShopStaff())
            <div>
                <select wire:model.live="shopId" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Shops</option>
                    @foreach($this->shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Status Filter --}}
            <div>
                <select wire:model.live="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @foreach($this->statusOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="flex gap-2">
                <input type="date" wire:model.live="dateFrom" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <input type="date" wire:model.live="dateTo" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button wire:click="resetFilters" class="text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400">
                Reset Filters
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                        <th wire:click="sortBy('reported_at')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                            Reported
                            @if($sortBy === 'reported_at')
                                <span class="ml-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Delivery / Shop</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantities</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reporter</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->discrepancies as $discrepancy)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">
                                #{{ $discrepancy->id }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $discrepancy->reported_at?->format('M d, Y') }}
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $discrepancy->reported_at?->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('deliveries.show', $discrepancy->delivery_id) }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 font-medium">
                                    Delivery #{{ $discrepancy->delivery_id }}
                                </a>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $discrepancy->delivery?->shop?->name }}
                                </span>
                                @if($discrepancy->deliveryItem)
                                    <span class="inline-flex items-center px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded mt-1">
                                        {{ $discrepancy->deliveryItem->eggCategory?->name }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <div class="flex items-center justify-center space-x-3">
                                    <div class="text-center">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">Sent</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $discrepancy->qty_sent }}</span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                    <div class="text-center">
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">Received</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $discrepancy->qty_received }}</span>
                                    </div>
                                    @if($discrepancy->qty_rejected > 0)
                                        <div class="text-center">
                                            <span class="block text-xs text-orange-500">Rejected</span>
                                            <span class="font-medium text-orange-600 dark:text-orange-400">{{ $discrepancy->qty_rejected }}</span>
                                        </div>
                                    @endif
                                    @if($discrepancy->qty_missing > 0)
                                        <div class="text-center">
                                            <span class="block text-xs text-red-500">Missing</span>
                                            <span class="font-bold text-red-600 dark:text-red-400">{{ $discrepancy->qty_missing }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $this->getStatusClass($discrepancy) }}">
                                    {{ $this->getStatusLabel($discrepancy) }}
                                </span>
                                @if($discrepancy->investigator)
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        by {{ $discrepancy->investigator->name }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $discrepancy->reporter?->name }}
                                @if($discrepancy->notes)
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 truncate max-w-[150px]" title="{{ $discrepancy->notes }}">
                                        {{ $discrepancy->notes }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    @if(!$discrepancy->resolution)
                                        @if(!$discrepancy->investigated_by)
                                            {{-- Start Investigation --}}
                                            <button 
                                                wire:click="startInvestigation({{ $discrepancy->id }})"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50"
                                                wire:target="startInvestigation({{ $discrepancy->id }})"
                                                class="p-1 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300" 
                                                title="Start Investigation"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                            </button>
                                        @endif
                                        
                                        @if($discrepancy->investigated_by === auth()->id() || auth()->user()?->hasRole('Manager'))
                                            {{-- Resolve --}}
                                            <button 
                                                wire:click="openInvestigateModal({{ $discrepancy->id }})"
                                                class="p-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" 
                                                title="Resolve Discrepancy"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                        @endif
                                    @else
                                        {{-- View Details (resolved) --}}
                                        <span class="p-1 text-gray-400" title="Resolved">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                No discrepancies found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->discrepancies->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->discrepancies->links() }}
            </div>
        @endif
    </div>

    {{-- Investigation Modal --}}
    @if($showInvestigateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div 
                    class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" 
                    wire:click="closeInvestigateModal"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Resolve Discrepancy
                                </h3>
                                <div class="mt-4 space-y-4">
                                    {{-- Resolution --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Resolution <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            wire:model="resolution"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        >
                                            <option value="">Select resolution...</option>
                                            @foreach($this->resolutions as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('resolution')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Notes --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Investigation Notes
                                        </label>
                                        <textarea 
                                            wire:model="investigationNotes"
                                            rows="3"
                                            placeholder="Document your findings..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            type="button"
                            wire:click="submitInvestigation"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed"
                            wire:target="submitInvestigation"
                            class="w-full inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="submitInvestigation">Resolve</span>
                            <span wire:loading wire:target="submitInvestigation">
                                <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Resolving...
                            </span>
                        </button>
                        <button 
                            type="button"
                            wire:click="closeInvestigateModal"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
