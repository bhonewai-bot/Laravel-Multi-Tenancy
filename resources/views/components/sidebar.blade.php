@php
    $isTenant = (bool) tenant();
    $homeUrl = $isTenant ? route('dashboard', absolute: false) : route('tenants.index', absolute: false);
@endphp

<aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white text-gray-700 md:flex md:flex-col">
    <!-- <div class="flex h-16 items-center border-b border-gray-200 px-4">
        <a href="{{ $homeUrl }}" class="flex items-center gap-3">
            <x-application-logo class="h-8 w-8 fill-current text-gray-800" />
            <span class="text-sm font-semibold tracking-wide text-gray-800">
                {{ $isTenant ? 'Tenant Panel' : 'Central Panel' }}
            </span>
        </a>
    </div> -->

    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-4">
        @if (! $isTenant)
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Central</p>
                <div x-data="{ open: {{ request()->routeIs('tenants.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button type="button"
                        class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        @click="open = ! open">
                        <span>Tenants</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform"
                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" class="space-y-1 ps-6 text-sm">
                        <a href="{{ route('tenants.index', absolute: false) }}"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('tenants.index') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                            <span class="text-gray-400">•</span>
                            <span>Tenant List</span>
                        </a>
                        <a href="{{ route('tenants.create', absolute: false) }}"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('tenants.create') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                            <span class="text-gray-400">•</span>
                            <span>Add Tenant</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Platform</p>
                <div x-data="{ open: {{ request()->routeIs('modules.*') || request()->routeIs('module-requests.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button type="button"
                        class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        @click="open = ! open">
                        <span>Modules</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform"
                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" class="space-y-1 ps-6 text-sm">
                        <a href="{{ route('modules.index', absolute: false) }}"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('modules.index') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                            <span class="text-gray-400">•</span>
                            <span>Module List</span>
                        </a>
                        <a href="{{ route('modules.create', absolute: false) }}"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('modules.create') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                            <span class="text-gray-400">•</span>
                            <span>Add Module</span>
                        </a>
                        <a href="{{ route('module-requests.index', absolute: false) }}"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('module-requests.*') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                            <span class="text-gray-400">•</span>
                            <span>Module Requests</span>
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Tenant</p>
                <div x-data="{ open: {{ request()->routeIs('tenant.modules.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button type="button"
                        class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        @click="open = ! open">
                        <span>Modules</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform"
                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" class="space-y-1 ps-6 text-sm">
                        <a href="{{ route('tenant.modules.index', absolute: false) }}"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('tenant.modules.*') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                            <span class="text-gray-400">•</span>
                            <span>Available Modules</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Business</p>
                <div x-data="{ open: {{ request()->routeIs('product.*') || request()->routeIs('customer.*') || request()->routeIs('sale.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button type="button"
                        class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        @click="open = ! open">
                        <span>Management</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform"
                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" class="space-y-1 ps-6 text-sm">
                        @if (Route::has('product.index'))
                            <a href="{{ route('product.index', absolute: false) }}"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('product.*') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                                <span class="text-gray-400">•</span>
                                <span>Products</span>
                            </a>
                        @endif
                        @if (Route::has('customer.index'))
                            <a href="{{ route('customer.index', absolute: false) }}"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('customer.*') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                                <span class="text-gray-400">•</span>
                                <span>Customers</span>
                            </a>
                        @endif
                        @if (Route::has('sale.index'))
                            <a href="{{ route('sale.index', absolute: false) }}"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-gray-600 hover:bg-gray-50 hover:text-gray-900 {{ request()->routeIs('sale.*') ? 'bg-gray-100 font-medium text-gray-900' : '' }}">
                                <span class="text-gray-400">•</span>
                                <span>Sales</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </nav>

    <div class="space-y-1 border-t border-gray-200 p-4">
        <a href="{{ $isTenant ? route('tenant.profile.edit', absolute: false) : route('profile.edit', absolute: false) }}"
            class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-8 10a6 6 0 1112 0H8z" />
            </svg>
            <span>Profile</span>
        </a>

        <form method="POST" action="{{ route('logout', absolute: false) }}">
            @csrf
            <button type="submit"
                class="inline-flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-7.5A2.25 2.25 0 003.75 5.25v13.5A2.25 2.25 0 006 21h7.5a2.25 2.25 0 002.25-2.25V15m-6-3h10.5m0 0l-3-3m3 3l-3 3" />
                </svg>
                <span>Log Out</span>
            </button>
        </form>
    </div>
</aside>
