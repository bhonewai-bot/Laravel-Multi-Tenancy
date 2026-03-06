<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Details</h2>
            <a href="{{ route('tenant.users.index', absolute: false) }}" class="text-sm text-indigo-600 hover:text-indigo-800">Back to users</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="w-full space-y-6">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Role</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($user->role->name ?? 'N/A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ optional($user->created_at)->format('Y-m-d H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Permissions (via Role)</h3>
                        <!-- <p class="mt-1 text-sm text-gray-500">Inherited from role <strong>{{ ucfirst($user->role->name ?? 'N/A') }}</strong>.</p> -->

                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse (($user->role?->permissions ?? collect()) as $permission)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                                    {{ $permission->feature?->name }}.{{ $permission->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-500">No permissions found for this role.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
