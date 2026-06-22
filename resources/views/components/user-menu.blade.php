@php
    $isTenant = (bool) tenant();
    $profileUrl = $isTenant ? route('tenant.profile.edit', absolute: false) : route('profile.edit', absolute: false);
    $user = Auth::user();
    $initials = strtoupper(substr($user->name, 0, 1));
@endphp

<x-dropdown align="right" width="48">
    <x-slot name="trigger">
        <button class="flex items-center gap-3 rounded-lg p-1.5 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#1e1e28] transition-colors focus:outline-none">
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
        <div class="px-4 py-3 border-b border-gray-100 dark:border-[#2a2a38]">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $user->email }}</p>
        </div>
        <x-dropdown-link :href="$profileUrl" class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-8 10a6 6 0 1112 0H8z" />
            </svg>
            <span>{{ __('Profile') }}</span>
        </x-dropdown-link>

        <div class="border-t border-gray-100 dark:border-[#2a2a38] my-1"></div>

        <form method="POST" action="{{ route('logout', absolute: false) }}">
            @csrf
            <x-dropdown-link :href="route('logout', absolute: false)"
                onclick="event.preventDefault(); this.closest('form').submit();"
                class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-7.5A2.25 2.25 0 003.75 5.25v13.5A2.25 2.25 0 006 21h7.5a2.25 2.25 0 002.25-2.25V15m-6-3h10.5m0 0l-3-3m3 3l-3 3" />
                </svg>
                <span>{{ __('Log Out') }}</span>
            </x-dropdown-link>
        </form>
    </x-slot>
</x-dropdown>
