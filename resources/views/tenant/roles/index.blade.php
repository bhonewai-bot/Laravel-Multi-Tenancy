<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Roles</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $roles->count() }} role{{ $roles->count() === 1 ? '' : 's' }}</p>
            </div>
            @can('create', App\Models\Role::class)
                <a href="{{ route('tenant.roles.create', absolute: false) }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    Add Role
                </a>
            @endcan
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6">
                <x-alert variant="success">{{ session('success') }}</x-alert>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6">
                <x-alert variant="error">{{ session('error') }}</x-alert>
            </div>
        @endif

        @if ($roles->isEmpty())
            {{-- Empty State --}}
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center mb-4">
                    <x-heroicon-o-shield-check class="w-6 h-6 text-brand-600 dark:text-brand-400" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">No roles yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Roles define what users can do in this tenant.</p>
                @can('create', App\Models\Role::class)
                    <a href="{{ route('tenant.roles.create', absolute: false) }}">
                        <x-primary-button type="button">
                            <x-heroicon-o-plus class="w-4 h-4" />
                            Add Role
                        </x-primary-button>
                    </a>
                @endcan
            </div>
        @else
            {{-- Table Container --}}
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] overflow-hidden">

                {{-- Desktop Table --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-[#262632] bg-gray-50/50 dark:bg-[#0e0e15]/50">
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Permissions</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Users</th>
                                <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[72px]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#181820]">
                            @foreach ($roles as $role)
                                <tr class="group hover:bg-gray-50/70 dark:hover:bg-[#181820]/70 transition-all duration-150">
                                    {{-- Role Name --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                                <x-heroicon-o-shield-check class="w-5 h-5 text-brand-600 dark:text-brand-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ ucfirst($role->name) }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Permissions --}}
                                    <td class="px-5 py-4">
                                        @if ($role->permissions->isEmpty())
                                            <span class="text-sm text-gray-400 dark:text-gray-500">No permissions</span>
                                        @else
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($role->permissions->take(5) as $permission)
                                                    <x-badge variant="brand">{{ ucfirst($permission->name) }}</x-badge>
                                                @endforeach
                                                @if ($role->permissions->count() > 5)
                                                    <x-badge variant="neutral">+{{ $role->permissions->count() - 5 }} more</x-badge>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Users Count --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $role->users->count() }}</span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-5 py-4 text-right">
                                        <x-dropdown align="right" width="w-40">
                                            <x-slot name="trigger">
                                                <button type="button"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition duration-150">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                    </svg>
                                                </button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <x-dropdown-link :href="route('tenant.roles.show', $role, absolute: false)">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    View details
                                                </x-dropdown-link>

                                                @can('update', $role)
                                                    <x-dropdown-link :href="route('tenant.roles.edit', $role, absolute: false)">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                                        Edit role
                                                    </x-dropdown-link>
                                                @endcan

                                                @can('delete', $role)
                                                    <div class="border-t border-gray-100 dark:border-[#262632] my-1"></div>

                                                    <form method="POST" action="{{ route('tenant.roles.destroy', $role, absolute: false) }}"
                                                        onsubmit="return confirm('Are you sure you want to delete the {{ $role->name }} role? This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition duration-150">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                            Delete role
                                                        </button>
                                                    </form>
                                                @endcan
                                            </x-slot>
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="lg:hidden divide-y divide-gray-100 dark:divide-[#181820]">
                    @foreach ($roles as $role)
                        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-[#181820]/50 transition-colors duration-150">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                        <x-heroicon-o-shield-check class="w-5 h-5 text-brand-600 dark:text-brand-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ ucfirst($role->name) }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $role->users->count() }} user{{ $role->users->count() === 1 ? '' : 's' }}</div>
                                    </div>
                                </div>
                            </div>

                            @if ($role->permissions->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5 mb-3">
                                    @foreach ($role->permissions->take(3) as $permission)
                                        <x-badge variant="brand">{{ ucfirst($permission->name) }}</x-badge>
                                    @endforeach
                                    @if ($role->permissions->count() > 3)
                                        <x-badge variant="neutral">+{{ $role->permissions->count() - 3 }} more</x-badge>
                                    @endif
                                </div>
                            @endif

                            <div class="flex items-center gap-2">
                                <a href="{{ route('tenant.roles.show', $role, absolute: false) }}"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-[#181820] border border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] transition duration-150">
                                    View
                                </a>
                                @can('update', $role)
                                    <a href="{{ route('tenant.roles.edit', $role, absolute: false) }}"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-[#181820] border border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] transition duration-150">
                                        Edit
                                    </a>
                                @endcan
                                @can('delete', $role)
                                    <form method="POST" action="{{ route('tenant.roles.destroy', $role, absolute: false) }}"
                                        onsubmit="return confirm('Delete this role? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center justify-center px-3 py-2 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/20 transition duration-150">
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
