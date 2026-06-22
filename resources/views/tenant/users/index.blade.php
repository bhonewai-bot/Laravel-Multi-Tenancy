<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Users"
            description="Manage tenant users and their roles."
        >
            <x-slot name="actions">
                @can('create', App\Models\User::class)
                    <a href="{{ route('tenant.users.create', absolute: false) }}">
                        <x-primary-button type="button">+ Add User</x-primary-button>
                    </a>
                @endcan
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800">
                {{ session('error') }}
            </div>
        @endif

        <x-card>
            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-[#2a2a38]">
                    <thead>
                        <tr>
                            <th class="w-[24%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                            <th class="w-[30%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                            <th class="w-[16%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</th>
                            <th class="w-[14%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</th>
                            <th class="w-[16%] px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#2a2a38]">
                        @forelse ($users as $user)
                            <tr class="border-t border-gray-200 dark:border-[#2a2a38] hover:bg-gray-50 dark:hover:bg-[#1e1e28] transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    @if ($user->role?->name)
                                        <x-badge variant="brand">{{ ucfirst($user->role->name) }}</x-badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ optional($user->created_at)->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <div class="flex w-full justify-end">
                                        <x-dropdown align="right" width="w-40">
                                            <x-slot name="trigger">
                                                <button type="button"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-[#2a2a38] bg-white dark:bg-[#14141c] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-[#1e1e28] transition-colors">
                                                    Action
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <x-dropdown-link :href="route('tenant.users.show', $user, absolute: false)">
                                                    View
                                                </x-dropdown-link>

                                                @can('update', $user)
                                                    <x-dropdown-link :href="route('tenant.users.edit', $user, absolute: false)">
                                                        Edit
                                                    </x-dropdown-link>
                                                @endcan

                                                @can('delete', $user)
                                                    <form method="POST" action="{{ route('tenant.users.destroy', $user, absolute: false) }}"
                                                        onsubmit="return confirm('Delete user {{ $user->name }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-[#1e1e28]">
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
                                <td colspan="5">
                                    <x-empty-state title="No users found" description="Get started by adding your first user to this tenant.">
                                        @can('create', App\Models\User::class)
                                            <x-slot name="action">
                                                <a href="{{ route('tenant.users.create', absolute: false) }}">
                                                    <x-primary-button type="button">+ Add User</x-primary-button>
                                                </a>
                                            </x-slot>
                                        @endcan
                                    </x-empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-200 dark:border-[#2a2a38] px-6 py-4">
                    {{ $users->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
