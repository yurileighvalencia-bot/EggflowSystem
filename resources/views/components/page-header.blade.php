@props([
    'title',
    'description' => null,
    'icon' => null,
    'iconBg' => 'amber',
])

@php
    $iconBgColors = [
        'amber' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
        'green' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        'red' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
        'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
        'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start justify-between']) }}>
    <div class="flex items-center gap-4">
        @if($icon)
        <div class="flex-shrink-0 w-12 h-12 rounded-lg {{ $iconBgColors[$iconBg] ?? $iconBgColors['amber'] }} flex items-center justify-center">
            {!! $icon !!}
        </div>
        @endif
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
            @if($description)
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $description }}</p>
            @endif
        </div>
    </div>
    
    {{-- Actions Slot --}}
    @if(isset($actions))
    <div class="flex items-center gap-2">
        {{ $actions }}
    </div>
    @endif
</div>
