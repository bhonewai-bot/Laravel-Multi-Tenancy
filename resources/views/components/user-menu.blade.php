@php
    $isTenant = (bool) tenant();
    $profileUrl = $isTenant ? route('tenant.profile.edit', absolute: false) : route('profile.edit', absolute: false);
    $user = Auth::user();
    $initials = strtoupper(substr($user->name, 0, 1));
@endphp

<x-dropdown align="right" width="48">
    <x-slot name="trigger">
        <button class="flex items-center gap-3 rounded-lg p-1.5 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#181820] transition-colors focus:outline-none">
            <div class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-sm font-semibold text-brand-700 dark:text-brand-300">
                {{ $initials }}
            </div>
            <span class="hidden sm:block font-medium text-gray-700 dark:text-gray-200">{{ $user->name }}</span>
            <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-[#262632]">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $user->email }}</p>
        </div>
        <x-dropdown-link :href="$profileUrl" class="flex items-center gap-2">
            <x-heroicon-o-user-circle class="w-4 h-4 text-gray-400" />
            <span>{{ __('Profile') }}</span>
        </x-dropdown-link>

        <div class="border-t border-gray-100 dark:border-[#262632] my-1"></div>

        <form method="POST" action="{{ $isTenant ? route('tenant.logout', absolute: false) : route('logout', absolute: false) }}">
            @csrf
            <x-dropdown-link :href="$isTenant ? route('tenant.logout', absolute: false) : route('logout', absolute: false)"
                onclick="event.preventDefault(); this.closest('form').submit();"
                class="flex items-center gap-2">
                <x-heroicon-o-arrow-left-start-on-rectangle class="w-4 h-4 text-gray-400" />
                <span>{{ __('Log Out') }}</span>
            </x-dropdown-link>
        </form>
    </x-slot>
</x-dropdown>
