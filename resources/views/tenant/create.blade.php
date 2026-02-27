<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Tenant</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($errors->any())
                        <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700 border border-red-200">
                            <p class="font-semibold">Please fix the following errors:</p>
                            <ul class="mt-2 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tenants.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="tenant_id" :value="__('Tenant ID')" />
                            <x-text-input id="tenant_id" class="mt-1 block w-full" type="text" name="tenant_id"
                                :value="old('tenant_id')" required />
                            <p class="mt-2 text-sm text-gray-500">A unique identifier (example: company-name).</p>
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
                            <p class="mt-2 text-sm text-gray-500">Use a tenant domain like company.app.localhost.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('tenants.index') }}"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">
                                Cancel
                            </a>
                            <x-primary-button>
                                Create Tenant
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
