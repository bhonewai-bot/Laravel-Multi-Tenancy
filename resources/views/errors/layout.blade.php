<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-init="$store.theme.init()">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('code') — {{ config('app.name', 'TenantSmith') }}</title>

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-[#08080c] text-gray-900 dark:text-gray-100">
        <div class="flex min-h-screen flex-col">
            {{-- Header: Logo + Theme toggle --}}
            <div class="flex items-center justify-between px-6 py-5">
                <a href="/" class="flex items-center gap-2.5">
                    <x-application-logo class="w-7 h-7" />
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">TenantSmith</span>
                </a>
                <x-theme-toggle />
            </div>

            {{-- Centered error content --}}
            <div class="flex-1 flex items-center justify-center px-6 pb-16">
                <div class="w-full max-w-sm animate-fade-up text-center">
                    {{-- Error code --}}
                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500 mb-4">Error @yield('code')</p>

                    {{-- Title --}}
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        @yield('title')
                    </h1>

                    {{-- Description --}}
                    <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                        @yield('description')
                    </p>

                    {{-- CTA --}}
                    <div class="mt-8">
                        @yield('body')
                    </div>
                </div>
            </div>
        </div>

        @livewireScriptConfig
    </body>
</html>
