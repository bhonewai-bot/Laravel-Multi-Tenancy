<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tenant Details">
            <x-slot name="actions">
                <x-secondary-button onclick="window.location='{{ route('tenants.index') }}'">
                    Back
                </x-secondary-button>
                <a href="{{ route('tenants.edit', $tenant) }}"
                    class="inline-flex items-center px-4 py-2 bg-brand-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 transition">
                    Edit
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        {{-- Tenant Info --}}
        <x-card>
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tenant ID</dt>
                    <dd class="mt-2 text-sm text-gray-900 dark:text-gray-100 font-semibold">{{ $tenant->id }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="mt-2 text-sm text-gray-900 dark:text-gray-100">{{ $tenant->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="mt-2 text-sm text-gray-900 dark:text-gray-100">{{ $tenant->email ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="mt-2 text-sm text-gray-900 dark:text-gray-100">{{ optional($tenant->created_at)->format('M d, Y H:i') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Description</dt>
                    <dd class="mt-2 text-sm text-gray-900 dark:text-gray-100">{{ $tenant->description ?: 'N/A' }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- Domains --}}
        <x-card>
            <x-slot name="header">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Domains</h3>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-[#2a2a38]">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Domain</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Open</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#2a2a38]">
                        @forelse ($tenant->domains as $domain)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $domain->domain }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ optional($domain->created_at)->format('M d, Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="http://{{ $domain->domain }}" target="_blank" rel="noopener noreferrer"
                                        class="text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
                                        Open site
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">No domains attached to this tenant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
