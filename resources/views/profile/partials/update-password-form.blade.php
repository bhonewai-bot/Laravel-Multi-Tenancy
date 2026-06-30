<x-card>
    <x-slot name="header">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Update Password</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ensure your account is using a long, random password to stay secure.</p>
    </x-slot>

    <form method="post" action="{{ tenant() ? route('tenant.password.update', absolute: false) : route('password.update', absolute: false) }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button x-bind:disabled="submitting" type="submit">
                <span x-show="!submitting">{{ __('Save') }}</span>
                <span x-show="submitting" x-cloak>{{ __('Saving...') }}</span>
            </x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600 dark:text-green-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</x-card>
