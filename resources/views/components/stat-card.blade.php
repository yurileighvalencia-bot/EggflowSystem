@props([
    'title',
    'value',
    'change' => null, // percentage change, e.g., +12 or -5
    'changeLabel' => 'vs last period',
    'icon' => null,
    'color' => 'amber', // amber, green, blue, red, purple, gray
    'href' => null,
])

@php
    $colors = [
        'amber' => [
            'icon_bg' => 'bg-amber-100 dark:bg-amber-900/30',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'border' => 'border-amber-200 dark:border-amber-700',
        ],
        'green' => [
            'icon_bg' => 'bg-green-100 dark:bg-green-900/30',
            'icon' => 'text-green-600 dark:text-green-400',
            'border' => 'border-green-200 dark:border-green-700',
        ],
        'blue' => [
            'icon_bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'border' => 'border-blue-200 dark:border-blue-700',
        ],
        'red' => [
            'icon_bg' => 'bg-red-100 dark:bg-red-900/30',
            'icon' => 'text-red-600 dark:text-red-400',
            'border' => 'border-red-200 dark:border-red-700',
        ],
        'purple' => [
            'icon_bg' => 'bg-purple-100 dark:bg-purple-900/30',
            'icon' => 'text-purple-600 dark:text-purple-400',
            'border' => 'border-purple-200 dark:border-purple-700',
        ],
        'gray' => [
            'icon_bg' => 'bg-gray-100 dark:bg-gray-700',
            'icon' => 'text-gray-600 dark:text-gray-400',
            'border' => 'border-gray-200 dark:border-gray-700',
        ],
    ];
    $colorSet = $colors[$color] ?? $colors['amber'];
@endphp

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} 
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => "bg-white dark:bg-gray-800 rounded-xl border {$colorSet['border']} p-6 " . ($href ? 'hover:shadow-md transition-shadow cursor-pointer' : '')]) }}
>
    <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
            
            @if($change !== null)
                <p class="mt-1 flex items-center gap-1 text-sm">
                    @if($change > 0)
                        <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                        <span class="text-green-600 dark:text-green-400 font-medium">+{{ $change }}%</span>
                    @elseif($change < 0)
                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                        <span class="text-red-600 dark:text-red-400 font-medium">{{ $change }}%</span>
                    @else
                        <span class="text-gray-500 dark:text-gray-400">0%</span>
                    @endif
                    <span class="text-gray-400 dark:text-gray-500">{{ $changeLabel }}</span>
                </p>
            @endif
        </div>
        
        @if($icon)
            <div class="flex-shrink-0 p-3 rounded-full {{ $colorSet['icon_bg'] }}">
                <div class="w-6 h-6 {{ $colorSet['icon'] }}">
                    {!! $icon !!}
                </div>
            </div>
        @endif
    </div>
    
    {{-- Optional slot for additional content --}}
    @if($slot->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            {{ $slot }}
        </div>
    @endif
</{{ $tag }}>
