<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Create Tenant</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Provision a new tenant workspace</p>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-400">
                <p class="font-semibold">Please fix the following errors:</p>
                <ul class="mt-2 list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tenants.store') }}" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf

            <x-card>
                <div class="space-y-6">
                    <div>
                        <x-input-label for="tenant_id" :value="__('Tenant ID')" />
                        <x-text-input id="tenant_id" class="mt-1 block w-full" type="text" name="tenant_id"
                            :value="old('tenant_id')" required />
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">A unique identifier (example: company-name).</p>
                        <x-input-error class="mt-2" :messages="$errors->get('tenant_id')" />
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Tenant Name')" />
                        <x-text-input id="name" class="mt-1 block w-full" type="text" name="name"
                            :value="old('name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Contact Email')" />
                        <x-text-input id="email" class="mt-1 block w-full" type="email" name="email"
                            :value="old('email')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="domain" :value="__('Domain')" />
                        <x-text-input id="domain" class="mt-1 block w-full" type="text" name="domain"
                            :value="old('domain')" required />
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Use a tenant domain like company.app.localhost.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="4"
                            class="mt-1 block w-full border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-lg shadow-card focus:shadow-glow-brand focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 dark:focus:ring-brand-400/20 transition-all duration-200">{{ old('description') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>
                </div>

                <x-slot name="footer">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('tenants.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-[#101016] border border-gray-300 dark:border-[#262632] rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-card hover:bg-gray-50 dark:hover:bg-[#181820] hover:shadow-card-hover transition-all duration-200">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            class="group relative inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 active:from-brand-600 active:to-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                            :disabled="submitting"
                        >
                            <span x-show="!submitting">Create Tenant</span>
                            <span x-show="submitting" x-cloak class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                Creating...
                            </span>
                        </button>
                    </div>
                </x-slot>
            </x-card>
        </form>
    </div>
</x-app-layout>
