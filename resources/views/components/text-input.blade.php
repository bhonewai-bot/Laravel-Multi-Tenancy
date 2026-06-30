@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-lg shadow-card focus:shadow-glow-brand focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 dark:focus:ring-brand-400/20 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
