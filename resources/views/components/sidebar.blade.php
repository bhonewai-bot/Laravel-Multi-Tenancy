@php
    $isTenant = (bool) tenant();
    $homeUrl = route('dashboard', absolute: false);
    $tenantUser = auth()->user();
    $canViewUsers = $isTenant && $tenantUser && ($tenantUser->hasRole('admin') || $tenantUser->hasPermission('user.read'));
    $canViewRoles = $isTenant && $tenantUser && ($tenantUser->hasRole('admin') || $tenantUser->hasPermission('role.read'));
@endphp

<aside
    x-data
    class="hidden md:flex md:flex-col shrink-0 border-r border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] text-gray-700 dark:text-gray-200 transition-all duration-300"
    :class="$store.sidebar.collapsed ? 'w-16' : 'w-56'"
>

    {{-- Logo + Toggle --}}
    <div class="flex items-center h-16 border-b border-gray-200 dark:border-[#262632] px-4">
        <a href="{{ $homeUrl }}" class="flex items-center gap-2.5 overflow-hidden">
            <x-application-logo class="w-8 h-8 shrink-0" />
            <span x-show="!$store.sidebar.collapsed" x-cloak class="text-sm font-semibold text-gray-800 dark:text-gray-100 whitespace-nowrap">TenantSmith</span>
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

        @if (! $isTenant)
            {{-- Central: Overview --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Overview</p>

                <a href="{{ route('dashboard', absolute: false) }}"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#181820] {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100' }}"
                    :title="$store.sidebar.collapsed ? 'Dashboard' : ''">
                    <x-heroicon-o-home class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" />
                    <span x-show="!$store.sidebar.collapsed" x-cloak class="whitespace-nowrap">Dashboard</span>
                </a>
            </div>

            {{-- Central: Tenants --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Central</p>

                <div x-data="{ open: {{ request()->routeIs('tenants.*') ? 'true' : 'false' }} }">
                    <button type="button"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#181820] {{ request()->routeIs('tenants.*') ? 'bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100' : '' }}"
                        @click="open = ! open"
                        :title="$store.sidebar.collapsed ? 'Tenants' : ''">
                        <x-heroicon-o-user-group class="w-5 h-5 shrink-0 {{ request()->routeIs('tenants.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" />
                        <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Tenants</span>
                        <x-heroicon-o-chevron-down x-show="!$store.sidebar.collapsed" class="h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform shrink-0" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>

                    {{-- Expanded submenu --}}
                    <div x-show="open && !$store.sidebar.collapsed" x-cloak x-collapse class="ps-4 mt-1 space-y-0.5">
                        <a href="{{ route('tenants.index', absolute: false) }}"
                            class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('tenants.index') ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Tenant List
                        </a>
                        <a href="{{ route('tenants.create', absolute: false) }}"
                            class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('tenants.create') ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100' }}">
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
                        class="absolute left-16 mt-[-2.5rem] w-48 rounded-lg bg-white dark:bg-[#101016] border border-gray-200 dark:border-[#262632] shadow-lg py-1 z-50"
                        @click.outside="open = false"
                    >
                        <a href="{{ route('tenants.index', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820]">Tenant List</a>
                        <a href="{{ route('tenants.create', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820]">Add Tenant</a>
                    </div>
                </div>
            </div>

            {{-- Central: Platform --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Platform</p>

                <div x-data="{ open: {{ request()->routeIs('modules.*') || request()->routeIs('module-requests.*') ? 'true' : 'false' }} }">
                    <button type="button"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#181820] {{ request()->routeIs('modules.*') || request()->routeIs('module-requests.*') ? 'bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100' : '' }}"
                        @click="open = ! open"
                        :title="$store.sidebar.collapsed ? 'Modules' : ''">
                        <x-heroicon-o-squares-2x2 class="w-5 h-5 shrink-0 {{ request()->routeIs('modules.*') || request()->routeIs('module-requests.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" />
                        <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Modules</span>
                        <x-heroicon-o-chevron-down x-show="!$store.sidebar.collapsed" class="h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform shrink-0" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="open && !$store.sidebar.collapsed" x-cloak x-collapse class="ps-4 mt-1 space-y-0.5">
                        <a href="{{ route('modules.index', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('modules.index') ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Module List
                        </a>
                        <a href="{{ route('modules.create', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('modules.create') ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Add Module
                        </a>
                        <a href="{{ route('module-requests.index', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('module-requests.*') ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Module Requests
                        </a>
                    </div>

                    <div
                        x-show="$store.sidebar.collapsed && open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-x-1"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="absolute left-16 mt-[-2.5rem] w-48 rounded-lg bg-white dark:bg-[#101016] border border-gray-200 dark:border-[#262632] shadow-lg py-1 z-50"
                        @click.outside="open = false"
                    >
                        <a href="{{ route('modules.index', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820]">Module List</a>
                        <a href="{{ route('modules.create', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820]">Add Module</a>
                        <a href="{{ route('module-requests.index', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820]">Module Requests</a>
                    </div>
                </div>
            </div>

        @else
            {{-- Tenant: Overview --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Overview</p>

                <a href="{{ route('dashboard', absolute: false) }}"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#181820] {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100' }}"
                    :title="$store.sidebar.collapsed ? 'Dashboard' : ''">
                    <x-heroicon-o-home class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" />
                    <span x-show="!$store.sidebar.collapsed" x-cloak class="whitespace-nowrap">Dashboard</span>
                </a>
            </div>

            {{-- Tenant: Modules --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Platform</p>

                <a href="{{ route('tenant.modules.index', absolute: false) }}"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#181820] {{ request()->routeIs('tenant.modules.*') ? 'bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100' : '' }}"
                    :title="$store.sidebar.collapsed ? 'Modules' : ''">
                    <x-heroicon-o-squares-2x2 class="w-5 h-5 shrink-0 {{ request()->routeIs('tenant.modules.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" />
                    <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Modules</span>
                </a>
            </div>

            {{-- Tenant: Users & Roles --}}
            @if ($canViewUsers || $canViewRoles)
                <div class="space-y-1">
                    <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Access</p>

                    @if ($canViewUsers)
                        <a href="{{ route('tenant.users.index', absolute: false) }}"
                            class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#181820] {{ request()->routeIs('tenant.users.*') ? 'bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100' : '' }}"
                            :title="$store.sidebar.collapsed ? 'Users' : ''">
                            <x-heroicon-o-user class="w-5 h-5 shrink-0 {{ request()->routeIs('tenant.users.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" />
                            <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Users</span>
                        </a>
                    @endif

                    @if ($canViewRoles)
                        <a href="{{ route('tenant.roles.index', absolute: false) }}"
                            class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#181820] {{ request()->routeIs('tenant.roles.*') ? 'bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100' : '' }}"
                            :title="$store.sidebar.collapsed ? 'Roles' : ''">
                            <x-heroicon-o-shield-check class="w-5 h-5 shrink-0 {{ request()->routeIs('tenant.roles.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" />
                            <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Roles</span>
                        </a>
                    @endif
                </div>
            @endif

            {{-- Tenant: Custom Domains --}}
            <div class="space-y-1">
                <p x-show="!$store.sidebar.collapsed" x-cloak class="px-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Network</p>

                <div x-data="{ open: {{ request()->routeIs('tenant.domains.*') ? 'true' : 'false' }} }">
                    <button type="button"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-[#181820] {{ request()->routeIs('tenant.domains.*') ? 'bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100' : '' }}"
                        @click="open = ! open"
                        :title="$store.sidebar.collapsed ? 'Domains' : ''">
                        <x-heroicon-o-globe-alt class="w-5 h-5 shrink-0 {{ request()->routeIs('tenant.domains.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" />
                        <span x-show="!$store.sidebar.collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">Custom Domains</span>
                        <x-heroicon-o-chevron-down x-show="!$store.sidebar.collapsed" class="h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform shrink-0" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>

                    <div x-show="open && !$store.sidebar.collapsed" x-cloak x-collapse class="ps-4 mt-1 space-y-0.5">
                        <a href="{{ route('tenant.domains.index', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('tenant.domains.index') ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            My Domains
                        </a>
                        <a href="{{ route('tenant.domains.create', absolute: false) }}"
                            class="block rounded-lg px-3 py-1.5 text-sm transition-colors {{ request()->routeIs('tenant.domains.create') ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100' }}">
                            Add Domain
                        </a>
                    </div>

                    <div
                        x-show="$store.sidebar.collapsed && open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-x-1"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="absolute left-16 mt-[-2.5rem] w-48 rounded-lg bg-white dark:bg-[#101016] border border-gray-200 dark:border-[#262632] shadow-lg py-1 z-50"
                        @click.outside="open = false"
                    >
                        <a href="{{ route('tenant.domains.index', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820]">My Domains</a>
                        <a href="{{ route('tenant.domains.create', absolute: false) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#181820]">Add Domain</a>
                    </div>
                </div>
            </div>
        @endif
    </nav>
</aside>
