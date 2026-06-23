<x-app-layout>
    <x-page-header title="Roles">
        <x-slot:actions>
            @can('create', App\Models\Role::class)
                <a href="{{ route('tenant.roles.create', absolute: false) }}"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 transition-all duration-200">
                    Add Role
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="w-full px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800">{{ session('error') }}</div>
        @endif

        <x-card>
            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-[#262632]">
                    <thead class="bg-gray-50 dark:bg-[#0e0e15]">
                        <tr>
                            <th class="w-[10%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                            <th class="w-[61%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Permissions</th>
                            <th class="w-[14%] px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-[#101016] divide-y divide-gray-200 dark:divide-[#262632]">
                        @forelse ($roles as $role)
                            <tr class="border-t border-gray-200 dark:border-[#262632] hover:bg-gray-50 dark:hover:bg-[#181820]">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($role->name) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    @if ($role->permissions->isEmpty())
                                        <span class="text-gray-400 dark:text-gray-500">No permissions</span>
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($role->permissions as $permission)
                                                <x-badge variant="brand">
                                                    {{ ucfirst($permission->feature?->name ?? 'General') }}: {{ ucfirst($permission->name) }}
                                                </x-badge>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex w-full justify-end">
                                        <x-dropdown align="right" width="w-40">
                                            <x-slot name="trigger">
                                                <button type="button"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-[#262632] bg-white dark:bg-[#101016] px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 shadow-card hover:bg-gray-50 dark:hover:bg-[#181820]">
                                                    Action
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <x-dropdown-link :href="route('tenant.roles.show', $role, absolute: false)">
                                                    View
                                                </x-dropdown-link>

                                                @can('update', $role)
                                                    <x-dropdown-link :href="route('tenant.roles.edit', $role, absolute: false)">
                                                        Edit
                                                    </x-dropdown-link>
                                                @endcan

                                                @can('delete', $role)
                                                    <form method="POST" action="{{ route('tenant.roles.destroy', $role, absolute: false) }}"
                                                        onsubmit="return confirm('Delete role {{ $role->name }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-[#181820]">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endcan
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                    <x-empty-state title="No roles found" description="Roles define what users can do in this tenant." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $roles->links() }}</div>
        </x-card>
    </div>
</x-app-layout>
