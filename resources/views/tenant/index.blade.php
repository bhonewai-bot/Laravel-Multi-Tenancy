<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tenants" description="Manage your tenant organizations">
            <x-slot name="actions">
                <a href="{{ route('tenants.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-brand-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 focus:bg-brand-700 active:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0f] transition ease-in-out duration-150">
                    + Create Tenant
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <x-card>
                @if ($tenants->isEmpty())
                    <x-empty-state title="No tenants found" description="Get started by creating your first tenant organization.">
                        <x-slot name="action">
                            <a href="{{ route('tenants.create') }}"
                                class="inline-flex items-center px-4 py-2 bg-brand-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 focus:bg-brand-700 active:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0f] transition ease-in-out duration-150">
                                + Create Tenant
                            </a>
                        </x-slot>
                    </x-empty-state>
                @else
                    <x-data-table>
                        <thead class="bg-gray-50 dark:bg-[#0e0e15]">
                            <tr>
                                <th class="w-[8%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">ID</th>
                                <th class="w-[14%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                                <th class="w-[22%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                                <th class="w-[20%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Domain</th>
                                <th class="w-[18%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</th>
                                <th class="w-[18%] px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#2a2a38] bg-white dark:bg-[#14141c]">
                            @foreach ($tenants as $tenant)
                                @php $primaryDomain = $tenant->domains->first()?->domain; @endphp
                                <tr class="border-t border-gray-200 dark:border-[#2a2a38] hover:bg-gray-50 dark:hover:bg-[#1e1e28] transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $tenant->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $tenant->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $tenant->email ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($primaryDomain)
                                            <a class="inline-flex items-center gap-1 text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors duration-150"
                                                href="http://{{ $primaryDomain }}" target="_blank" rel="noopener noreferrer">
                                                {{ $primaryDomain }}
                                            </a>
                                        @else
                                            <x-badge variant="neutral" label="No domain" />
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ optional($tenant->created_at)->format('M d, Y H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        <div class="flex w-full justify-end">
                                            <x-dropdown align="right" width="w-40">
                                                <x-slot name="trigger">
                                                    <button type="button"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-[#2a2a38] bg-white dark:bg-[#1e1e28] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-[#2a2a38] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0f] transition duration-150 ease-in-out">
                                                        Action
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </x-slot>

                                                <x-slot name="content">
                                                    <x-dropdown-link :href="route('tenants.show', $tenant)">
                                                        View
                                                    </x-dropdown-link>

                                                    <x-dropdown-link :href="route('tenants.edit', $tenant)">
                                                        Edit
                                                    </x-dropdown-link>

                                                    <form method="POST" action="{{ route('tenants.destroy', $tenant) }}"
                                                        onsubmit="return confirm('Delete tenant {{ $tenant->id }}? This may remove its tenant database.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-[#2a2a38] transition duration-150 ease-in-out">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        {{-- Mobile card view --}}
                        <x-slot name="mobile">
                            @foreach ($tenants as $tenant)
                                @php $primaryDomain = $tenant->domains->first()?->domain; @endphp
                                <div class="rounded-lg border border-gray-200 dark:border-[#2a2a38] bg-white dark:bg-[#14141c] p-4 space-y-3 hover:bg-gray-50 dark:hover:bg-[#1e1e28] transition-colors duration-150">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $tenant->name ?? 'N/A' }}</span>
                                            @if ($primaryDomain)
                                                <x-badge variant="info" label="Active" />
                                            @else
                                                <x-badge variant="neutral" label="No domain" />
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">#{{ $tenant->id }}</span>
                                    </div>

                                    <div class="space-y-1 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500 dark:text-gray-400">Email:</span>
                                            <span class="text-gray-900 dark:text-gray-100">{{ $tenant->email ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500 dark:text-gray-400">Domain:</span>
                                            @if ($primaryDomain)
                                                <a class="text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors duration-150"
                                                    href="http://{{ $primaryDomain }}" target="_blank" rel="noopener noreferrer">
                                                    {{ $primaryDomain }}
                                                </a>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">None</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-500 dark:text-gray-400">Created:</span>
                                            <span class="text-gray-500 dark:text-gray-400">{{ optional($tenant->created_at)->format('M d, Y H:i') }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-[#2a2a38]">
                                        <a href="{{ route('tenants.show', $tenant) }}"
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-[#1e1e28] border border-gray-200 dark:border-[#2a2a38] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#2a2a38] transition duration-150 ease-in-out">
                                            View
                                        </a>
                                        <a href="{{ route('tenants.edit', $tenant) }}"
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-[#1e1e28] border border-gray-200 dark:border-[#2a2a38] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#2a2a38] transition duration-150 ease-in-out">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('tenants.destroy', $tenant) }}"
                                            onsubmit="return confirm('Delete tenant {{ $tenant->id }}? This may remove its tenant database.');"
                                            class="ml-auto">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition duration-150 ease-in-out">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </x-slot>
                    </x-data-table>

                    <div class="mt-4">{{ $tenants->links() }}</div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
