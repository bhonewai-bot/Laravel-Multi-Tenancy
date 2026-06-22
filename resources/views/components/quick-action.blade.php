@props(['href', 'title', 'description' => null, 'icon' => null])

<a href="{{ $href }}" class="group bg-white dark:bg-[#14141c] rounded-lg border border-gray-200 dark:border-[#2a2a38] p-5 shadow-sm hover:shadow-md transition-all duration-200 block">
    <div class="flex items-start gap-4">
        @if ($icon)
            <div class="p-2.5 rounded-lg bg-brand-50 dark:bg-brand-900/20 group-hover:bg-brand-100 dark:group-hover:bg-brand-900/30 transition-colors">
                <div class="w-5 h-5 text-brand-600 dark:text-brand-400">
                    {{ $icon }}
                </div>
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">{{ $title }}</p>
            @if ($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-brand-500 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    </div>
</a>
