@php
    $profileDestroyRoute = tenant() ? 'tenant.profile.destroy' : 'profile.destroy';
@endphp

<x-card class="border-red-200 dark:border-red-900/50">
    <x-slot name="header">
        <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">Delete Account</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
    </x-slot>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {{ __('Before deleting your account, please download any data or information that you wish to retain.') }}
    </p>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route($profileDestroyRoute, absolute: false) }}" class="p-6" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button x-bind:disabled="submitting" type="submit" class="ms-3">
                    <span x-show="!submitting">{{ __('Delete Account') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Deleting...') }}</span>
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</x-card>
