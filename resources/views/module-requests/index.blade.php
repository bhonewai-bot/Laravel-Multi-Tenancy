<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Module Requests</h2>
            <a href="{{ route('modules.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Back to modules</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700 border border-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700 border border-red-200">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-[10%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                                    <th class="w-[14%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                                    <th class="w-[12%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="w-[13%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                                    <th class="w-[13%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewed</th>
                                    <th class="w-[24%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Review Note</th>
                                    <th class="w-[14%] px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($moduleRequests as $request)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $request->tenant_id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $request->module->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($request->status === 'approved')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                                    Approved
                                                </span>
                                            @elseif ($request->status === 'rejected')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                    Rejected
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-yellow-200 bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-800">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($request->created_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ optional($request->reviewed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 break-words">{{ $request->review_note ?: '-' }}</td>
                                        <td class="px-6 py-4 text-sm">
                                            @if ($request->status === 'pending')
                                                <div class="ms-auto w-full max-w-[240px] space-y-2">
                                                    <form method="POST" action="{{ route('module-requests.approve', $request) }}">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-green-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-green-500">
                                                            Approve
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('module-requests.reject', $request) }}" class="space-y-2">
                                                        @csrf
                                                        <x-text-input type="text" name="review_note" class="w-full" placeholder="Reason (optional)" />
                                                        <button type="submit"
                                                            class="inline-flex w-full items-center justify-center rounded-md border border-red-600 bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-red-500">
                                                            Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="block text-right text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-sm text-gray-500">No requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $moduleRequests->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
