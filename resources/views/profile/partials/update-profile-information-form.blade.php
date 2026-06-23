@php
    $profileUpdateRoute = tenant() ? 'tenant.profile.update' : 'profile.update';
@endphp

<x-card>
    <x-slot name="header">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Profile Information</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Update your account's profile information and email address.</p>
    </x-slot>

    <form id="send-verification" method="post" action="{{ route('verification.send', absolute: false) }}">
        @csrf
    </form>

    <form method="post" action="{{ route($profileUpdateRoute, absolute: false) }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="underline text-sm text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 dark:focus:ring-offset-[#08080c]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
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
