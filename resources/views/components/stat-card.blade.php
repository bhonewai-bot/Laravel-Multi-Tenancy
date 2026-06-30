@props(['label', 'value', 'icon' => null, 'description' => null, 'trend' => null, 'trendDirection' => null])

<div {{ $attributes->merge(['class' => 'group bg-white dark:bg-[#101016] rounded-xl border border-gray-200 dark:border-[#262632] p-6 shadow-card hover:shadow-card-hover hover:border-brand-200 dark:hover:border-brand-800/40 transition-all duration-300']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $value }}</p>
            @if ($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
            @if ($trend)
                <div class="mt-2 flex items-center gap-1.5">
                    @if ($trendDirection === 'up')
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    @elseif ($trendDirection === 'down')
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                    <span class="text-sm font-medium {{ $trendDirection === 'up' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $trend }}
                    </span>
                </div>
            @endif
        </div>
        @if ($icon)
            <div class="ml-4 p-3 rounded-xl bg-brand-50 dark:bg-brand-500/10 group-hover:bg-brand-100 dark:group-hover:bg-brand-500/15 transition-colors duration-300">
                <div class="w-6 h-6 text-brand-600 dark:text-brand-400">
                    {{ $icon }}
                </div>
            </div>
        @endif
    </div>
</div>
