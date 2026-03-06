<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Role</h2>
            <a href="{{ route('tenant.roles.index', absolute: false) }}" class="text-sm text-indigo-600 hover:text-indigo-800">Back to roles</a>
        </div>
    </x-slot>

    <div class="py-8">
        @php
            $selectedPermissions = collect(old('permission_ids', []))->map(fn ($id) => (int) $id);
            $permissionColumns = $features
                ->flatMap(fn ($feature) => $feature->permissions->pluck('name'))
                ->unique()
                ->values();
        @endphp

        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="w-full rounded-lg bg-white shadow-sm sm:rounded-lg">
                <form
                    method="POST"
                    action="{{ route('tenant.roles.store', absolute: false) }}"
                    class="space-y-6 p-6 pt-1"
                    x-data="{
                        toggleRow(selectAllEl) {
                            const row = selectAllEl.closest('tr');
                            row.querySelectorAll('input[data-role-permission]').forEach((checkbox) => {
                                checkbox.checked = selectAllEl.checked;
                            });
                        },
                        syncRow(anyCheckboxEl) {
                            const row = anyCheckboxEl.closest('tr');
                            const all = row.querySelectorAll('input[data-role-permission]');
                            const checked = row.querySelectorAll('input[data-role-permission]:checked');
                            const selectAll = row.querySelector('input[data-role-select-all]');
                            if (selectAll) {
                                selectAll.checked = all.length > 0 && checked.length === all.length;
                            }
                        },
                    }"
                    x-init="$nextTick(() => {
                        $el.querySelectorAll('tbody tr').forEach((row) => {
                            const sampleCheckbox = row.querySelector('input[data-role-permission], input[data-role-select-all]');
                            if (sampleCheckbox) syncRow(sampleCheckbox);
                        });
                    })"
                >
                    @csrf

                    <div class="space-y-2">
                        <h3 class="text-3xl font-semibold text-gray-900">Create Role</h3>
                        <!-- <p class="text-sm text-gray-500">Define a role and assign permissions by feature.</p> -->
                    </div>

                    <div class="space-y-2">
                        <x-input-label for="name" :value="__('Role Name')" />
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('name')"
                            placeholder="Enter role name"
                            required
                        />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">Role Permissions</h3>
                        <x-input-error :messages="$errors->get('permission_ids')" class="mt-2" />

                        <div class="mt-3 overflow-hidden rounded-md border border-gray-200">
                            <table class="w-full table-fixed">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="w-1/3 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Feature</th>
                                        <th class="w-2/3 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Permissions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($features as $feature)
                                        @php $permissionsByName = $feature->permissions->keyBy('name'); @endphp
                                        <tr>
                                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                                {{ ucfirst($feature->name) }}
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-700">
                                                    <label class="inline-flex items-center gap-2 font-medium text-gray-900">
                                                        <input
                                                            type="checkbox"
                                                            data-role-select-all
                                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                            @change="toggleRow($el)"
                                                        >
                                                        <span>Select All</span>
                                                    </label>

                                                    @foreach ($permissionColumns as $permissionName)
                                                        @php $permissionModel = $permissionsByName->get($permissionName); @endphp
                                                        @if ($permissionModel)
                                                            <label class="inline-flex items-center gap-2 whitespace-nowrap">
                                                                <input
                                                                    type="checkbox"
                                                                    name="permission_ids[]"
                                                                    value="{{ $permissionModel->id }}"
                                                                    data-role-permission
                                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                                    @checked($selectedPermissions->contains($permissionModel->id))
                                                                    @change="syncRow($el)"
                                                                >
                                                                <span>{{ ucfirst($permissionName) }}</span>
                                                            </label>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('tenant.roles.index', absolute: false) }}"
                            class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">
                            Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
