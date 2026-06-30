<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">User Details</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $user->name }}</p>
            </div>
            <div class="flex items-center gap-2">
                @can('update', $user)
                    <a href="{{ route('tenant.users.edit', $user, absolute: false) }}">
                        <x-secondary-button type="button">Edit User</x-secondary-button>
                    </a>
                @endcan
                <a href="{{ route('tenant.users.index', absolute: false) }}">
                    <x-secondary-button type="button">Back to Users</x-secondary-button>
                </a>
            </div>
        </div>

        {{-- User Info Card --}}
        <x-card>
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</dt>
                    <dd class="mt-1">
                        @if ($user->role?->name)
                            <x-badge variant="brand">{{ ucfirst($user->role->name) }}</x-badge>
                        @else
                            <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ optional($user->created_at)->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- Permissions Card --}}
        <div class="mt-6">
            <x-card>
                <x-slot name="header">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Permissions (via Role)</h3>
                </x-slot>

                <div class="flex flex-wrap gap-2">
                    @forelse (($user->role?->permissions ?? collect()) as $permission)
                        <x-badge variant="brand">{{ $permission->feature?->name }}.{{ $permission->name }}</x-badge>
                    @empty
                        <x-empty-state title="No permissions" description="No permissions are assigned to this user's role." />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
