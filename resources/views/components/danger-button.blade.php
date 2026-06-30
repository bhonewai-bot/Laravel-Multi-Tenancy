<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-gradient-to-b from-red-500 to-red-600 border border-red-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-[0_0_20px_rgba(239,68,68,0.15)] hover:from-red-500 hover:to-red-700 active:from-red-600 active:to-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200']) }}>
    {{ $slot }}
</button>
