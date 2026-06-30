<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Modules</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $modules->total() }} modules available</p>
            </div>
            <a href="{{ route('modules.create') }}">
                <x-primary-button type="button">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    Create Module
                </x-primary-button>
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6">
                <x-alert variant="success">{{ session('success') }}</x-alert>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6">
                <x-alert variant="error">{{ session('error') }}</x-alert>
            </div>
        @endif

        @if ($modules->isEmpty())
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center mb-4">
                    <x-heroicon-o-squares-2x2 class="w-6 h-6 text-brand-600 dark:text-brand-400" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">No modules yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Create your first module to get started.</p>
                <a href="{{ route('modules.create') }}">
                    <x-primary-button type="button">
                        <x-heroicon-o-plus class="w-4 h-4" />
                        Create Module
                    </x-primary-button>
                </a>
            </div>
        @else
            {{-- Table Container --}}
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] overflow-hidden">

                {{-- Desktop Table --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-[#262632] bg-gray-50/50 dark:bg-[#0e0e15]/50">
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Slug</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Version</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Price</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[120px]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#181820]">
                            @foreach ($modules as $module)
                                <tr class="group hover:bg-gray-50/70 dark:hover:bg-[#181820]/70 transition-all duration-150">
                                    {{-- Module Name --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-500/10 border border-purple-100 dark:border-purple-500/20 flex items-center justify-center shrink-0">
                                                <x-heroicon-o-puzzle-piece class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $module->name }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Slug --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $module->slug }}</span>
                                    </td>

                                    {{-- Version --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $module->version }}</span>
                                    </td>

                                    {{-- Price --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${{ number_format((float) $module->price, 2) }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-5 py-4">
                                        @if ($module->is_active)
                                            <x-badge variant="success" label="Active" />
                                        @else
                                            <x-badge variant="neutral" label="Disabled" />
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-5 py-4 text-right">
                                        <form method="POST" action="{{ route('modules.toggle', $module) }}" x-data="{ toggling: false }" @submit="toggling = true">
                                            @csrf
                                            @if ($module->is_active)
                                                <button type="submit" :disabled="toggling"
                                                    class="inline-flex items-center px-4 py-2 bg-gradient-to-b from-red-500 to-red-600 border border-red-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-[0_0_20px_rgba(239,68,68,0.15)] hover:from-red-500 hover:to-red-700 active:from-red-600 active:to-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                                    <span x-show="!toggling">DISABLE</span>
                                                    <span x-show="toggling" x-cloak>DISABLING...</span>
                                                </button>
                                            @else
                                                <button type="submit" :disabled="toggling"
                                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                                                    <span x-show="!toggling">ENABLE</span>
                                                    <span x-show="toggling" x-cloak>ENABLING...</span>
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="lg:hidden divide-y divide-gray-100 dark:divide-[#181820]">
                    @foreach ($modules as $module)
                        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-[#181820]/50 transition-colors duration-150">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-500/10 border border-purple-100 dark:border-purple-500/20 flex items-center justify-center shrink-0">
                                        <x-heroicon-o-puzzle-piece class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $module->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $module->slug }}</div>
                                    </div>
                                </div>
                                @if ($module->is_active)
                                    <x-badge variant="success" label="Active" />
                                @else
                                    <x-badge variant="neutral" label="Disabled" />
                                @endif
                            </div>

                            <div class="flex items-center gap-4 text-sm mb-3">
                                <span class="text-gray-500 dark:text-gray-400">v{{ $module->version }}</span>
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                <span class="text-gray-900 dark:text-gray-100 font-medium">${{ number_format((float) $module->price, 2) }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('modules.toggle', $module) }}" x-data="{ toggling: false }" @submit="toggling = true" class="flex-1">
                                    @csrf
                                    @if ($module->is_active)
                                        <button type="submit" :disabled="toggling"
                                            class="inline-flex w-full items-center justify-center px-4 py-2 bg-gradient-to-b from-red-500 to-red-600 border border-red-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-[0_0_20px_rgba(239,68,68,0.15)] hover:from-red-500 hover:to-red-700 active:from-red-600 active:to-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                            <span x-show="!toggling">DISABLE</span>
                                            <span x-show="toggling" x-cloak>DISABLING...</span>
                                        </button>
                                    @else
                                        <button type="submit" :disabled="toggling"
                                            class="inline-flex w-full items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                                            <span x-show="!toggling">ENABLE</span>
                                            <span x-show="toggling" x-cloak>ENABLING...</span>
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Pagination --}}
            @if ($modules->hasPages())
                <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Showing {{ $modules->firstItem() }} to {{ $modules->lastItem() }} of {{ $modules->total() }} modules</span>
                    <div>{{ $modules->links() }}</div>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
