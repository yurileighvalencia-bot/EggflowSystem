@props([
    'rows' => 3,
    'cols' => 1,
    'type' => 'card', // card, table, list
])

@if($type === 'card')
<div {{ $attributes->merge(['class' => 'grid gap-4']) }} style="grid-template-columns: repeat({{ $cols }}, minmax(0, 1fr));">
    @for($i = 0; $i < $rows; $i++)
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 animate-pulse">
        <div class="flex items-center justify-between mb-4">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
            <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
        </div>
        <div class="space-y-2">
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
        </div>
    </div>
    @endfor
</div>
@elseif($type === 'table')
<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden animate-pulse">
    {{-- Table Header --}}
    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <div class="flex gap-4">
            @for($c = 0; $c < min($cols, 5); $c++)
            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded flex-1"></div>
            @endfor
        </div>
    </div>
    {{-- Table Rows --}}
    @for($i = 0; $i < $rows; $i++)
    <div class="px-4 py-3 {{ $i < $rows - 1 ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
        <div class="flex gap-4 items-center">
            @for($c = 0; $c < min($cols, 5); $c++)
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
            @endfor
        </div>
    </div>
    @endfor
</div>
@elseif($type === 'list')
<div class="space-y-3 animate-pulse">
    @for($i = 0; $i < $rows; $i++)
    <div class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-full flex-shrink-0"></div>
        <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
        </div>
        <div class="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
    </div>
    @endfor
</div>
@endif
