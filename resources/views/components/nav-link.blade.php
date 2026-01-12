@props(['active' => false])

@php
$classes = $active 
    ? 'flex items-center gap-3 rounded-lg px-3 py-2 text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700'
    : 'flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
