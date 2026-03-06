<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Role Details</h2>
            <a href="{{ route('tenant.roles.index', absolute: false) }}" class="text-sm text-indigo-600 hover:text-indigo-800">Back to roles</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="w-full space-y-6">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Role Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($role->name) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Assigned Users</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $role->users->count() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Permissions</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            @forelse ($role->permissions->groupBy(fn ($permission) => $permission->feature?->name ?? 'other') as $featureName => $permissions)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ $featureName }}</h4>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($permissions as $permission)
                                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                                                <!-- {{ $featureName }}. -->{{ $permission->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No permissions assigned.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
