<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Roles</h2>
            @can('create', App\Models\Role::class)
                <a href="{{ route('tenant.roles.create', absolute: false) }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    + Add Role
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700 border border-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700 border border-red-200">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-visible shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-visible">
                        <table class="w-full table-fixed divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <!-- <th class="w-[7%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th> -->
                                    <th class="w-[10%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <!-- <th class="w-[8%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th> -->
                                    <th class="w-[61%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                                    <th class="w-[14%] px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($roles as $role)
                                    <tr>
                                        <!-- <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $role->id }}</td> -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ucfirst($role->name) }}</td>
                                        <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $role->users_count }}</td> -->
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            @if ($role->permissions->isEmpty())
                                                <span class="text-gray-400">No permissions</span>
                                            @else
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($role->permissions as $permission)
                                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">
                                                            {{ ucfirst($permission->feature?->name ?? 'General') }}: {{ ucfirst($permission->name) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <div class="flex w-full justify-end">
                                                <x-dropdown align="right" width="w-40">
                                                    <x-slot name="trigger">
                                                        <button type="button"
                                                            class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">
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
                                                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-50">
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
                                        <td colspan="5" class="px-6 py-4 text-sm text-gray-500">No roles found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $roles->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
