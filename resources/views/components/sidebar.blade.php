@php
    $isTenant = (bool) tenant();
    $homeUrl = route('dashboard', absolute: false);
    $tenantUser = auth()->user();
    $canViewUsers = $isTenant && $tenantUser && ($tenantUser->hasRole('admin') || $tenantUser->hasPermission('user.read'));
    $canViewRoles = $isTenant && $tenantUser && ($tenantUser->hasRole('admin') || $tenantUser->hasPermission('role.read'));
@endphp

<aside
    x-data
    class="hidden md:flex md:flex-col shrink-0 border-r border-gray-200 dark:border-[#2a2a38] bg-white dark:bg-[#14141c] text-gray-700 dark:text-gray-200 transition-all duration-300"
    :class="$store.sidebar.collapsed ? 'w-16' : 'w-64'"
>

    {{-- Logo + Toggle --}}
    <div class="flex items-center h-16 border-b border-gray-200 dark:border-[#2a2a38] px-4">
        <a href="{{ $homeUrl }}" class="flex items-center gap-2.5 overflow-hidden">
            <x-application-logo class="w-8 h-8 shrink-0" />
            <span x-show="!$store.sidebar.collapsed" x-cloak class="text-sm font-semibold text-gray-800 dark:text-gray-100 whitespace-nowrap">TenantSmith</span>
        </a>
        <button
            @click="$store.sidebar.toggle()"
            class="ml-auto p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1e1e28] transition-colors shrink-0"
            :aria-label="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
            <svg x-show="!$store.sidebar.collapsed" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
            <svg x-show="$store.sidebar.collapsed" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">

        @if (! $isTenant)
            {{-- Central: Overview --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Overview</p>

                <a href="{{ route('dashboard', absolute: false) }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100' }}"
                    :title="$store.sidebar.collapsed ? 'Dashboard' : ''">
                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" />
                    </svg>
                    <span x-show="!$store.sidebar.collapsed" x-cloak class="whitespace-nowrap">Dashboard</span>
                </a>
            </div>

            {{-- Central: Tenants --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Central</p>

                <div x-data="{ open: {{ request()->routeIs('tenants.*') ? 'true' : 'false' }} }">
                    <button type="button"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] {{ request()->routeIs('tenants.*') ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100' : '' }}"
                        @click="open = ! open"
                        :title="$store.sidebar.collapsed ? 'Tenants' : ''">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('tenants.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Tenants</span>
                        <svg x-show="!$store.sidebar.collapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform shrink-0"
                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    {{-- Expanded submenu --}}
                    <div x-show="open && !$store.sidebar.collapsed" x-cloak x-collapse class="ps-4 mt-1 space-y-0.5">
                        <a href="{{ route('tenants.index', absolute: false) }}"
                            class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('tenants.index') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#1e1e28] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Tenant List
                        </a>
                        <a href="{{ route('tenants.create', absolute: false) }}"
                            class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('tenants.create') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#1e1e28] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Add Tenant
                        </a>
                    </div>

                    {{-- Collapsed flyout --}}
                    <div
                        x-show="$store.sidebar.collapsed && open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-x-1"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="absolute left-16 mt-[-2.5rem] w-48 rounded-lg bg-white dark:bg-[#14141c] border border-gray-200 dark:border-[#2a2a38] shadow-lg py-1 z-50"
                        @click.outside="open = false"
                    >
                        <a href="{{ route('tenants.index', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1e1e28]">Tenant List</a>
                        <a href="{{ route('tenants.create', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1e1e28]">Add Tenant</a>
                    </div>
                </div>
            </div>

            {{-- Central: Platform --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Platform</p>

                <div x-data="{ open: {{ request()->routeIs('modules.*') || request()->routeIs('module-requests.*') ? 'true' : 'false' }} }">
                    <button type="button"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] {{ request()->routeIs('modules.*') || request()->routeIs('module-requests.*') ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100' : '' }}"
                        @click="open = ! open"
                        :title="$store.sidebar.collapsed ? 'Modules' : ''">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('modules.*') || request()->routeIs('module-requests.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" />
                        </svg>
                        <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Modules</span>
                        <svg x-show="!$store.sidebar.collapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform shrink-0"
                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="open && !$store.sidebar.collapsed" x-cloak x-collapse class="ps-4 mt-1 space-y-0.5">
                        <a href="{{ route('modules.index', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('modules.index') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#1e1e28] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Module List
                        </a>
                        <a href="{{ route('modules.create', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('modules.create') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#1e1e28] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Add Module
                        </a>
                        <a href="{{ route('module-requests.index', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('module-requests.*') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#1e1e28] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Module Requests
                        </a>
                    </div>

                    <div
                        x-show="$store.sidebar.collapsed && open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-x-1"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="absolute left-16 mt-[-2.5rem] w-48 rounded-lg bg-white dark:bg-[#14141c] border border-gray-200 dark:border-[#2a2a38] shadow-lg py-1 z-50"
                        @click.outside="open = false"
                    >
                        <a href="{{ route('modules.index', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1e1e28]">Module List</a>
                        <a href="{{ route('modules.create', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1e1e28]">Add Module</a>
                        <a href="{{ route('module-requests.index', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1e1e28]">Module Requests</a>
                    </div>
                </div>
            </div>

        @else
            {{-- Tenant: Overview --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Overview</p>

                <a href="{{ route('dashboard', absolute: false) }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100' }}"
                    :title="$store.sidebar.collapsed ? 'Dashboard' : ''">
                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" />
                    </svg>
                    <span x-show="!$store.sidebar.collapsed" x-cloak class="whitespace-nowrap">Dashboard</span>
                </a>
            </div>

            {{-- Tenant: Modules --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Platform</p>

                <a href="{{ route('tenant.modules.index', absolute: false) }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] {{ request()->routeIs('tenant.modules.*') ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100' : '' }}"
                    :title="$store.sidebar.collapsed ? 'Modules' : ''">
                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('tenant.modules.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" />
                    </svg>
                    <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Modules</span>
                </a>
            </div>

            {{-- Tenant: Users & Roles --}}
            @if ($canViewUsers || $canViewRoles)
                <div class="space-y-1">
                    <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Access</p>

                    @if ($canViewUsers)
                        <a href="{{ route('tenant.users.index', absolute: false) }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] {{ request()->routeIs('tenant.users.*') ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100' : '' }}"
                            :title="$store.sidebar.collapsed ? 'Users' : ''">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('tenant.users.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Users</span>
                        </a>
                    @endif

                    @if ($canViewRoles)
                        <a href="{{ route('tenant.roles.index', absolute: false) }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] {{ request()->routeIs('tenant.roles.*') ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100' : '' }}"
                            :title="$store.sidebar.collapsed ? 'Roles' : ''">
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('tenant.roles.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Roles</span>
                        </a>
                    @endif
                </div>
            @endif

            {{-- Tenant: Custom Domains --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Network</p>

                <div x-data="{ open: {{ request()->routeIs('tenant.domains.*') ? 'true' : 'false' }} }">
                    <button type="button"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] {{ request()->routeIs('tenant.domains.*') ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100' : '' }}"
                        @click="open = ! open"
                        :title="$store.sidebar.collapsed ? 'Domains' : ''">
                        <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('tenant.domains.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Custom Domains</span>
                        <svg x-show="!$store.sidebar.collapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform shrink-0"
                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="open && !$store.sidebar.collapsed" x-cloak x-collapse class="ps-4 mt-1 space-y-0.5">
                        <a href="{{ route('tenant.domains.index', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('tenant.domains.index') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#1e1e28] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            My Domains
                        </a>
                        <a href="{{ route('tenant.domains.create', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('tenant.domains.create') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#1e1e28] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Add Domain
                        </a>
                    </div>

                    <div
                        x-show="$store.sidebar.collapsed && open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-x-1"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="absolute left-16 mt-[-2.5rem] w-48 rounded-lg bg-white dark:bg-[#14141c] border border-gray-200 dark:border-[#2a2a38] shadow-lg py-1 z-50"
                        @click.outside="open = false"
                    >
                        <a href="{{ route('tenant.domains.index', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1e1e28]">My Domains</a>
                        <a href="{{ route('tenant.domains.create', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1e1e28]">Add Domain</a>
                    </div>
                </div>
            </div>
        @endif
    </nav>

    {{-- Bottom: Profile + Logout --}}
    <div class="border-t border-gray-200 dark:border-[#2a2a38] p-3 space-y-1">
        <a href="{{ $isTenant ? route('tenant.profile.edit', absolute: false) : route('profile.edit', absolute: false) }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
            :title="$store.sidebar.collapsed ? 'Profile' : ''">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-8 10a6 6 0 1112 0H8z" />
            </svg>
            <span x-show="!$store.sidebar.collapsed" x-cloak class="whitespace-nowrap">Profile</span>
        </a>

        <form method="POST" action="{{ route('logout', absolute: false) }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#1e1e28] text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                :title="$store.sidebar.collapsed ? 'Log Out' : ''">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-7.5A2.25 2.25 0 003.75 5.25v13.5A2.25 2.25 0 006 21h7.5a2.25 2.25 0 002.25-2.25V15m-6-3h10.5m0 0l-3-3m3 3l-3 3" />
                </svg>
                <span x-show="!$store.sidebar.collapsed" x-cloak class="whitespace-nowrap">Log Out</span>
            </button>
        </form>
    </div>
</aside>
