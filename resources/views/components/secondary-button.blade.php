<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white dark:bg-[#101016] border border-gray-300 dark:border-[#262632] rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-card hover:bg-gray-50 dark:hover:bg-[#181820] hover:shadow-card-hover focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] disabled:opacity-50 transition-all duration-200']) }}>
    {{ $slot }}
</button>
