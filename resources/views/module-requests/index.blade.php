<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Module Requests">
            <x-slot:actions>
                <a href="{{ route('modules.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 dark:border-[#2a2a38] bg-white dark:bg-[#1e1e28] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-[#2a2a38] transition-colors">
                    Back to Modules
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800">{{ session('error') }}</div>
            @endif

            <x-card>
                <div class="overflow-x-auto">
                    @if ($moduleRequests->isEmpty())
                        <x-empty-state title="No requests found" description="Module requests from tenants will appear here." />
                    @else
                        <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-[#2a2a38]">
                            <thead>
                                <tr>
                                    <th class="w-[14%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tenant</th>
                                    <th class="w-[18%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Module</th>
                                    <th class="w-[18%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="w-[20%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Requested</th>
                                    <th class="w-[30%] px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-[#2a2a38]">
                                @foreach ($moduleRequests as $request)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-[#1e1e28] transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $request->tenant_id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $request->module->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($request->status === 'approved')
                                                <x-badge variant="success" label="Approved" />
                                            @elseif ($request->status === 'rejected')
                                                <x-badge variant="danger" label="Rejected" />
                                            @else
                                                <x-badge variant="warning" label="Pending" />
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ optional($request->created_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 text-sm">
                                            @if ($request->status === 'pending')
                                                <div class="ms-auto flex w-full max-w-[360px] items-center justify-end gap-2">
                                                    <form method="POST" action="{{ route('module-requests.approve', $request) }}" class="inline-flex">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex w-full items-center justify-center rounded-lg border border-transparent bg-green-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-green-500 transition-colors">
                                                            Approve
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('module-requests.reject', $request) }}" class="inline-flex">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex w-full items-center justify-center rounded-lg border border-red-600 bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-red-500 transition-colors">
                                                            Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="block text-right text-gray-400 dark:text-gray-500">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                @if ($moduleRequests->hasPages())
                    <x-slot:footer>
                        {{ $moduleRequests->links() }}
                    </x-slot:footer>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
