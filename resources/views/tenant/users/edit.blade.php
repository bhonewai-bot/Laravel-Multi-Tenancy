<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit User</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $user->name }}</p>
            </div>
            <a href="{{ route('tenant.users.index', absolute: false) }}">
                <x-secondary-button type="button">Back to Users</x-secondary-button>
            </a>
        </div>

        {{-- Errors --}}
        @if (session('error'))
            <div class="mb-6">
                <x-alert variant="error">{{ session('error') }}</x-alert>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6">
                <x-alert variant="error">Please fix the errors below.</x-alert>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('tenant.users.update', $user, absolute: false) }}" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('PATCH')
            <x-card>
                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-lg" :value="old('name', $user->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-lg" :value="old('email', $user->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="role_id" :value="__('Role')" />
                        <select id="role_id" name="role_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-[#262632] dark:bg-[#101016] dark:text-gray-100 shadow-card focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Select role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('New Password (optional)')" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full rounded-lg" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-lg" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <x-slot name="footer">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('tenant.users.index', absolute: false) }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                        <x-primary-button x-bind:disabled="submitting" type="submit">
                            <span x-show="!submitting">UPDATE USER</span>
                            <span x-show="submitting" x-cloak>UPDATING...</span>
                        </x-primary-button>
                    </div>
                </x-slot>
            </x-card>
        </form>
    </div>
</x-app-layout>
