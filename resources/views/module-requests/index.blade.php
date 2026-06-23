<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Module Requests</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $moduleRequests->total() }} requests from tenants</p>
            </div>
            <a href="{{ route('modules.index') }}">
                <x-secondary-button type="button">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Back to Modules
                </x-secondary-button>
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

        @if ($moduleRequests->isEmpty())
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center mb-4">
                    <x-heroicon-o-inbox class="w-6 h-6 text-brand-600 dark:text-brand-400" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">No requests yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Module requests from tenants will appear here.</p>
            </div>
        @else
            {{-- Table Container --}}
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] overflow-hidden">

                {{-- Desktop Table --}}
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-[#262632] bg-gray-50/50 dark:bg-[#0e0e15]/50">
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tenant</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Module</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Requested</th>
                                <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[140px]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#181820]">
                            @foreach ($moduleRequests as $request)
                                <tr class="group hover:bg-gray-50/70 dark:hover:bg-[#181820]/70 transition-all duration-150">
                                    {{-- Tenant --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                                <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ strtoupper(substr($request->tenant_id ?? 'T', 0, 1)) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $request->tenant_id }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Module --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $request->module->name ?? '-' }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-5 py-4">
                                        @if ($request->status === 'approved')
                                            <x-badge variant="success" label="Approved" />
                                        @elseif ($request->status === 'rejected')
                                            <x-badge variant="danger" label="Rejected" />
                                        @else
                                            <x-badge variant="warning" label="Pending" />
                                        @endif
                                    </td>

                                    {{-- Requested --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ optional($request->created_at)->format('M d, Y H:i') }}</span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-5 py-4 text-right">
                                        @if ($request->status === 'pending')
                                            <div class="flex items-center justify-end gap-2">
                                                <form method="POST" action="{{ route('module-requests.approve', $request) }}" class="inline-flex">
                                                    @csrf
                                                    <x-primary-button type="submit">
                                                        Approve
                                                    </x-primary-button>
                                                </form>

                                                <form method="POST" action="{{ route('module-requests.reject', $request) }}" class="inline-flex">
                                                    @csrf
                                                    <x-danger-button type="submit">
                                                        Reject
                                                    </x-danger-button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="lg:hidden divide-y divide-gray-100 dark:divide-[#181820]">
                    @foreach ($moduleRequests as $request)
                        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-[#181820]/50 transition-colors duration-150">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                        <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ strtoupper(substr($request->tenant_id ?? 'T', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $request->tenant_id }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $request->module->name ?? '-' }}</div>
                                    </div>
                                </div>
                                @if ($request->status === 'approved')
                                    <x-badge variant="success" label="Approved" />
                                @elseif ($request->status === 'rejected')
                                    <x-badge variant="danger" label="Rejected" />
                                @else
                                    <x-badge variant="warning" label="Pending" />
                                @endif
                            </div>

                            <div class="flex items-center gap-4 text-sm mb-3">
                                <span class="text-gray-500 dark:text-gray-400">{{ optional($request->created_at)->format('M d, Y H:i') }}</span>
                            </div>

                            @if ($request->status === 'pending')
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('module-requests.approve', $request) }}" class="flex-1">
                                        @csrf
                                        <x-primary-button type="submit" class="w-full">
                                            Approve
                                        </x-primary-button>
                                    </form>
                                    <form method="POST" action="{{ route('module-requests.reject', $request) }}" class="flex-1">
                                        @csrf
                                        <x-danger-button type="submit" class="w-full">
                                            Reject
                                        </x-danger-button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Pagination --}}
            @if ($moduleRequests->hasPages())
                <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Showing {{ $moduleRequests->firstItem() }} to {{ $moduleRequests->lastItem() }} of {{ $moduleRequests->total() }} requests</span>
                    <div>{{ $moduleRequests->links() }}</div>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
