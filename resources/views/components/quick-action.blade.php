@props(['href', 'title', 'description' => null, 'icon' => null])

<a href="{{ $href }}" class="group bg-white dark:bg-[#101016] rounded-xl border border-gray-200 dark:border-[#262632] p-5 shadow-card hover:shadow-card-hover hover:border-brand-200 dark:hover:border-brand-800/40 transition-all duration-300 block">
    <div class="flex items-start gap-4">
        @if ($icon)
            <div class="p-2.5 rounded-xl bg-brand-50 dark:bg-brand-500/10 group-hover:bg-brand-100 dark:group-hover:bg-brand-500/15 transition-colors duration-300">
                <div class="w-5 h-5 text-brand-600 dark:text-brand-400">
                    {{ $icon }}
                </div>
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors duration-200">{{ $title }}</p>
            @if ($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-brand-500 group-hover:translate-x-0.5 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    </div>
</a>
