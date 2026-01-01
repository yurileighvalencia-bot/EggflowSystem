<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notification Center</h1>
            <p class="text-gray-600 dark:text-gray-400">View and manage your notifications</p>
        </div>
        <div class="flex gap-2">
            @if($this->stats['unread'] > 0)
                <button wire:click="markAllAsRead"
                    class="px-4 py-2 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                    Mark All as Read
                </button>
            @endif
            @if($this->stats['total'] - $this->stats['unread'] > 0)
                <button wire:click="deleteAllRead"
                    wire:confirm="Delete all read notifications?"
                    class="px-4 py-2 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                    Clear Read
                </button>
            @endif
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700 p-4">
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->stats['unread'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Unread</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $this->stats['today'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Today</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col md:flex-row gap-4">
            {{-- Filter Tabs --}}
            <div class="flex gap-2">
                @foreach($this->getFilterOptions() as $key => $label)
                    <button wire:click="setFilter('{{ $key }}')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                            {{ $filter === $key 
                                ? 'bg-amber-600 text-white' 
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Type Filter --}}
            <select wire:model.live="type"
                class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Types</option>
                @foreach($this->notificationTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->notifications as $notification)
                @php
                    $data = $notification->data;
                    $color = $this->getColor($data);
                @endphp
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ !$notification->read_at ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' }}">
                    <div class="flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 p-2 rounded-lg
                            @switch($color)
                                @case('red') bg-red-100 dark:bg-red-900/30 @break
                                @case('green') bg-green-100 dark:bg-green-900/30 @break
                                @case('blue') bg-blue-100 dark:bg-blue-900/30 @break
                                @case('yellow') bg-yellow-100 dark:bg-yellow-900/30 @break
                                @case('purple') bg-purple-100 dark:bg-purple-900/30 @break
                                @case('amber') bg-amber-100 dark:bg-amber-900/30 @break
                                @default bg-gray-100 dark:bg-gray-700
                            @endswitch
                        ">
                            <svg class="w-5 h-5
                                @switch($color)
                                    @case('red') text-red-600 dark:text-red-400 @break
                                    @case('green') text-green-600 dark:text-green-400 @break
                                    @case('blue') text-blue-600 dark:text-blue-400 @break
                                    @case('yellow') text-yellow-600 dark:text-yellow-400 @break
                                    @case('purple') text-purple-600 dark:text-purple-400 @break
                                    @case('amber') text-amber-600 dark:text-amber-400 @break
                                    @default text-gray-600 dark:text-gray-400
                                @endswitch
                            " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {!! $this->getIcon($data) !!}
                            </svg>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0 cursor-pointer" wire:click="viewDetails('{{ $notification->id }}')">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                    @switch($color)
                                        @case('red') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 @break
                                        @case('green') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 @break
                                        @case('blue') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 @break
                                        @case('yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 @break
                                        @case('purple') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 @break
                                        @case('amber') bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 @break
                                        @default bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                    @endswitch
                                ">
                                    {{ $this->getTypeLabel($data['type'] ?? 'notification') }}
                                </span>
                                @if(!$notification->read_at)
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-900 dark:text-white {{ !$notification->read_at ? 'font-medium' : '' }}">
                                {{ $data['message'] ?? 'You have a new notification' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex-shrink-0 flex items-center gap-1">
                            @if($notification->read_at)
                                <button wire:click="markAsUnread('{{ $notification->id }}')"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                                    title="Mark as Unread">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            @else
                                <button wire:click="markAsRead('{{ $notification->id }}')"
                                    class="p-1.5 text-amber-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded"
                                    title="Mark as Read">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            @endif
                            <button wire:click="deleteNotification('{{ $notification->id }}')"
                                class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded"
                                title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    No notifications found
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($this->notifications->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->notifications->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedNotification)
        @php
            $data = $selectedNotification->data;
            $color = $this->getColor($data);
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" wire:click="closeDetailModal"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg
                                @switch($color)
                                    @case('red') bg-red-100 dark:bg-red-900/30 @break
                                    @case('green') bg-green-100 dark:bg-green-900/30 @break
                                    @case('blue') bg-blue-100 dark:bg-blue-900/30 @break
                                    @case('yellow') bg-yellow-100 dark:bg-yellow-900/30 @break
                                    @case('purple') bg-purple-100 dark:bg-purple-900/30 @break
                                    @case('amber') bg-amber-100 dark:bg-amber-900/30 @break
                                    @default bg-gray-100 dark:bg-gray-700
                                @endswitch
                            ">
                                <svg class="w-6 h-6
                                    @switch($color)
                                        @case('red') text-red-600 dark:text-red-400 @break
                                        @case('green') text-green-600 dark:text-green-400 @break
                                        @case('blue') text-blue-600 dark:text-blue-400 @break
                                        @case('yellow') text-yellow-600 dark:text-yellow-400 @break
                                        @case('purple') text-purple-600 dark:text-purple-400 @break
                                        @case('amber') text-amber-600 dark:text-amber-400 @break
                                        @default text-gray-600 dark:text-gray-400
                                    @endswitch
                                " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $this->getIcon($data) !!}
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $this->getTypeLabel($data['type'] ?? 'notification') }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $selectedNotification->created_at->format('M d, Y g:i A') }}
                                </p>
                            </div>
                        </div>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Message --}}
                    <div class="mb-6">
                        <p class="text-gray-900 dark:text-white">
                            {{ $data['message'] ?? 'You have a new notification' }}
                        </p>
                    </div>

                    {{-- Details --}}
                    @if(count($data) > 2)
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Details</h4>
                            <div class="space-y-2">
                                @foreach($data as $key => $value)
                                    @if(!in_array($key, ['type', 'message']))
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">{{ Str::title(str_replace('_', ' ', $key)) }}:</span>
                                            <span class="text-gray-900 dark:text-white">
                                                @if(is_bool($value))
                                                    {{ $value ? 'Yes' : 'No' }}
                                                @elseif(is_array($value))
                                                    {{ json_encode($value) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <button wire:click="closeDetailModal"
                        class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
