<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Role Details</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($role->name) }}</p>
            </div>
            <div class="flex items-center gap-2">
                @can('update', $role)
                    <a href="{{ route('tenant.roles.edit', $role, absolute: false) }}">
                        <x-secondary-button type="button">
                            <x-heroicon-o-pencil class="w-4 h-4" />
                            Edit Role
                        </x-secondary-button>
                    </a>
                @endcan
                <a href="{{ route('tenant.roles.index', absolute: false) }}">
                    <x-secondary-button type="button">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Back to Roles
                    </x-secondary-button>
                </a>
            </div>
        </div>

        {{-- Role Info Card --}}
        <x-card>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($role->name) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Assigned Users</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $role->users->count() }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- Permissions Card --}}
        <div class="mt-6">
            <x-card>
                <x-slot:header>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Permissions</h3>
                </x-slot:header>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @forelse ($role->permissions->groupBy(fn ($permission) => $permission->feature?->name ?? 'other') as $featureName => $permissions)
                        <div class="rounded-lg border border-gray-200 dark:border-[#262632] p-4">
                            <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $featureName }}</h4>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($permissions as $permission)
                                    <x-badge variant="brand">{{ $permission->name }}</x-badge>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <x-empty-state title="No permissions assigned" description="This role has no permissions yet." />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
