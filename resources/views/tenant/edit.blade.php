<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Edit Tenant" />
    </x-slot>

    <div class="max-w-2xl">
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

        <form method="POST" action="{{ route('tenants.update', $tenant) }}">
            @csrf
            @method('PUT')

            <x-card>
                <div class="space-y-6">
                    <div>
                        <x-input-label for="tenant_id" :value="__('Tenant ID')" />
                        <x-text-input id="tenant_id" class="mt-1 block w-full bg-gray-100 dark:bg-[#1e1e28]" type="text"
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
                            class="mt-1 block w-full border-gray-300 dark:border-[#2a2a38] dark:bg-[#14141c] dark:text-gray-100 focus:border-brand-500 focus:ring-brand-500 dark:focus:border-brand-400 dark:focus:ring-brand-400 rounded-md shadow-sm">{{ old('description', $tenant->description) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>
                </div>

                <x-slot name="footer">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('tenants.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-[#14141c] border border-gray-300 dark:border-[#2a2a38] rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cancel
                        </a>
                        <x-primary-button>
                            Save Changes
                        </x-primary-button>
                    </div>
                </x-slot>
            </x-card>
        </form>
    </div>
</x-app-layout>
