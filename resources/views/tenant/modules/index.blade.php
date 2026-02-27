<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Available Modules</h2>
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
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Version</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($modules as $module)
                                    @php
                                        $isInstalled = in_array($module->slug, $installedModules ?? [], true);
                                        $requestStatus = $requestModules[$module->id] ?? null;
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $module->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $module->version }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($isInstalled)
                                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Installed</span>
                                            @elseif ($requestStatus === 'pending')
                                                <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800">Pending</span>
                                            @elseif ($requestStatus === 'approved')
                                                <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">Approved</span>
                                            @elseif ($requestStatus === 'rejected')
                                                <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Rejected</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">Not requested</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($isInstalled)
                                                <form method="POST" action="{{ route('tenant.modules.uninstall') }}">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center rounded-md border border-red-600 bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-red-500">
                                                        Uninstall
                                                    </button>
                                                </form>
                                            @elseif ($requestStatus === 'approved')
                                                <form method="POST" action="{{ route('tenant.modules.install') }}">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-indigo-500">
                                                        Install
                                                    </button>
                                                </form>
                                            @elseif ($requestStatus === 'pending')
                                                <span class="text-sm text-gray-500">Waiting for approval</span>
                                            @elseif ($requestStatus === 'rejected')
                                                <form method="POST" action="{{ route('tenant.modules.request') }}">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">
                                                        Request Again
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('tenant.modules.request') }}">
                                                    @csrf
                                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">
                                                        Request Module
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-sm text-gray-500">No modules available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
