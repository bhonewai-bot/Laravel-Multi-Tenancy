@php
    $homeUrl = tenant() ? route('tenant.dashboard', absolute: false) : route('dashboard', absolute: false);
@endphp

{{-- Mobile overlay — separate from flex container to avoid layout conflicts --}}
<div
    x-show="$store.sidebar.mobileOpen"
    x-cloak
    class="fixed inset-0 z-50 md:hidden"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/50"
        x-show="$store.sidebar.mobileOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$store.sidebar.closeMobile()"
    ></div>

    {{-- Slide-in panel --}}
    <div
        class="fixed inset-y-0 left-0 flex w-64 flex-col border-r border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] text-gray-700 dark:text-gray-200 shadow-xl"
        x-show="$store.sidebar.mobileOpen"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        @click.stop=""
    >
        <div class="flex items-center h-16 border-b border-gray-200 dark:border-[#262632] px-4">
            <a href="{{ $homeUrl }}" class="flex items-center gap-2">
                <x-application-logo class="w-6 h-6 shrink-0" />
                <span class="text-[13px] font-bold tracking-tight text-gray-800 dark:text-gray-100">TenantSmith</span>
            </a>
            <button
                @click="$store.sidebar.closeMobile()"
                class="ml-auto p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820] transition-colors shrink-0"
            >
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>
        <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-3" @click="$store.sidebar.closeMobile()">
            @include('components._sidebar-nav', ['mobile' => true])
        </nav>
    </div>
</div>

{{-- Desktop sidebar --}}
<aside
    x-data
    class="hidden md:flex md:flex-col shrink-0 border-r border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] text-gray-700 dark:text-gray-200 transition-all duration-300"
    :class="$store.sidebar.collapsed ? 'w-16' : 'w-56'"
>
    <div class="flex items-center h-16 border-b border-gray-200 dark:border-[#262632] px-4">
        <a href="{{ $homeUrl }}" class="flex items-center gap-2 overflow-hidden">
            <x-application-logo class="w-6 h-6 shrink-0" />
            <span x-show="!$store.sidebar.collapsed" x-cloak class="text-[13px] font-bold tracking-tight text-gray-800 dark:text-gray-100 whitespace-nowrap translate-y-[0.5px]">TenantSmith</span>
        </a>
        <button
            @click="$store.sidebar.toggle()"
            class="ml-auto p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820] transition-colors shrink-0"
            :aria-label="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
            <x-heroicon-o-chevron-double-left x-show="!$store.sidebar.collapsed" class="w-4 h-4" />
            <x-heroicon-o-chevron-double-right x-show="$store.sidebar.collapsed" x-cloak class="w-4 h-4" />
        </button>
    </div>
    <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-3">
        @include('components._sidebar-nav', ['mobile' => false])
    </nav>
</aside>
