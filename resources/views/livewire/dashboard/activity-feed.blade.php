<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Activity Feed</h1>
            <p class="text-gray-600 dark:text-gray-400">Recent actions across the system</p>
        </div>
    </div>

    {{-- Today's Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->recentStats['sales_today'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sales Today</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->recentStats['reservations_today'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Reservations Today</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->recentStats['deliveries_today'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Deliveries Today</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->recentStats['collections_today'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Collections Today</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex gap-2 flex-wrap">
        @foreach($this->getFilterTypes() as $key => $label)
            <button wire:click="setFilter('{{ $key }}')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                    {{ $filter === $key 
                        ? 'bg-amber-600 text-white' 
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Activity Timeline --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->activities as $activity)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                    wire:click="viewDetails({{ $activity['id'] }})">
                    <div class="flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 p-2 rounded-lg
                            @switch($activity['color'])
                                @case('green') bg-green-100 dark:bg-green-900/30 @break
                                @case('blue') bg-blue-100 dark:bg-blue-900/30 @break
                                @case('purple') bg-purple-100 dark:bg-purple-900/30 @break
                                @case('amber') bg-amber-100 dark:bg-amber-900/30 @break
                                @case('red') bg-red-100 dark:bg-red-900/30 @break
                                @default bg-gray-100 dark:bg-gray-700
                            @endswitch
                        ">
                            <svg class="w-5 h-5
                                @switch($activity['color'])
                                    @case('green') text-green-600 dark:text-green-400 @break
                                    @case('blue') text-blue-600 dark:text-blue-400 @break
                                    @case('purple') text-purple-600 dark:text-purple-400 @break
                                    @case('amber') text-amber-600 dark:text-amber-400 @break
                                    @case('red') text-red-600 dark:text-red-400 @break
                                    @default text-gray-600 dark:text-gray-400
                                @endswitch
                            " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {!! $activity['icon'] !!}
                            </svg>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                        @switch($activity['color'])
                                            @case('green') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 @break
                                            @case('blue') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 @break
                                            @case('purple') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 @break
                                            @case('amber') bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 @break
                                            @case('red') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 @break
                                            @default bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                        @endswitch
                                    ">
                                        {{ $activity['type'] }}
                                    </span>
                                    <span class="text-xs text-gray-400 uppercase">{{ $activity['event'] }}</span>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $activity['time_diff'] }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-900 dark:text-white mt-1">
                                {{ $activity['description'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                by {{ $activity['user_name'] }}
                            </p>
                        </div>

                        {{-- View Arrow --}}
                        <div class="flex-shrink-0 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    No activity found
                </div>
            @endforelse
        </div>

        {{-- Load More --}}
        @if($this->activities->count() >= $limit)
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="loadMore"
                    class="w-full px-4 py-2 text-sm text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors">
                    Load More Activity
                </button>
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedAudit)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="closeDetailModal"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activity Details</h3>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Meta Info --}}
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Event:</span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                {{ ucfirst($selectedAudit->event) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Model:</span>
                            <span class="text-gray-900 dark:text-white">{{ class_basename($selectedAudit->auditable_type) }} #{{ $selectedAudit->auditable_id }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">User:</span>
                            <span class="text-gray-900 dark:text-white">{{ $selectedAudit->user?->name ?? 'System' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Time:</span>
                            <span class="text-gray-900 dark:text-white">{{ $selectedAudit->created_at->format('M d, Y g:i:s A') }}</span>
                        </div>
                        @if($selectedAudit->ip_address)
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">IP:</span>
                                <span class="text-gray-900 dark:text-white font-mono text-sm">{{ $selectedAudit->ip_address }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Changes --}}
                    @if(!empty($selectedAudit->old_values) || !empty($selectedAudit->new_values))
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Changes</h4>
                            <div class="space-y-2">
                                @php
                                    $allKeys = array_unique(array_merge(
                                        array_keys($selectedAudit->old_values ?? []),
                                        array_keys($selectedAudit->new_values ?? [])
                                    ));
                                @endphp
                                @foreach($allKeys as $key)
                                    @php
                                        $oldVal = $selectedAudit->old_values[$key] ?? null;
                                        $newVal = $selectedAudit->new_values[$key] ?? null;
                                    @endphp
                                    <div class="p-2 bg-gray-50 dark:bg-gray-700/50 rounded text-sm">
                                        <div class="font-medium text-gray-700 dark:text-gray-300 mb-1">{{ Str::title(str_replace('_', ' ', $key)) }}</div>
                                        <div class="flex items-center gap-2 text-xs">
                                            @if($selectedAudit->event !== 'created' && $oldVal !== null)
                                                <span class="text-red-600 dark:text-red-400 line-through">
                                                    {{ is_array($oldVal) ? json_encode($oldVal) : $oldVal }}
                                                </span>
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                                </svg>
                                            @endif
                                            @if($newVal !== null)
                                                <span class="text-green-600 dark:text-green-400">
                                                    {{ is_array($newVal) ? json_encode($newVal) : $newVal }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <button wire:click="closeDetailModal"
                        class="w-full mt-6 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
