{{-- Toast Notification Component --}}
{{-- Usage: Add this to your layout and dispatch events like:
     $this->dispatch('toast', type: 'success', message: 'Action completed!');
--}}

<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now();
            this.toasts.push({ id, ...toast });
            setTimeout(() => this.remove(id), toast.duration || 5000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @toast.window="add($event.detail)"
    class="fixed top-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="pointer-events-auto min-w-[300px] max-w-md p-4 rounded-lg shadow-lg border backdrop-blur-sm"
            :class="{
                'bg-green-50 dark:bg-green-900/90 border-green-200 dark:border-green-700': toast.type === 'success',
                'bg-red-50 dark:bg-red-900/90 border-red-200 dark:border-red-700': toast.type === 'error',
                'bg-yellow-50 dark:bg-yellow-900/90 border-yellow-200 dark:border-yellow-700': toast.type === 'warning',
                'bg-blue-50 dark:bg-blue-900/90 border-blue-200 dark:border-blue-700': toast.type === 'info',
            }"
        >
            <div class="flex items-start gap-3">
                {{-- Icon --}}
                <div class="flex-shrink-0">
                    {{-- Success Icon --}}
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    {{-- Error Icon --}}
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </template>
                    {{-- Warning Icon --}}
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-5 h-5 text-yellow-500 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>
                    {{-- Info Icon --}}
                    <template x-if="toast.type === 'info'">
                        <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </template>
                </div>
                
                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium"
                       :class="{
                           'text-green-800 dark:text-green-200': toast.type === 'success',
                           'text-red-800 dark:text-red-200': toast.type === 'error',
                           'text-yellow-800 dark:text-yellow-200': toast.type === 'warning',
                           'text-blue-800 dark:text-blue-200': toast.type === 'info',
                       }"
                       x-text="toast.message">
                    </p>
                    <p x-show="toast.description" 
                       class="mt-1 text-xs"
                       :class="{
                           'text-green-600 dark:text-green-300': toast.type === 'success',
                           'text-red-600 dark:text-red-300': toast.type === 'error',
                           'text-yellow-600 dark:text-yellow-300': toast.type === 'warning',
                           'text-blue-600 dark:text-blue-300': toast.type === 'info',
                       }"
                       x-text="toast.description">
                    </p>
                </div>
                
                {{-- Close Button --}}
                <button @click="remove(toast.id)" class="flex-shrink-0 p-1 rounded hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>
