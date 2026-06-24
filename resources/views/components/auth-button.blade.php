@props(['loadingText' => 'Signing in...'])

<button
    type="button"
    x-data="{ submitting: false }"
    @click="if (!submitting) { submitting = true; $el.closest('form').requestSubmit(); }"
    x-bind:disabled="submitting"
    {{ $attributes->merge(['class' => '
        group relative w-full flex items-center justify-center gap-2
        px-6 py-3 text-sm font-semibold text-white rounded-lg
        bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20
        hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700
        active:from-brand-600 active:to-brand-800
        focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c]
        shadow-card
        transition-all duration-200 ease-in-out
        disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none
    ']) }}
>
    <span x-show="!submitting" class="flex items-center gap-2">
        {{ $slot }}
        <svg
            class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
        </svg>
    </span>

    {{-- Loading spinner --}}
    <span x-show="submitting" x-cloak class="flex items-center gap-2">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        {{ $loadingText }}
    </span>
</button>
