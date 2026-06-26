<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Modules</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $moduleRows->count() }} available module{{ $moduleRows->count() === 1 ? '' : 's' }}</p>
            </div>
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
        @if (!empty($operationAlert))
            <div class="mb-6">
                <x-alert :variant="$operationAlert['type'] === 'success' ? 'success' : 'error'">{{ $operationAlert['message'] }}</x-alert>
            </div>
        @endif

        {{-- Watch Script --}}
        @if (($watching ?? false) && !($watchDone ?? true))
            <script>
                setTimeout(() => {
                    const url = new URL(window.location.href);
                    const attempt = Number(url.searchParams.get('watch_attempt') || '0') + 1;
                    const maxAttempts = 20;

                    if (attempt <= maxAttempts) {
                        url.searchParams.set('watch_attempt', String(attempt));
                        window.location.href = url.toString();
                        return;
                    }

                    url.searchParams.delete('watch_module_id');
                    url.searchParams.delete('watch_action');
                    url.searchParams.delete('watch_attempt');
                    window.location.href = url.toString();
                }, 1500);
            </script>
        @endif

        @if ($moduleRows->isEmpty())
            {{-- Empty State --}}
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center mb-4">
                    <x-heroicon-o-puzzle-piece class="w-6 h-6 text-brand-600 dark:text-brand-400" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">No modules available</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">There are no modules available at this time.</p>
            </div>
        @else
            {{-- Table Container --}}
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] overflow-hidden">

                {{-- Desktop Table --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-[#262632] bg-gray-50/50 dark:bg-[#0e0e15]/50">
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Module</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Version</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[120px]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#181820]">
                            @foreach ($moduleRows as $row)
                                @php($module = $row['module'])
                                <tr class="group hover:bg-gray-50/70 dark:hover:bg-[#181820]/70 transition-all duration-150">
                                    {{-- Module Name --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-500/10 border border-purple-100 dark:border-purple-500/20 flex items-center justify-center shrink-0">
                                                <x-heroicon-o-puzzle-piece class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $module->name }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Version --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $module->version }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-5 py-4">
                                        @if ($row['is_queued_install'])
                                            <x-badge variant="brand">Installing...</x-badge>
                                        @elseif ($row['is_queued_uninstall'])
                                            <x-badge variant="warning">Uninstalling...</x-badge>
                                        @elseif ($row['is_installed'])
                                            <x-badge variant="success">Installed</x-badge>
                                        @elseif ($row['request_status'] === 'pending')
                                            <x-badge variant="warning">Pending</x-badge>
                                        @elseif ($row['request_status'] === 'approved')
                                            <x-badge variant="info">Approved</x-badge>
                                        @elseif ($row['request_status'] === 'rejected')
                                            <x-badge variant="danger">Rejected</x-badge>
                                        @else
                                            <x-badge variant="neutral">Not requested</x-badge>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-5 py-4 text-right">
                                        @if ($row['is_processing'])
                                            <button type="button" disabled
                                                class="inline-flex items-center rounded-lg border border-gray-300 dark:border-[#262632] bg-gray-100 dark:bg-[#101016] px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 shadow-card cursor-not-allowed">
                                                Processing...
                                            </button>
                                        @elseif ($row['is_installed'])
                                            <div class="inline-flex items-center gap-2">
                                                @if ($row['open_route_name'])
                                                    <a href="{{ route($row['open_route_name'], absolute: false) }}"
                                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out">
                                                        Open
                                                    </a>
                                                @endif
                                                <form method="POST" action="{{ route('tenant.modules.uninstall') }}" class="inline" x-data="{ submitting: false }" @submit="submitting = true">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                    <button type="submit" x-bind:disabled="submitting"
                                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-red-500 to-red-600 border border-red-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-red-500 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-red-600 active:to-red-800 transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                                        <span x-show="!submitting">Uninstall</span>
                                                        <span x-show="submitting" x-cloak>Uninstalling...</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif ($row['request_status'] === 'approved')
                                            <form method="POST" action="{{ route('tenant.modules.install') }}" class="inline" x-data="{ submitting: false }" @submit="submitting = true">
                                                @csrf
                                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                <button type="submit" x-bind:disabled="submitting"
                                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span x-show="!submitting">Install</span>
                                                    <span x-show="submitting" x-cloak>Installing...</span>
                                                </button>
                                            </form>
                                        @elseif ($row['request_status'] === 'pending')
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Waiting for approval</span>
                                        @elseif ($row['request_status'] === 'rejected')
                                            <form method="POST" action="{{ route('tenant.modules.request') }}" class="inline" x-data="{ submitting: false }" @submit="submitting = true">
                                                @csrf
                                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                <button type="submit" x-bind:disabled="submitting"
                                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-[#101016] border border-gray-300 dark:border-[#262632] rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-card hover:bg-gray-50 dark:hover:bg-[#181820] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span x-show="!submitting">Request Again</span>
                                                    <span x-show="submitting" x-cloak>Requesting...</span>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('tenant.modules.request') }}" class="inline" x-data="{ submitting: false }" @submit="submitting = true">
                                                @csrf
                                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                <button type="submit" x-bind:disabled="submitting"
                                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-[#101016] border border-gray-300 dark:border-[#262632] rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-card hover:bg-gray-50 dark:hover:bg-[#181820] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span x-show="!submitting">Request Module</span>
                                                    <span x-show="submitting" x-cloak>Requesting...</span>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="lg:hidden divide-y divide-gray-100 dark:divide-[#181820]">
                    @foreach ($moduleRows as $row)
                        @php($module = $row['module'])
                        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-[#181820]/50 transition-colors duration-150">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-purple-50 dark:bg-purple-500/10 border border-purple-100 dark:border-purple-500/20 flex items-center justify-center shrink-0">
                                        <x-heroicon-o-puzzle-piece class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $module->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">v{{ $module->version }}</div>
                                    </div>
                                </div>
                                @if ($row['is_queued_install'])
                                    <x-badge variant="brand">Installing...</x-badge>
                                @elseif ($row['is_queued_uninstall'])
                                    <x-badge variant="warning">Uninstalling...</x-badge>
                                @elseif ($row['is_installed'])
                                    <x-badge variant="success">Installed</x-badge>
                                @elseif ($row['request_status'] === 'pending')
                                    <x-badge variant="warning">Pending</x-badge>
                                @elseif ($row['request_status'] === 'approved')
                                    <x-badge variant="info">Approved</x-badge>
                                @elseif ($row['request_status'] === 'rejected')
                                    <x-badge variant="danger">Rejected</x-badge>
                                @else
                                    <x-badge variant="neutral">Not requested</x-badge>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($row['is_processing'])
                                    <button type="button" disabled
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-gray-50 dark:bg-[#181820] border border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium text-gray-400 dark:text-gray-500 cursor-not-allowed">
                                        Processing...
                                    </button>
                                @elseif ($row['is_installed'])
                                    @if ($row['open_route_name'])
                                        <a href="{{ route($row['open_route_name'], absolute: false) }}"
                                            class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg text-xs font-semibold text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out">
                                            OPEN
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('tenant.modules.uninstall') }}" class="flex-1" x-data="{ submitting: false }" @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                                        <button type="submit" x-bind:disabled="submitting"
                                            class="w-full inline-flex items-center justify-center px-3 py-2 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/20 transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span x-show="!submitting">Uninstall</span>
                                            <span x-show="submitting" x-cloak>Uninstalling...</span>
                                        </button>
                                    </form>
                                @elseif ($row['request_status'] === 'approved')
                                    <form method="POST" action="{{ route('tenant.modules.install') }}" class="flex-1" x-data="{ submitting: false }" @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                                        <button type="submit" x-bind:disabled="submitting"
                                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span x-show="!submitting">INSTALL</span>
                                            <span x-show="submitting" x-cloak>INSTALLING...</span>
                                        </button>
                                    </form>
                                @elseif ($row['request_status'] === 'pending')
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Waiting for approval</span>
                                @elseif ($row['request_status'] === 'rejected')
                                    <form method="POST" action="{{ route('tenant.modules.request') }}" class="flex-1" x-data="{ submitting: false }" @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                                        <button type="submit" x-bind:disabled="submitting"
                                            class="w-full inline-flex items-center justify-center px-3 py-2 bg-gray-50 dark:bg-[#181820] border border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span x-show="!submitting">Request Again</span>
                                            <span x-show="submitting" x-cloak>Requesting...</span>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('tenant.modules.request') }}" class="flex-1" x-data="{ submitting: false }" @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                                        <button type="submit" x-bind:disabled="submitting"
                                            class="w-full inline-flex items-center justify-center px-3 py-2 bg-gray-50 dark:bg-[#181820] border border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span x-show="!submitting">Request Module</span>
                                            <span x-show="submitting" x-cloak>Requesting...</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
