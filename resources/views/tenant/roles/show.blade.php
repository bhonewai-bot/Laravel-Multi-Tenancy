<x-app-layout>
    <x-page-header title="Role Details">
        <x-slot:actions>
            <a href="{{ route('tenant.roles.index', absolute: false) }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-[#101016] border border-gray-300 dark:border-[#262632] rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 shadow-card hover:bg-gray-50 dark:hover:bg-[#181820] transition">
                Back to Roles
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="space-y-6">
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
