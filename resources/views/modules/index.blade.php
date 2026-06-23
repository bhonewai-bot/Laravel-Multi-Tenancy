<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Modules">
            <x-slot:actions>
                <a href="{{ route('modules.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 transition-all duration-200">
                    + Create Module
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
                    @if ($modules->isEmpty())
                        <x-empty-state title="No modules found" description="Create your first module to get started." />
                    @else
                        <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-[#262632]">
                            <thead>
                                <tr>
                                    <th class="w-[22%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                                    <th class="w-[20%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Slug</th>
                                    <th class="w-[12%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Version</th>
                                    <th class="w-[12%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Price</th>
                                    <th class="w-[16%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="w-[18%] px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-[#262632]">
                                @foreach ($modules as $module)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-[#181820] transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $module->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $module->slug }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $module->version }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${{ number_format((float) $module->price, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($module->is_active)
                                                <x-badge variant="success" label="Active" />
                                            @else
                                                <x-badge variant="danger" label="Disabled" />
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <form method="POST" action="{{ route('modules.toggle', $module) }}" class="flex justify-end">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center rounded-lg border px-3 py-2 text-xs font-semibold uppercase tracking-widest shadow-card transition-colors {{ $module->is_active ? 'border-red-600 bg-red-600 text-white hover:bg-red-500' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-[#262632] dark:bg-[#181820] dark:text-gray-300 dark:hover:bg-[#262632]' }}">
                                                    {{ $module->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                @if ($modules->hasPages())
                    <x-slot:footer>
                        {{ $modules->links() }}
                    </x-slot:footer>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
