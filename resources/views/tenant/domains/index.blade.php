<x-app-layout>
    @php
        $statusMeta = function (?string $status): array {
            return match ($status) {
                'active' => ['label' => 'Active', 'variant' => 'success'],
                'pending_validation' => ['label' => 'Pending Validation', 'variant' => 'warning'],
                'initializing' => ['label' => 'Initializing', 'variant' => 'info'],
                'pending' => ['label' => 'Pending', 'variant' => 'neutral'],
                default => ['label' => 'Pending', 'variant' => 'neutral'],
            };
        };

        $primaryDomain = $domains->first(fn ($domain) => $domainService->isPrimarySubDomain($tenant, $domain->domain));
    @endphp

    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Custom Domains</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $domains->count() }} domain{{ $domains->count() === 1 ? '' : 's' }}</p>
            </div>
            <a href="{{ route('tenant.domains.create', absolute: false) }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out">
                <x-heroicon-o-plus class="w-4 h-4" />
                Add Domain
            </a>
        </div>

        {{-- Flash Messages --}}
        @foreach (['success', 'error', 'warning', 'info'] as $msg)
            @if (session($msg))
                <div class="mb-6">
                    <x-alert :variant="$msg">{{ session($msg) }}</x-alert>
                </div>
            @endif
        @endforeach

        @if ($domains->isEmpty())
            {{-- Empty State --}}
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center mb-4">
                    <x-heroicon-o-globe-alt class="w-6 h-6 text-brand-600 dark:text-brand-400" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">No custom domains yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Add your first custom domain to start setup.</p>
                <a href="{{ route('tenant.domains.create', absolute: false) }}">
                    <x-primary-button type="button">
                        <x-heroicon-o-plus class="w-4 h-4" />
                        Add Domain
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
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Domain</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Hostname</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">SSL</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Last Check</th>
                                <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-[72px]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#181820]">
                            @foreach ($domains as $domain)
                                @php
                                    $isPrimary = $domainService->isPrimarySubDomain($tenant, $domain->domain);
                                    $host = $isPrimary
                                        ? ['label' => 'Trusted', 'variant' => 'brand']
                                        : $statusMeta($domain->cf_hostname_status);
                                    $ssl = $isPrimary
                                        ? ['label' => 'Local TLS', 'variant' => 'brand']
                                        : $statusMeta($domain->cf_ssl_status);
                                @endphp
                                <tr class="group hover:bg-gray-50/70 dark:hover:bg-[#181820]/70 transition-all duration-150">
                                    {{-- Domain Name --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                                <x-heroicon-o-globe-alt class="w-5 h-5 text-brand-600 dark:text-brand-400" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $domain->domain }}</div>
                                                @if ($isPrimary)
                                                    <x-badge variant="brand">Primary</x-badge>
                                                @elseif ($domain->verified_at)
                                                    <x-badge variant="success">Live with SSL</x-badge>
                                                @elseif ($domain->cf_error)
                                                    <x-badge variant="danger">Needs attention</x-badge>
                                                @else
                                                    <x-badge variant="warning">Waiting for activation</x-badge>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Hostname --}}
                                    <td class="px-5 py-4">
                                        <x-badge :variant="$host['variant']">{{ $host['label'] }}</x-badge>
                                    </td>

                                    {{-- SSL --}}
                                    <td class="px-5 py-4">
                                        <x-badge :variant="$ssl['variant']">{{ $ssl['label'] }}</x-badge>
                                    </td>

                                    {{-- Last Check --}}
                                    <td class="px-5 py-4">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '—' }}</span>
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
                                                <x-dropdown-link :href="route('tenant.domains.show', $domain, absolute: false)">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    {{ $domain->verified_at ? 'View details' : 'Setup domain' }}
                                                </x-dropdown-link>

                                                @if (! $isPrimary)
                                                    <div class="border-t border-gray-100 dark:border-[#262632] my-1"></div>

                                                    <button type="button"
                                                        @click="$dispatch('open-modal', 'delete-domain-{{ $domain->id }}')"
                                                        class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition duration-150">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                        Remove domain
                                                    </button>
                                                @endif
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
                    @foreach ($domains as $domain)
                        @php
                            $isPrimary = $domainService->isPrimarySubDomain($tenant, $domain->domain);
                            $host = $isPrimary
                                ? ['label' => 'Trusted', 'variant' => 'brand']
                                : $statusMeta($domain->cf_hostname_status);
                            $ssl = $isPrimary
                                ? ['label' => 'Local TLS', 'variant' => 'brand']
                                : $statusMeta($domain->cf_ssl_status);
                        @endphp
                        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-[#181820]/50 transition-colors duration-150">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                        <x-heroicon-o-globe-alt class="w-5 h-5 text-brand-600 dark:text-brand-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $domain->domain }}</div>
                                        @if ($isPrimary)
                                            <x-badge variant="brand">Primary</x-badge>
                                        @elseif ($domain->verified_at)
                                            <x-badge variant="success">Live with SSL</x-badge>
                                        @elseif ($domain->cf_error)
                                            <x-badge variant="danger">Needs attention</x-badge>
                                        @else
                                            <x-badge variant="warning">Waiting for activation</x-badge>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 text-sm mb-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-gray-500 dark:text-gray-400">Hostname:</span>
                                    <x-badge :variant="$host['variant']">{{ $host['label'] }}</x-badge>
                                </div>
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-gray-500 dark:text-gray-400">SSL:</span>
                                    <x-badge :variant="$ssl['variant']">{{ $ssl['label'] }}</x-badge>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('tenant.domains.show', $domain, absolute: false) }}"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-[#181820] border border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] transition duration-150">
                                    {{ $domain->verified_at ? 'View' : 'Setup' }}
                                </a>
                                @if (! $isPrimary)
                                    <button type="button"
                                        @click="$dispatch('open-modal', 'delete-domain-{{ $domain->id }}')"
                                        class="inline-flex items-center justify-center px-3 py-2 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/20 transition duration-150">
                                        Remove
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Delete Modals --}}
            @foreach ($domains as $domain)
                @php $isPrimary = $domainService->isPrimarySubDomain($tenant, $domain->domain); @endphp
                @if (! $isPrimary)
                    <x-delete-modal name="delete-domain-{{ $domain->id }}" :action="route('tenant.domains.destroy', $domain, absolute: false)" entity="domain" />
                @endif
            @endforeach
        @endif
    </div>
</x-app-layout>
