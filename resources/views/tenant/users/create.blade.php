<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Add User"
            description="Create a new user for this tenant."
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
            <form method="POST" action="{{ route('tenant.users.store', absolute: false) }}" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-lg" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-lg" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="role_id" :value="__('Role')" />
                    <select id="role_id" name="role_id"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-[#2a2a38] dark:bg-[#14141c] dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Select role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full rounded-lg" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-lg" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('tenant.users.index', absolute: false) }}">
                        <x-secondary-button type="button">Cancel</x-secondary-button>
                    </a>
                    <x-primary-button>Create User</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
