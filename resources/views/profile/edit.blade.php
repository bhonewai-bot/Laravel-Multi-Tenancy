@php
    $user = Auth::user();
    $initials = strtoupper(substr($user->name, 0, 1));
@endphp

<x-app-layout>
    <div class="animate-fade-up max-w-4xl">
        {{-- Profile Header --}}
        <div class="mb-8 flex items-center gap-5">
            <div class="w-16 h-16 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-xl font-bold text-brand-700 dark:text-brand-300 shrink-0">
                {{ $initials }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            </div>
        </div>

        {{-- Tabs --}}
        <div x-data="{ activeTab: 'profile' }">
            <div class="flex gap-1 border-b border-gray-200 dark:border-[#2a2a38] mb-6">
                <button
                    @click="activeTab = 'profile'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                    :class="activeTab === 'profile'
                        ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'"
                >
                    Profile Information
                </button>
                <button
                    @click="activeTab = 'security'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                    :class="activeTab === 'security'
                        ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'"
                >
                    Security
                </button>
                <button
                    @click="activeTab = 'danger'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                    :class="activeTab === 'danger'
                        ? 'border-red-500 text-red-600 dark:text-red-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'"
                >
                    Danger Zone
                </button>
            </div>

            {{-- Profile Tab --}}
            <div x-show="activeTab === 'profile'" x-cloak>
                @include('profile.partials.update-profile-information-form')
            </div>

            {{-- Security Tab --}}
            <div x-show="activeTab === 'security'" x-cloak>
                @include('profile.partials.update-password-form')
            </div>

            {{-- Danger Zone Tab --}}
            <div x-show="activeTab === 'danger'" x-cloak>
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
