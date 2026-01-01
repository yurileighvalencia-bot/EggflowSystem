<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'EggFlow') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:flex-shrink-0">
            <div class="flex w-64 flex-col">
                <div class="flex min-h-0 flex-1 flex-col bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                    <!-- Logo -->
                    <div class="flex h-16 flex-shrink-0 items-center px-4 border-b border-gray-200 dark:border-gray-700">
                        <a href="/" class="flex items-center space-x-2">
                            <span class="text-2xl">🥚</span>
                            <span class="text-xl font-bold text-gray-900 dark:text-white">EggFlow</span>
                        </a>
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 space-y-1 px-2 py-4 overflow-y-auto">
                        {{ $sidebar ?? '' }}
                    </nav>

                    <!-- User Menu -->
                    <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 p-4">
                        {{ $userMenu ?? '' }}
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <!-- Mobile menu button -->
                    <button type="button" 
                            class="lg:hidden -ml-0.5 -mt-0.5 inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-500 hover:text-gray-900 dark:hover:text-white"
                            x-data
                            @click="$dispatch('toggle-sidebar')">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Page Title -->
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $header ?? ($title ?? 'Dashboard') }}
                    </h1>

                    <!-- Header Actions -->
                    <div class="flex items-center space-x-4">
                        {{ $headerActions ?? '' }}
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            @if (session()->has('success'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 4000)"
                     x-transition
                     class="mx-4 mt-4 bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 4000)"
                     x-transition
                     class="mx-4 mt-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div x-data="{ open: false }"
         @toggle-sidebar.window="open = !open"
         x-show="open"
         x-cloak
         class="relative z-50 lg:hidden">
        <div x-show="open"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/80"
             @click="open = false"></div>

        <div class="fixed inset-0 flex">
            <div x-show="open"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="relative mr-16 flex w-full max-w-xs flex-1">
                <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-gray-800 px-6 pb-4">
                    <div class="flex h-16 shrink-0 items-center">
                        <span class="text-2xl">🥚</span>
                        <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white">EggFlow</span>
                    </div>
                    <nav class="flex flex-1 flex-col">
                        {{ $mobileSidebar ?? $sidebar ?? '' }}
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>
