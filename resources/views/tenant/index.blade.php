<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Tenants</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $tenants->total() }} tenant organizations</p>
            </div>
            <a href="{{ route('tenants.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:bg-brand-700 active:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition-all duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Tenant
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 flex items-center gap-3 rounded-lg bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        @if ($tenants->isEmpty())
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">No tenants yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Create your first tenant organization to get started.</p>
                <a href="{{ route('tenants.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Tenant
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
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tenant</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Contact</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Domain</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</th>
                                <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[72px]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#181820]">
                            @foreach ($tenants as $tenant)
                                @php $primaryDomain = $tenant->domains->first()?->domain; @endphp
                                <tr class="group border-l-2 border-transparent hover:border-l-brand-500 hover:bg-gray-50/70 dark:hover:bg-[#181820]/70 transition-all duration-150">
                                    {{-- Tenant Name --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                                <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ strtoupper(substr($tenant->name ?? 'N', 0, 1)) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $tenant->name ?? 'N/A' }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">#{{ $tenant->id }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Contact Email --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $tenant->email ?? 'N/A' }}</span>
                                    </td>

                                    {{-- Domain --}}
                                    <td class="px-5 py-4">
                                        @if ($primaryDomain)
                                            <a class="inline-flex items-center gap-1.5 text-sm text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors duration-150"
                                                href="http://{{ $primaryDomain }}" target="_blank" rel="noopener noreferrer">
                                                <svg class="w-3.5 h-3.5 shrink-0 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                </svg>
                                                {{ $primaryDomain }}
                                            </a>
                                        @else
                                            <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>

                                    {{-- Created --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ optional($tenant->created_at)->format('M d, Y') }}</span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-5 py-4 text-right">
                                        <x-dropdown align="right" width="w-40">
                                            <x-slot name="trigger">
                                                <button type="button"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition duration-150">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                    </svg>
                                                </button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <x-dropdown-link :href="route('tenants.show', $tenant)">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    View details
                                                </x-dropdown-link>

                                                <x-dropdown-link :href="route('tenants.edit', $tenant)">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                                    Edit tenant
                                                </x-dropdown-link>

                                                <div class="border-t border-gray-100 dark:border-[#262632] my-1"></div>

                                                <form method="POST" action="{{ route('tenants.destroy', $tenant) }}"
                                                    onsubmit="return confirm('Are you sure you want to delete this tenant? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition duration-150">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                        Delete tenant
                                                    </button>
                                                </form>
                                            </x-slot>
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="lg:hidden divide-y divide-gray-100 dark:divide-[#181820]">
                    @foreach ($tenants as $tenant)
                        @php $primaryDomain = $tenant->domains->first()?->domain; @endphp
                        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-[#181820]/50 transition-colors duration-150">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                        <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ strtoupper(substr($tenant->name ?? 'N', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $tenant->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tenant->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">#{{ $tenant->id }}</span>
                            </div>

                            <div class="flex items-center gap-4 text-sm mb-3">
                                <div class="flex items-center gap-1.5">
                                    @if ($primaryDomain)
                                        <a class="text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors"
                                            href="http://{{ $primaryDomain }}" target="_blank" rel="noopener noreferrer">
                                            {{ $primaryDomain }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">No domain</span>
                                    @endif
                                </div>
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ optional($tenant->created_at)->format('M d, Y') }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('tenants.show', $tenant) }}"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-[#181820] border border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] transition duration-150">
                                    View
                                </a>
                                <a href="{{ route('tenants.edit', $tenant) }}"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-[#181820] border border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] transition duration-150">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('tenants.destroy', $tenant) }}"
                                    onsubmit="return confirm('Delete this tenant? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center px-3 py-2 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/20 transition duration-150">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Pagination --}}
            @if ($tenants->hasPages())
                <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Showing {{ $tenants->firstItem() }} to {{ $tenants->lastItem() }} of {{ $tenants->total() }} tenants</span>
                    <div>{{ $tenants->links() }}</div>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
