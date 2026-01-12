@props([
    'id' => 'confirmation-modal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'type' => 'warning', // warning, danger, info
])

@php
    $colors = [
        'warning' => [
            'icon_bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            'icon' => 'text-yellow-600 dark:text-yellow-400',
            'btn' => 'bg-yellow-600 hover:bg-yellow-700',
        ],
        'danger' => [
            'icon_bg' => 'bg-red-100 dark:bg-red-900/30',
            'icon' => 'text-red-600 dark:text-red-400',
            'btn' => 'bg-red-600 hover:bg-red-700',
        ],
        'info' => [
            'icon_bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'btn' => 'bg-blue-600 hover:bg-blue-700',
        ],
    ];
    $color = $colors[$type] ?? $colors['warning'];
@endphp

<div 
    x-data="{ open: false }"
    x-on:open-{{ $id }}.window="open = true"
    x-on:close-{{ $id }}.window="open = false"
    x-on:keydown.escape.window="open = false"
>
    {{-- Backdrop --}}
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
        @click="open = false"
    ></div>

    {{-- Modal --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6" @click.stop>
            <div class="flex items-start gap-4">
                {{-- Icon --}}
                <div class="flex-shrink-0 p-3 rounded-full {{ $color['icon_bg'] }}">
                    @if($type === 'danger')
                        <svg class="w-6 h-6 {{ $color['icon'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    @elseif($type === 'warning')
                        <svg class="w-6 h-6 {{ $color['icon'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    @else
                        <svg class="w-6 h-6 {{ $color['icon'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    @endif
                </div>
                
                {{-- Content --}}
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $message }}</p>
                    
                    {{-- Custom slot content --}}
                    {{ $slot }}
                </div>
            </div>
            
            {{-- Actions --}}
            <div class="mt-6 flex justify-end gap-3">
                <button 
                    @click="open = false"
                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    {{ $cancelText }}
                </button>
                <button 
                    {{ $attributes->whereStartsWith('wire:') }}
                    @click="open = false"
                    class="px-4 py-2 text-white rounded-lg {{ $color['btn'] }} transition-colors"
                >
                    {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>
