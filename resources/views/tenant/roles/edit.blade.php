<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Role</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($role->name) }}</p>
            </div>
            <a href="{{ route('tenant.roles.index', absolute: false) }}">
                <x-secondary-button type="button">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Back to Roles
                </x-secondary-button>
            </a>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="mb-6">
                <x-alert variant="error">Please fix the errors below.</x-alert>
            </div>
        @endif

        @php
            $selectedPermissions = collect(old('permission_ids', $role->permissions->pluck('id')->all()))->map(fn ($id) => (int) $id);
            $permissionColumns = $features
                ->flatMap(fn ($feature) => $feature->permissions->pluck('name'))
                ->unique()
                ->values();
        @endphp

        {{-- Form --}}
        <form
            method="POST"
            action="{{ route('tenant.roles.update', $role, absolute: false) }}"
            x-data="{
                submitting: false,
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
            @submit="submitting = true"
            x-init="$nextTick(() => {
                $el.querySelectorAll('tbody tr').forEach((row) => {
                    const sampleCheckbox = row.querySelector('input[data-role-permission], input[data-role-select-all]');
                    if (sampleCheckbox) syncRow(sampleCheckbox);
                });
            })"
        >
            @csrf
            @method('PATCH')
            <x-card>
                <div class="space-y-6">
                    <div class="space-y-2">
                        <x-input-label for="name" :value="__('Role Name')" />
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full rounded-lg"
                            :value="old('name', $role->name)"
                            placeholder="Enter role name"
                            required
                        />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Role Permissions</h3>
                        <x-input-error :messages="$errors->get('permission_ids')" class="mt-2" />

                        <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 dark:border-[#262632]">
                            <table class="w-full table-fixed">
                                <thead class="bg-gray-50 dark:bg-[#0e0e15]">
                                    <tr>
                                        <th class="w-1/3 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Feature</th>
                                        <th class="w-2/3 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Permissions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-[#262632] bg-white dark:bg-[#101016]">
                                    @foreach ($features as $feature)
                                        @php $permissionsByName = $feature->permissions->keyBy('name'); @endphp
                                        <tr class="border-t border-gray-200 dark:border-[#262632] hover:bg-gray-50 dark:hover:bg-[#181820]">
                                            <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ ucfirst($feature->name) }}
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-700 dark:text-gray-300">
                                                    <label class="inline-flex items-center gap-2 font-medium text-gray-900 dark:text-gray-100">
                                                        <input
                                                            type="checkbox"
                                                            data-role-select-all
                                                            class="rounded border-gray-300 dark:border-[#262632] text-brand-600 shadow-card focus:ring-brand-500"
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
                                                                    class="rounded border-gray-300 dark:border-[#262632] text-brand-600 shadow-card focus:ring-brand-500"
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
                </div>

                <x-slot name="footer">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('tenant.roles.index', absolute: false) }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                        <button type="submit" :disabled="submitting"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                            <span x-show="!submitting">UPDATE ROLE</span>
                            <span x-show="submitting" x-cloak>UPDATING...</span>
                        </button>
                    </div>
                </x-slot>
            </x-card>
        </form>
    </div>
</x-app-layout>
