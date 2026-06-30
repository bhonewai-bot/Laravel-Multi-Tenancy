@props([
    'name',
    'action',
    'entity' => 'this item',
])

<x-modal :name="$name" maxWidth="sm">
    <form method="POST" action="{{ $action }}" x-data="{ deleting: false }" @submit="deleting = true">
        @csrf
        @method('DELETE')

        <div class="p-6">
            {{-- Icon --}}
            <div class="mx-auto w-12 h-12 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center mb-4">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>

            {{-- Text --}}
            <h3 class="text-center text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                Delete {{ $entity }}
            </h3>
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                Are you sure? This action cannot be undone.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 px-6 pb-6">
            <button type="button"
                @click="$dispatch('close-modal', '{{ $name }}')"
                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-white dark:bg-[#181820] border border-gray-300 dark:border-[#262632] rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-card hover:bg-gray-50 dark:hover:bg-[#262632] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition-all duration-200 ease-in-out">
                Cancel
            </button>
            <button type="submit" :disabled="deleting"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-b from-red-500 to-red-600 border border-red-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-[0_0_20px_rgba(239,68,68,0.15)] hover:from-red-500 hover:to-red-700 active:from-red-600 active:to-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 ease-in-out">
                <span x-show="!deleting">Delete</span>
                <span x-show="deleting" x-cloak class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    Deleting...
                </span>
            </button>
        </div>
    </form>
</x-modal>
