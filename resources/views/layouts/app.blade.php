<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-init="$store.theme.init()">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TenantSmith') }}</title>

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-[#08080c] text-gray-900 dark:text-gray-100">
        @php
            $isTenant = (bool) tenant();
        @endphp

        <div class="flex min-h-screen">
            <x-sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                {{-- Sticky Header --}}
                <header class="sticky top-0 z-30 border-b border-gray-200 dark:border-[#262632] bg-white/80 dark:bg-[#101016]/80 backdrop-blur-xl">
                    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">

                        {{-- Left: Mobile sidebar toggle + Breadcrumbs --}}
                        <div class="flex items-center gap-3">
                            {{-- Mobile sidebar toggle --}}
                            <button
                                x-data
                                @click="$dispatch('toggle-mobile-sidebar')"
                                class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#181820] transition-colors md:hidden"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>

                            @if (isset($breadcrumbs))
                                {{ $breadcrumbs }}
                            @endif
                        </div>

                        {{-- Right: Tenant badge + Theme toggle + User menu --}}
                        <div class="flex items-center gap-2">
                            @if ($isTenant)
                                <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-500/20">
                                    {{ tenant()->id }}
                                </span>
                            @endif

                            <x-theme-toggle />

                            <x-user-menu />
                        </div>
                    </div>
                </header>

                {{-- Page Header Slot --}}
                @isset($header)
                    <div class="bg-white dark:bg-[#101016] border-b border-gray-200 dark:border-[#262632]">
                        <div class="px-4 sm:px-6 lg:px-8 py-5">
                            {{ $header }}
                        </div>
                    </div>
                @endisset

                {{-- Main Content --}}
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
