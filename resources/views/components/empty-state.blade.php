@props(['title', 'description' => null, 'action' => null])

<div class="text-center py-12 px-4">
    <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 dark:bg-[#1e1e28] flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
    </div>
    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">{{ $description }}</p>
    @endif
    @if ($action)
        <div class="mt-4">
            {{ $action }}
        </div>
    @endif
</div>
