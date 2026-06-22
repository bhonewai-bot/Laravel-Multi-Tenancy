<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="User Details"
            description="{{ $user->name }}"
        >
            <x-slot name="actions">
                <a href="{{ route('tenant.users.index', absolute: false) }}">
                    <x-secondary-button type="button">Back to Users</x-secondary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
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
                            <span class="text-sm text-gray-400 dark:text-gray-500">N/A</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ optional($user->created_at)->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>
        </x-card>

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
</x-app-layout>
