<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-init="$store.theme.init()">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TenantSmith') }}</title>

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased">
        @php
            $isTenant = (bool) tenant();
        @endphp

        <div class="flex min-h-screen">

            {{-- ──────────────────────────────────────────────────────────
                 LEFT PANEL — Brand / Illustration (hidden on mobile)
            ────────────────────────────────────────────────────────── --}}
            <div class="hidden lg:flex lg:w-[55%] xl:w-1/2 relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-950">

                {{-- Theme toggle (top-right of brand panel) --}}
                <div class="absolute top-6 right-6 z-10">
                    <x-theme-toggle />
                </div>

                {{-- Decorative grid dots --}}
                <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 24px 24px;"></div>

                {{-- Content --}}
                <div class="relative z-10 flex flex-col justify-between w-full px-12 xl:px-16 py-12">

                    {{-- Top: Logo + Brand --}}
                    <div class="animate-fade-up">
                        <div class="flex items-center gap-2.5">
                            <x-application-logo class="w-10 h-10" />
                            <span class="text-lg font-bold tracking-tight text-white translate-y-[0.5px]">TenantSmith</span>
                        </div>
                    </div>

                    {{-- Middle: Illustration --}}
                    <div class="flex-1 flex items-center justify-center py-8">
                        <div class="animate-fade-up-delay-1">
                            @if ($isTenant)
                                {{-- Tenant illustration: workspace / dashboard --}}
                                <svg class="w-full max-w-md animate-float" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    {{-- Main dashboard card --}}
                                    <rect x="50" y="50" width="220" height="160" rx="12" fill="white" fill-opacity="0.1" stroke="white" stroke-opacity="0.15"/>
                                    <rect x="50" y="50" width="220" height="32" rx="12" fill="white" fill-opacity="0.08"/>
                                    <circle cx="68" cy="66" r="4" fill="#f87171" fill-opacity="0.7"/>
                                    <circle cx="82" cy="66" r="4" fill="#fbbf24" fill-opacity="0.7"/>
                                    <circle cx="96" cy="66" r="4" fill="#34d399" fill-opacity="0.7"/>
                                    {{-- Chart bars --}}
                                    <rect x="66" y="120" width="20" height="50" rx="4" fill="#818cf8" fill-opacity="0.25"/>
                                    <rect x="92" y="100" width="20" height="70" rx="4" fill="#818cf8" fill-opacity="0.35"/>
                                    <rect x="118" y="130" width="20" height="40" rx="4" fill="#818cf8" fill-opacity="0.2"/>
                                    <rect x="144" y="90" width="20" height="80" rx="4" fill="#a5b4fc" fill-opacity="0.4"/>
                                    <rect x="170" y="110" width="20" height="60" rx="4" fill="#818cf8" fill-opacity="0.3"/>
                                    <rect x="196" y="140" width="20" height="30" rx="4" fill="#c7d2fe" fill-opacity="0.2"/>
                                    <rect x="222" y="100" width="20" height="70" rx="4" fill="#818cf8" fill-opacity="0.3"/>

                                    {{-- Floating stat card --}}
                                    <g class="animate-float-delayed">
                                        <rect x="260" y="40" width="110" height="70" rx="8" fill="white" fill-opacity="0.1" stroke="white" stroke-opacity="0.12"/>
                                        <rect x="276" y="56" width="40" height="6" rx="3" fill="white" fill-opacity="0.2"/>
                                        <rect x="276" y="70" width="60" height="8" rx="4" fill="#34d399" fill-opacity="0.35"/>
                                    </g>

                                    {{-- Floating users card --}}
                                    <g class="animate-float">
                                        <rect x="280" y="140" width="90" height="80" rx="8" fill="white" fill-opacity="0.08" stroke="white" stroke-opacity="0.1"/>
                                        <circle cx="300" cy="168" r="10" fill="#a5b4fc" fill-opacity="0.25"/>
                                        <circle cx="320" cy="168" r="10" fill="#818cf8" fill-opacity="0.2"/>
                                        <circle cx="340" cy="168" r="10" fill="#c7d2fe" fill-opacity="0.15"/>
                                        <rect x="296" y="192" width="58" height="6" rx="3" fill="white" fill-opacity="0.12"/>
                                    </g>
                                </svg>
                            @else
                                {{-- Central illustration: multi-tenant dashboard --}}
                                <svg class="w-full max-w-md animate-float" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    {{-- Background card --}}
                                    <rect x="40" y="60" width="200" height="140" rx="12" fill="white" fill-opacity="0.1" stroke="white" stroke-opacity="0.15"/>
                                    <rect x="40" y="60" width="200" height="32" rx="12" fill="white" fill-opacity="0.08"/>
                                    <circle cx="58" cy="76" r="4" fill="#f87171" fill-opacity="0.7"/>
                                    <circle cx="72" cy="76" r="4" fill="#fbbf24" fill-opacity="0.7"/>
                                    <circle cx="86" cy="76" r="4" fill="#34d399" fill-opacity="0.7"/>
                                    <rect x="56" y="108" width="120" height="6" rx="3" fill="white" fill-opacity="0.2"/>
                                    <rect x="56" y="124" width="80" height="6" rx="3" fill="white" fill-opacity="0.12"/>
                                    <rect x="56" y="148" width="56" height="28" rx="6" fill="white" fill-opacity="0.15"/>

                                    {{-- Floating card 1 (top-right) --}}
                                    <g class="animate-float-delayed">
                                        <rect x="220" y="30" width="140" height="100" rx="10" fill="white" fill-opacity="0.12" stroke="white" stroke-opacity="0.15"/>
                                        <rect x="236" y="50" width="60" height="6" rx="3" fill="white" fill-opacity="0.25"/>
                                        <rect x="236" y="64" width="40" height="6" rx="3" fill="white" fill-opacity="0.15"/>
                                        <rect x="236" y="84" width="108" height="24" rx="6" fill="#818cf8" fill-opacity="0.3"/>
                                    </g>

                                    {{-- Floating card 2 (bottom-right) --}}
                                    <g class="animate-float">
                                        <rect x="240" y="150" width="130" height="90" rx="10" fill="white" fill-opacity="0.1" stroke="white" stroke-opacity="0.12"/>
                                        <rect x="256" y="170" width="48" height="48" rx="8" fill="#a5b4fc" fill-opacity="0.2"/>
                                        <rect x="316" y="170" width="38" height="6" rx="3" fill="white" fill-opacity="0.2"/>
                                        <rect x="316" y="184" width="28" height="6" rx="3" fill="white" fill-opacity="0.12"/>
                                        <rect x="256" y="226" width="98" height="6" rx="3" fill="white" fill-opacity="0.1"/>
                                    </g>

                                    {{-- Connected dots / nodes --}}
                                    <circle cx="180" cy="180" r="5" fill="#a5b4fc" fill-opacity="0.5"/>
                                    <circle cx="240" cy="150" r="4" fill="#818cf8" fill-opacity="0.4"/>
                                    <line x1="180" y1="180" x2="240" y2="150" stroke="white" stroke-opacity="0.1" stroke-width="1.5" stroke-dasharray="4 4"/>
                                    <circle cx="210" cy="240" r="3" fill="#c7d2fe" fill-opacity="0.3"/>
                                    <line x1="180" y1="180" x2="210" y2="240" stroke="white" stroke-opacity="0.08" stroke-width="1" stroke-dasharray="4 4"/>

                                    {{-- Stacked layers icon --}}
                                    <rect x="90" y="210" width="44" height="44" rx="6" fill="#a5b4fc" fill-opacity="0.15" transform="rotate(-6 90 210)"/>
                                    <rect x="96" y="216" width="44" height="44" rx="6" fill="#818cf8" fill-opacity="0.2" transform="rotate(-3 96 216)"/>
                                    <rect x="102" y="222" width="44" height="44" rx="6" fill="white" fill-opacity="0.15"/>
                                </svg>
                            @endif
                        </div>
                    </div>

                    {{-- Bottom: Tagline + Features --}}
                    <div class="animate-fade-up-delay-2 space-y-6">
                        <div>
                            @if ($isTenant)
                                <h2 class="text-2xl xl:text-3xl font-bold text-white leading-tight">
                                    Your workspace<br>is ready
                                </h2>
                                <p class="mt-3 text-brand-200 text-sm leading-relaxed max-w-sm">
                                    Access your workspace, manage users, and explore available modules.
                                </p>
                            @else
                                <h2 class="text-2xl xl:text-3xl font-bold text-white leading-tight">
                                    Manage your tenants<br>with confidence
                                </h2>
                                <p class="mt-3 text-brand-200 text-sm leading-relaxed max-w-sm">
                                    Multi-tenant infrastructure made simple. Provision, configure, and scale from a single dashboard.
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                            @if ($isTenant)
                                <div class="flex items-center gap-2 text-sm text-brand-200">
                                    <svg class="w-4 h-4 text-brand-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Team management
                                </div>
                                <div class="flex items-center gap-2 text-sm text-brand-200">
                                    <svg class="w-4 h-4 text-brand-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Available modules
                                </div>
                                <div class="flex items-center gap-2 text-sm text-brand-200">
                                    <svg class="w-4 h-4 text-brand-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Custom domains
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-sm text-brand-200">
                                    <svg class="w-4 h-4 text-brand-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Multi-tenant ready
                                </div>
                                <div class="flex items-center gap-2 text-sm text-brand-200">
                                    <svg class="w-4 h-4 text-brand-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Custom domains
                                </div>
                                <div class="flex items-center gap-2 text-sm text-brand-200">
                                    <svg class="w-4 h-4 text-brand-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Module management
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ──────────────────────────────────────────────────────────
                 RIGHT PANEL — Form
            ────────────────────────────────────────────────────────── --}}
            <div class="flex-1 flex flex-col bg-white dark:bg-[#08080c]">

                {{-- Mobile brand header (visible below lg) --}}
                <div class="lg:hidden flex items-center justify-between px-6 pt-6">
                    <div class="flex items-center gap-2">
                        <x-application-logo class="w-8 h-8" />
                        <span class="text-base font-bold tracking-tight text-gray-800 dark:text-gray-100 translate-y-[0.5px]">TenantSmith</span>
                    </div>
                    <x-theme-toggle />
                </div>

                {{-- Form area --}}
                <div class="flex-1 flex items-center justify-center px-6 sm:px-12 lg:px-16 xl:px-20 py-12">
                    <div class="w-full max-w-sm animate-fade-up-delay-1">
                        {{ $slot }}
                    </div>
                </div>
            </div>

        </div>
        @livewireScriptConfig
    </body>
</html>
