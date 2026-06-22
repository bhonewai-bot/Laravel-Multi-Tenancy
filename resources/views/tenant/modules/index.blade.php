<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Available Modules" />
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800">{{ session('error') }}</div>
            @endif

            @if (!empty($operationAlert))
                <div class="mb-4 rounded-lg border p-4 text-sm {{ $operationAlert['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400' : 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                    {{ $operationAlert['message'] }}
                </div>
            @endif

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

            <x-card>
                <div class="overflow-x-auto">
                    @if ($moduleRows->isEmpty())
                        <x-empty-state title="No modules available" description="There are no modules available at this time." />
                    @else
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#2a2a38]">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Version</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-[#2a2a38]">
                                @foreach ($moduleRows as $row)
                                    @php($module = $row['module'])
                                    <tr class="hover:bg-gray-50 dark:hover:bg-[#1e1e28] transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $module->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $module->version }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($row['is_queued_install'])
                                                <x-badge variant="brand" label="Installing..." />
                                            @elseif ($row['is_queued_uninstall'])
                                                <x-badge variant="warning" label="Uninstalling..." />
                                            @elseif ($row['is_installed'])
                                                <x-badge variant="success" label="Installed" />
                                            @elseif ($row['request_status'] === 'pending')
                                                <x-badge variant="warning" label="Pending" />
                                            @elseif ($row['request_status'] === 'approved')
                                                <x-badge variant="info" label="Approved" />
                                            @elseif ($row['request_status'] === 'rejected')
                                                <x-badge variant="danger" label="Rejected" />
                                            @else
                                                <x-badge variant="neutral" label="Not requested" />
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($row['is_processing'])
                                                <button type="button" disabled
                                                    class="inline-flex items-center rounded-lg border border-gray-300 dark:border-[#2a2a38] bg-gray-100 dark:bg-[#1e1e28] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400 shadow-sm cursor-not-allowed">
                                                    Processing...
                                                </button>
                                            @elseif ($row['is_installed'])
                                                <div class="flex items-center gap-2">
                                                    @if ($row['open_route_name'])
                                                        <a href="{{ route($row['open_route_name'], absolute: false) }}"
                                                            class="inline-flex items-center rounded-lg border border-transparent bg-indigo-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-indigo-500 transition-colors">
                                                            Open
                                                        </a>
                                                    @endif
                                                    <form method="POST" action="{{ route('tenant.modules.uninstall') }}">
                                                        @csrf
                                                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                        <button type="submit"
                                                            class="inline-flex items-center rounded-lg border border-red-600 bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-red-500 transition-colors">
                                                            Uninstall
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif ($row['request_status'] === 'approved')
                                                <form method="POST" action="{{ route('tenant.modules.install') }}">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center rounded-lg border border-transparent bg-indigo-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-indigo-500 transition-colors">
                                                        Install
                                                    </button>
                                                </form>
                                            @elseif ($row['request_status'] === 'pending')
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Waiting for approval</span>
                                            @elseif ($row['request_status'] === 'rejected')
                                                <form method="POST" action="{{ route('tenant.modules.request') }}">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-[#2a2a38] bg-white dark:bg-[#1e1e28] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-[#2a2a38] transition-colors">
                                                        Request Again
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('tenant.modules.request') }}">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-[#2a2a38] bg-white dark:bg-[#1e1e28] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-[#2a2a38] transition-colors">
                                                        Request Module
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
