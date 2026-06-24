<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Tenant</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $tenant->name ?? $tenant->id }}</p>
            </div>
            <a href="{{ route('tenants.index') }}">
                <x-secondary-button type="button">Back to Tenants</x-secondary-button>
            </a>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="mb-6">
                <x-alert variant="error">Please fix the errors below.</x-alert>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('tenants.update', $tenant) }}" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('PUT')

            <x-card>
                <div class="space-y-6">
                    <div>
                        <x-input-label for="tenant_id" :value="__('Tenant ID')" />
                        <x-text-input id="tenant_id" class="mt-1 block w-full bg-gray-100 dark:bg-[#101016]" type="text"
                            :value="$tenant->id" disabled />
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Tenant Name')" />
                        <x-text-input id="name" class="mt-1 block w-full" type="text" name="name"
                            :value="old('name', $tenant->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Contact Email')" />
                        <x-text-input id="email" class="mt-1 block w-full" type="email" name="email"
                            :value="old('email', $tenant->email)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="domain" :value="__('Domain')" />
                        <x-text-input id="domain" class="mt-1 block w-full" type="text" name="domain"
                            :value="old('domain', $tenant->domains->first()?->domain)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="4"
                            class="mt-1 block w-full border-gray-300 dark:border-[#262632] dark:bg-[#101016] dark:text-gray-100 focus:border-brand-500 focus:ring-brand-500 dark:focus:border-brand-400 dark:focus:ring-brand-400 rounded-lg shadow-card">{{ old('description', $tenant->description) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>
                </div>

                <x-slot name="footer">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('tenants.index') }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                        <x-primary-button x-bind:disabled="submitting">
                            <span x-show="!submitting">UPDATE TENANT</span>
                            <span x-show="submitting" x-cloak>UPDATING...</span>
                        </x-primary-button>
                    </div>
                </x-slot>
            </x-card>
        </form>
    </div>
</x-app-layout>
