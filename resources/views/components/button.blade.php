@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, danger, ghost
    'size' => 'md', // sm, md, lg
    'loading' => false,
    'disabled' => false,
    'icon' => null, // left icon slot name
])

@php
    $variants = [
        'primary' => 'bg-amber-600 hover:bg-amber-700 text-white focus:ring-amber-500 disabled:bg-amber-400',
        'secondary' => 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 focus:ring-gray-500',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500 disabled:bg-red-400',
        'ghost' => 'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-gray-500',
        'success' => 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500 disabled:bg-green-400',
    ];
    
    $sizes = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
        'xl' => 'px-6 py-3 text-base',
    ];
    
    $variantClass = $variants[$variant] ?? $variants['primary'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed {$variantClass} {$sizeClass}"
    ]) }}
    {{ $disabled || $loading ? 'disabled' : '' }}
>
    {{-- Loading Spinner --}}
    @if($loading)
        <x-loading-spinner size="sm" :color="in_array($variant, ['primary', 'danger', 'success']) ? 'white' : 'gray'" />
    @elseif(isset($iconLeft))
        {{ $iconLeft }}
    @endif
    
    {{-- Button Text --}}
    <span>{{ $slot }}</span>
    
    {{-- Right Icon --}}
    @if(isset($iconRight))
        {{ $iconRight }}
    @endif
</button>
