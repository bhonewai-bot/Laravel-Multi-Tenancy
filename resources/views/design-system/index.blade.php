@php
    $sections = [
        ['id' => 'colors', 'label' => 'Colors'],
        ['id' => 'typography', 'label' => 'Typography'],
        ['id' => 'buttons', 'label' => 'Buttons'],
        ['id' => 'inputs', 'label' => 'Inputs'],
        ['id' => 'badges', 'label' => 'Badges'],
        ['id' => 'cards', 'label' => 'Cards'],
        ['id' => 'data-display', 'label' => 'Data Display'],
        ['id' => 'navigation', 'label' => 'Navigation'],
        ['id' => 'feedback', 'label' => 'Feedback'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Design System" description="TenantSmith component library and visual reference. Every component, both themes.">
            <x-slot name="actions">
                <x-theme-toggle />
            </x-slot>
        </x-page-header>
    </x-slot>

    {{-- Section Nav --}}
    <nav class="sticky top-16 z-20 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-2 bg-gray-100/80 dark:bg-[#08080c]/80 backdrop-blur-xl border-b border-gray-200 dark:border-[#262632] mb-8">
        <div class="flex gap-1 overflow-x-auto scrollbar-hide">
            @foreach ($sections as $section)
                <a href="#{{ $section['id'] }}"
                    class="shrink-0 px-3 py-1.5 text-xs font-medium rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-200/60 dark:hover:bg-[#181820] transition-colors">
                    {{ $section['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    <div class="space-y-16">

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- COLORS --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="colors" class="scroll-mt-24">
            <x-ds-heading title="Colors" description="Brand palette, dark surfaces, and feedback colors." />

            {{-- Brand Palette --}}
            <x-ds-subheading label="Brand (Primary Accent)" />
            <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
                @foreach (['50' => '#eef2ff', '100' => '#e0e7ff', '200' => '#c7d2fe', '300' => '#a5b4fc', '400' => '#818cf8', '500' => '#6366f1', '600' => '#4f46e5', '700' => '#4338ca', '800' => '#3730a3', '900' => '#312e81'] as $shade => $hex)
                    <div class="text-center">
                        <div class="h-12 rounded-lg border border-gray-200 dark:border-[#262632]" style="background-color: {{ $hex }}"></div>
                        <p class="mt-1 text-[10px] font-mono text-gray-500 dark:text-gray-400">{{ $shade }}</p>
                        <p class="text-[10px] font-mono text-gray-400 dark:text-gray-500">{{ $hex }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Dark Surfaces --}}
            <x-ds-subheading label="Dark Theme Surfaces" class="mt-8" />
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach (['Page bg' => '#08080c', 'Surface' => '#101016', 'Elevated' => '#181820', 'Border' => '#262632'] as $name => $hex)
                    <div>
                        <div class="h-14 rounded-lg border border-[#262632]" style="background-color: {{ $hex }}"></div>
                        <p class="mt-1.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $name }}</p>
                        <p class="text-[10px] font-mono text-gray-400 dark:text-gray-500">{{ $hex }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Feedback Colors --}}
            <x-ds-subheading label="Feedback" class="mt-8" />
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach (['success' => ['bg-green-500', 'text-green-600 dark:text-green-400'], 'warning' => ['bg-amber-500', 'text-amber-600 dark:text-amber-400'], 'danger' => ['bg-red-500', 'text-red-600 dark:text-red-400'], 'info' => ['bg-blue-500', 'text-blue-600 dark:text-blue-400']] as $name => $colors)
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-white dark:bg-[#101016] border border-gray-200 dark:border-[#262632]">
                        <div class="w-3 h-3 rounded-full {{ $colors[0] }}"></div>
                        <span class="text-sm font-medium {{ $colors[1] }}">{{ ucfirst($name) }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- TYPOGRAPHY --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="typography" class="scroll-mt-24">
            <x-ds-heading title="Typography" description="Figtree font family. Consistent sizing and weight scale." />

            <div class="space-y-4">
                <x-ds-demo label="Page Heading — text-2xl font-bold">
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">Manage your tenants</p>
                </x-ds-demo>
                <x-ds-demo label="Section Heading — text-lg font-semibold">
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Activity</p>
                </x-ds-demo>
                <x-ds-demo label="Body — text-sm text-gray-700 dark:text-gray-300">
                    <p class="text-sm text-gray-700 dark:text-gray-300">Your workspace is ready. Start by adding tenants or exploring modules.</p>
                </x-ds-demo>
                <x-ds-demo label="Muted — text-sm text-gray-500 dark:text-gray-400">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Last updated 3 minutes ago</p>
                </x-ds-demo>
                <x-ds-demo label="Caption — text-xs text-gray-400 dark:text-gray-500">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Created on Jun 22, 2026</p>
                </x-ds-demo>
                <x-ds-demo label="Sidebar Section Label — text-[10px] uppercase tracking-[0.08em]">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Overview</p>
                </x-ds-demo>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- BUTTONS --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="buttons" class="scroll-mt-24">
            <x-ds-heading title="Buttons" description="Primary, secondary, and danger variants with all states." />

            <div class="space-y-6">
                {{-- Primary --}}
                <x-ds-demo label="Primary Button">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-primary-button>Save Changes</x-primary-button>
                        <x-primary-button disabled>Disabled</x-primary-button>
                        <x-primary-button>
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Saving...
                        </x-primary-button>
                    </div>
                </x-ds-demo>

                {{-- Secondary --}}
                <x-ds-demo label="Secondary Button">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-secondary-button>Cancel</x-secondary-button>
                        <x-secondary-button disabled>Disabled</x-secondary-button>
                    </div>
                </x-ds-demo>

                {{-- Danger --}}
                <x-ds-demo label="Danger Button">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-danger-button>Delete Account</x-danger-button>
                        <x-danger-button disabled>Disabled</x-danger-button>
                    </div>
                </x-ds-demo>

                {{-- Auth --}}
                <x-ds-demo label="Auth Button (full-width)">
                    <div class="max-w-sm">
                        <x-auth-button>Sign In</x-auth-button>
                    </div>
                </x-ds-demo>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- INPUTS --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="inputs" class="scroll-mt-24">
            <x-ds-heading title="Inputs" description="Text inputs, floating labels, and form elements." />

            <div class="space-y-6">
                <x-ds-demo label="Text Input">
                    <div class="max-w-sm">
                        <x-input-label value="Email Address" />
                        <x-text-input type="email" name="demo-email" class="mt-1 block w-full" placeholder="you@example.com" />
                    </div>
                </x-ds-demo>

                <x-ds-demo label="Text Input — Error State">
                    <div class="max-w-sm">
                        <x-input-label value="Email Address" />
                        <x-text-input type="email" name="demo-email-error" class="mt-1 block w-full border-red-400 dark:border-red-500" value="invalid" />
                        <x-input-error messages="Please enter a valid email address." class="mt-2" />
                    </div>
                </x-ds-demo>

                <x-ds-demo label="Auth Input (Floating Label)">
                    <div class="max-w-sm">
                        <x-auth-input name="demo-auth-email" type="email" label="Email Address" />
                    </div>
                </x-ds-demo>

                <x-ds-demo label="Auth Input — Password">
                    <div class="max-w-sm">
                        <x-auth-input name="demo-auth-password" type="password" label="Password" />
                    </div>
                </x-ds-demo>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- BADGES --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="badges" class="scroll-mt-24">
            <x-ds-heading title="Badges" description="Status indicators and labels in all variants." />

            <x-ds-demo label="All Variants">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge variant="success" label="Active" />
                    <x-badge variant="warning" label="Pending" />
                    <x-badge variant="danger" label="Suspended" />
                    <x-badge variant="info" label="New" />
                    <x-badge variant="brand" label="Pro" />
                    <x-badge variant="neutral" label="Draft" />
                </div>
            </x-ds-demo>
        </section>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- CARDS --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="cards" class="scroll-mt-24">
            <x-ds-heading title="Cards" description="Cards, stat cards, and action tiles." />

            <div class="space-y-6">
                {{-- Basic Card --}}
                <x-ds-demo label="Card (with header & footer)">
                    <div class="max-w-lg">
                        <x-card>
                            <x-slot name="header">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Tenant Details</h3>
                            </x-slot>
                            <p class="text-sm text-gray-700 dark:text-gray-300">Acme Corp is on the Pro plan with 5 active users and 3 custom domains configured.</p>
                            <x-slot name="footer">
                                <div class="flex items-center justify-end gap-3">
                                    <x-secondary-button>Edit</x-secondary-button>
                                    <x-primary-button>Save</x-primary-button>
                                </div>
                            </x-slot>
                        </x-card>
                    </div>
                </x-ds-demo>

                {{-- Stat Cards --}}
                <x-ds-demo label="Stat Cards">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <x-stat-card label="Total Tenants" value="24" description="3 added this week" trend="+14%" trendDirection="up">
                            <x-slot name="icon">
                                <x-heroicon-o-user-group />
                            </x-slot>
                        </x-stat-card>
                        <x-stat-card label="Active Modules" value="12" description="2 pending approval">
                            <x-slot name="icon">
                                <x-heroicon-o-squares-2x2 />
                            </x-slot>
                        </x-stat-card>
                        <x-stat-card label="Custom Domains" value="8" description="All verified" trend="100%" trendDirection="up">
                            <x-slot name="icon">
                                <x-heroicon-o-globe-alt />
                            </x-slot>
                        </x-stat-card>
                        <x-stat-card label="Uptime" value="99.9%" description="Last 30 days" trend="-0.1%" trendDirection="down">
                            <x-slot name="icon">
                                <x-heroicon-o-chart-bar />
                            </x-slot>
                        </x-stat-card>
                    </div>
                </x-ds-demo>

                {{-- Quick Actions --}}
                <x-ds-demo label="Quick Actions">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-quick-action href="#" title="Add New Tenant" description="Create a new tenant workspace">
                            <x-slot name="icon">
                                <x-heroicon-o-plus />
                            </x-slot>
                        </x-quick-action>
                        <x-quick-action href="#" title="Browse Modules" description="Explore available modules">
                            <x-slot name="icon">
                                <x-heroicon-o-puzzle-piece />
                            </x-slot>
                        </x-quick-action>
                    </div>
                </x-ds-demo>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- DATA DISPLAY --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="data-display" class="scroll-mt-24">
            <x-ds-heading title="Data Display" description="Tables, empty states, and data presentation." />

            <div class="space-y-6">
                <x-ds-demo label="Data Table">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-[#262632]">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Name</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Plan</th>
                                    <th class="px-4 py-3 font-medium">Users</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-[#181820]">
                                @foreach ([
                                    ['Acme Corp', 'success', 'Pro', '5'],
                                    ['Globex Inc', 'warning', 'Starter', '2'],
                                    ['Initech', 'neutral', 'Free', '1'],
                                ] as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-[#181820] transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $row[0] }}</td>
                                        <td class="px-4 py-3"><x-badge :variant="$row[1]" label="{{ $row[1] === 'success' ? 'Active' : ($row[1] === 'warning' ? 'Pending' : 'Draft') }}" /></td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row[2] }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row[3] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ds-demo>

                <x-ds-demo label="Empty State">
                    <x-empty-state title="No tenants yet" description="Get started by creating your first tenant workspace.">
                        <x-slot name="action">
                            <x-primary-button>Add Tenant</x-primary-button>
                        </x-slot>
                    </x-empty-state>
                </x-ds-demo>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- NAVIGATION --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="navigation" class="scroll-mt-24">
            <x-ds-heading title="Navigation" description="Breadcrumbs, sidebar nav items, and navigation patterns." />

            <div class="space-y-6">
                <x-ds-demo label="Breadcrumbs">
                    <x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => '#'], ['label' => 'Tenants', 'url' => '#'], ['label' => 'Acme Corp']]" />
                </x-ds-demo>

                <x-ds-demo label="Sidebar Nav Items (states)">
                    <div class="max-w-xs space-y-1">
                        {{-- Inactive --}}
                        <a href="#" class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                            <x-heroicon-o-home class="w-5 h-5 shrink-0 text-gray-400 dark:text-gray-500" />
                            <span>Dashboard</span>
                        </a>
                        {{-- Active --}}
                        <a href="#" class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#181820] text-gray-900 dark:text-gray-100 font-medium transition-colors">
                            <x-heroicon-o-user-group class="w-5 h-5 shrink-0 text-brand-500 dark:text-brand-400" />
                            <span>Tenants</span>
                        </a>
                        {{-- Sub-link (active) --}}
                        <a href="#" class="flex items-center gap-2 rounded-lg px-3 py-1.5 ms-4 text-sm bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium transition-colors">
                            Tenant List
                        </a>
                        {{-- Sub-link (inactive) --}}
                        <a href="#" class="flex items-center gap-2 rounded-lg px-3 py-1.5 ms-4 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#181820] hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                            Add Tenant
                        </a>
                    </div>
                </x-ds-demo>

                <x-ds-demo label="Sidebar Section Label">
                    <p class="px-3 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Central</p>
                </x-ds-demo>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- FEEDBACK --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <section id="feedback" class="scroll-mt-24">
            <x-ds-heading title="Feedback" description="Status messages, alerts, and user feedback patterns." />

            <div class="space-y-6">
                <x-ds-demo label="Inline Alerts">
                    <div class="space-y-3">
                        <x-alert variant="success" title="Tenant created">Acme Corp has been provisioned and is ready to use.</x-alert>
                        <x-alert variant="error" title="Deployment failed">Build process exited with code 1. Check the logs for details.</x-alert>
                        <x-alert variant="warning" title="Domain expiring">Your custom domain acme.com expires in 7 days.</x-alert>
                        <x-alert variant="info">A new module update is available for your tenants.</x-alert>
                        <x-alert variant="success" title="Dismissible" dismissible>This alert can be closed by clicking the X button.</x-alert>
                    </div>
                </x-ds-demo>

                <x-ds-demo label="Session Status (legacy)">
                    <x-auth-session-status status="Your profile has been updated successfully." />
                </x-ds-demo>

                <x-ds-demo label="Input Error">
                    <x-input-error :messages="['The email field is required.', 'The email must be a valid email address.']" />
                </x-ds-demo>

                <x-ds-demo label="Dropdown Menu">
                    <div class="max-w-xs">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <x-secondary-button>
                                    Options
                                    <svg class="ml-2 -mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </x-secondary-button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link href="#">Edit</x-dropdown-link>
                                <x-dropdown-link href="#">Duplicate</x-dropdown-link>
                                <div class="border-t border-gray-100 dark:border-[#262632] my-1"></div>
                                <x-dropdown-link href="#">Delete</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </x-ds-demo>

                <x-ds-demo label="Modal">
                    <div x-data="{ showModal: false }">
                        <x-primary-button x-on:click="showModal = true">Open Modal</x-primary-button>
                        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75" x-on:click="showModal = false"></div>
                            <div class="relative mx-auto mt-16 max-w-md overflow-hidden rounded-lg bg-white dark:bg-[#101016] shadow-xl" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                                <div class="p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Confirm Action</h3>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Are you sure you want to proceed? This action cannot be undone.</p>
                                    <div class="mt-6 flex justify-end gap-3">
                                        <x-secondary-button x-on:click="showModal = false">Cancel</x-secondary-button>
                                        <x-danger-button x-on:click="showModal = false">Confirm</x-danger-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ds-demo>
            </div>
        </section>

    </div>

    {{-- Bottom spacer --}}
    <div class="h-24"></div>
</x-app-layout>
