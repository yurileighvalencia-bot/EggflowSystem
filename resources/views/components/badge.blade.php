@props([
    'color' => 'gray', // gray, green, red, yellow, blue, amber, purple, pink
    'size' => 'md', // sm, md, lg
    'dot' => false, // show status dot
    'pill' => true, // rounded-full vs rounded
])

@php
    $colors = [
        'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
        'green' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        'red' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        'yellow' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        'amber' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        'pink' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400',
        'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
    ];
    
    $dotColors = [
        'gray' => 'bg-gray-500',
        'green' => 'bg-green-500',
        'red' => 'bg-red-500',
        'yellow' => 'bg-yellow-500',
        'blue' => 'bg-blue-500',
        'amber' => 'bg-amber-500',
        'purple' => 'bg-purple-500',
        'pink' => 'bg-pink-500',
        'orange' => 'bg-orange-500',
    ];
    
    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1.5 text-sm',
    ];
    
    $colorClass = $colors[$color] ?? $colors['gray'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $dotColorClass = $dotColors[$color] ?? $dotColors['gray'];
    $roundedClass = $pill ? 'rounded-full' : 'rounded';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-medium {$colorClass} {$sizeClass} {$roundedClass}"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColorClass }}"></span>
    @endif
    {{ $slot }}
</span>
