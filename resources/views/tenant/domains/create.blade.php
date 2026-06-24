<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Add Custom Domain</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Register a new custom domain for your application.</p>
            </div>
            <a href="{{ route('tenant.domains.index', absolute: false) }}">
                <x-secondary-button type="button">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Back to Domains
                </x-secondary-button>
            </a>
        </div>

        {{-- Errors --}}
        @if (session('error'))
            <div class="mb-6">
                <x-alert variant="error">{{ session('error') }}</x-alert>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6">
                <x-alert variant="error">Please fix the errors below.</x-alert>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('tenant.domains.store', absolute: false) }}" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <x-card>
                <div class="space-y-6">
                    <div>
                        <x-input-label for="domain" value="Domain" />
                        <x-text-input
                            id="domain"
                            name="domain"
                            type="text"
                            class="mt-1 block w-full rounded-lg"
                            placeholder="shop.example.com"
                            :value="old('domain')"
                            required
                        />
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Enter the custom domain you own (e.g. <span class="font-semibold">shop.example.com</span> or <span class="font-semibold">www.example.com</span>).</p>
                        <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                    </div>
                </div>

                <x-slot name="footer">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('tenant.domains.index', absolute: false) }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                        <button type="submit" :disabled="submitting"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                            <span x-show="!submitting">ADD DOMAIN</span>
                            <span x-show="submitting" x-cloak>ADDING...</span>
                        </button>
                    </div>
                </x-slot>
            </x-card>
        </form>

        {{-- Info Card --}}
        <div class="mt-6">
            <x-card>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">After adding the domain</p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Create a <span class="font-semibold">CNAME</span> to <span class="font-semibold">{{ config('cloudflare.fallback_origin') }}</span>.</li>
                        <li>If DNS is also on Cloudflare, keep the record as <span class="font-semibold">DNS only</span>.</li>
                        <li>Open the setup page and click <span class="font-semibold">Check Status</span> until Hostname and SSL are active.</li>
                    </ul>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
