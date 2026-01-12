@props([
    'text' => 'Loading...',
    'overlay' => false,
])

@if($overlay)
<div {{ $attributes->merge(['class' => 'absolute inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm flex items-center justify-center z-10 rounded-lg']) }}>
    <div class="flex flex-col items-center gap-3">
        <x-loading-spinner size="lg" />
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $text }}</span>
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'flex items-center justify-center py-8']) }}>
    <div class="flex flex-col items-center gap-3">
        <x-loading-spinner size="lg" />
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $text }}</span>
    </div>
</div>
@endif
