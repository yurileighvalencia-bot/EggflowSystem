@props([
    'type' => 'info', // info, success, warning, error
    'dismissible' => false,
    'icon' => true,
])

@php
    $styles = [
        'info' => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/30',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-800 dark:text-blue-200',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />',
            'iconColor' => 'text-blue-500 dark:text-blue-400',
        ],
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-900/30',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'iconColor' => 'text-green-500 dark:text-green-400',
        ],
        'warning' => [
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/30',
            'border' => 'border-yellow-200 dark:border-yellow-800',
            'text' => 'text-yellow-800 dark:text-yellow-200',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />',
            'iconColor' => 'text-yellow-500 dark:text-yellow-400',
        ],
        'error' => [
            'bg' => 'bg-red-50 dark:bg-red-900/30',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />',
            'iconColor' => 'text-red-500 dark:text-red-400',
        ],
    ];
    
    $style = $styles[$type] ?? $styles['info'];
@endphp

<div 
    x-data="{ show: true }" 
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => "p-4 rounded-lg border {$style['bg']} {$style['border']}"]) }}
>
    <div class="flex items-start gap-3">
        @if($icon)
        <svg class="w-5 h-5 flex-shrink-0 {{ $style['iconColor'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            {!! $style['icon'] !!}
        </svg>
        @endif
        
        <div class="flex-1 {{ $style['text'] }}">
            {{ $slot }}
        </div>
        
        @if($dismissible)
        <button @click="show = false" class="flex-shrink-0 p-1 rounded hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
            <svg class="w-4 h-4 {{ $style['text'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        @endif
    </div>
</div>
