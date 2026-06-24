<x-app-layout>
    @php
        $statusMeta = function (?string $status, string $type): array {
            $map = match ($status) {
                'active' => ['label' => 'Active', 'variant' => 'success', 'desc' => 'Cloudflare reports this as healthy.'],
                'pending_validation' => ['label' => 'Pending Validation', 'variant' => 'warning', 'desc' => 'Waiting for DNS and certificate validation.'],
                'initializing' => ['label' => 'Initializing', 'variant' => 'info', 'desc' => 'Cloudflare is preparing this resource.'],
                'pending' => ['label' => 'Pending', 'variant' => 'neutral', 'desc' => 'Cloudflare has not activated this stage yet.'],
                default => ['label' => 'Pending', 'variant' => 'neutral', 'desc' => 'Status is still pending.'],
            };

            if ($status === null) {
                $map['desc'] = $type === 'hostname' ? 'Hostname status is not available yet.' : 'SSL status is not available yet.';
            }

            return $map;
        };

        $host = $statusMeta($domain->cf_hostname_status, 'hostname');
        $ssl = $statusMeta($domain->cf_ssl_status, 'ssl');
        $isActive = $domain->verified_at !== null;
        $isPrimary = $domainService->isPrimarySubDomain($tenant, $domain->domain);
        $canCheckStatus = ! $isPrimary && ! empty($domain->cf_hostname_id);
        $stateVariant = $isActive ? 'success' : ($domain->cf_error ? 'error' : 'warning');
        $stateTitle = $isActive ? 'Ready to serve traffic' : ($domain->cf_error ? 'Cloudflare needs attention' : 'Activation still in progress');
        $stateDescription = $isActive
            ? 'Hostname routing and SSL are both active, so this domain is verified.'
            : ($domain->cf_error
                ? 'The last sync returned an error. Review the details below, fix the DNS or Cloudflare issue, then check status again.'
                : 'This domain has been created, but Cloudflare has not finished validating it yet.');
    @endphp

    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Domain Setup</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $domain->domain }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($isActive)
                    <a href="https://{{ $domain->domain }}" target="_blank">
                        <x-secondary-button type="button">
                            <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                            Visit Site
                        </x-secondary-button>
                    </a>
                @endif
                <a href="{{ route('tenant.domains.index', absolute: false) }}">
                    <x-secondary-button type="button">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        All Domains
                    </x-secondary-button>
                </a>
            </div>
        </div>

        {{-- Flash Messages --}}
        @foreach (['success', 'error', 'warning', 'info'] as $msg)
            @if (session($msg))
                <div class="mb-6">
                    <x-alert :variant="$msg">{{ session($msg) }}</x-alert>
                </div>
            @endif
        @endforeach

        {{-- State Banner --}}
        <div class="mb-6">
            <x-alert :variant="$stateVariant" :title="$stateTitle">
                {{ $stateDescription }}
            </x-alert>
        </div>

        {{-- Domain Detail Card --}}
        <x-card>
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $domain->domain }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Added {{ optional($domain->created_at)->format('F d, Y') }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                {{-- Hostname routing status --}}
                <div class="rounded-lg border border-gray-200 dark:border-[#262632] bg-gray-50 dark:bg-[#181820] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Hostname Routing</p>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $host['desc'] }}</p>
                        </div>
                        <x-badge :variant="$host['variant']">{{ $host['label'] }}</x-badge>
                    </div>
                </div>

                {{-- SSL certificate status --}}
                <div class="rounded-lg border border-gray-200 dark:border-[#262632] bg-gray-50 dark:bg-[#181820] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">SSL Certificate</p>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $ssl['desc'] }}</p>
                        </div>
                        <x-badge :variant="$ssl['variant']">{{ $ssl['label'] }}</x-badge>
                    </div>
                </div>
            </div>

            {{-- Info grid --}}
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Verification</p>
                    <div class="mt-2">
                        <x-badge :variant="$isActive ? 'success' : 'warning'">{{ $isActive ? 'Verified' : 'Not verified yet' }}</x-badge>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">Verified at: {{ optional($domain->verified_at)->format('M d, Y H:i') ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cloudflare Hostname ID</p>
                    <p class="mt-2 break-all font-mono text-sm text-gray-900 dark:text-gray-100">{{ $domain->cf_hostname_id ?: '—' }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Used to poll Cloudflare status for this domain.</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Last Sync</p>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '—' }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">This updates whenever create or check-status runs.</p>
                </div>
            </div>

            {{-- DNS configuration (non-primary domains) --}}
            @if (! $isPrimary)
                <div class="pt-5">
                    <div class="rounded-lg bg-gray-50 dark:bg-[#181820] p-5 text-sm text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-[#262632]">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">DNS configuration</p>
                        <p class="mt-1">Ask the tenant to create this record.</p>

                        <div class="mt-4 overflow-x-auto rounded-lg bg-white dark:bg-[#101016] border border-gray-200 dark:border-[#262632]">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 dark:bg-[#0e0e15]">
                                    <tr>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name / Host</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Target / Value</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Proxy</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-[#101016] text-gray-700 dark:text-gray-300">
                                    <tr class="border-t border-gray-200 dark:border-[#262632]">
                                        <td class="px-4 py-3 font-semibold">CNAME</td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ $cnameName }}</td>
                                        <td class="px-4 py-3 break-all font-mono text-xs">{{ $fallbackOrigin }}</td>
                                        <td class="px-4 py-3">DNS only</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">If the tenant also uses Cloudflare DNS, the record should stay DNS only during verification.</p>
                    </div>
                </div>
            @endif

            <x-slot name="footer">
                <div class="flex items-center justify-between">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Last checked: {{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '—' }}
                    </div>

                    @if ($canCheckStatus)
                        <form method="POST" action="{{ route('tenant.domains.check-status', $domain, absolute: false) }}" x-data="{ submitting: false }" @submit="submitting = true">
                            @csrf
                            <button type="submit" :disabled="submitting"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-card hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] active:from-brand-600 active:to-brand-800 transition-all duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none">
                                <span x-show="!submitting">CHECK STATUS</span>
                                <span x-show="submitting" x-cloak>CHECKING...</span>
                            </button>
                        </form>
                    @endif
                </div>
            </x-slot>
        </x-card>

        {{-- Domain Diagnostics Card --}}
        <div class="mt-6">
            <x-card>
                <x-slot name="header">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Domain Diagnostics</h3>
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-[#262632]">
                            <tr class="border-t border-gray-200 dark:border-[#262632]">
                                <th class="w-56 py-3 font-medium text-gray-500 dark:text-gray-400">Domain</th>
                                <td class="py-3 text-gray-900 dark:text-gray-100">{{ $domain->domain }}</td>
                            </tr>
                            <tr class="border-t border-gray-200 dark:border-[#262632]">
                                <th class="py-3 font-medium text-gray-500 dark:text-gray-400">Hostname status</th>
                                <td class="py-3 text-gray-900 dark:text-gray-100">{{ $domain->cf_hostname_status ?? 'not available' }}</td>
                            </tr>
                            <tr class="border-t border-gray-200 dark:border-[#262632]">
                                <th class="py-3 font-medium text-gray-500 dark:text-gray-400">SSL status</th>
                                <td class="py-3 text-gray-900 dark:text-gray-100">{{ $domain->cf_ssl_status ?? 'not available' }}</td>
                            </tr>
                            <tr class="border-t border-gray-200 dark:border-[#262632]">
                                <th class="py-3 font-medium text-gray-500 dark:text-gray-400">Verified</th>
                                <td class="py-3">
                                    <x-badge :variant="$isActive ? 'success' : 'neutral'">{{ $isActive ? 'yes' : 'no' }}</x-badge>
                                </td>
                            </tr>
                            <tr class="border-t border-gray-200 dark:border-[#262632]">
                                <th class="py-3 font-medium text-gray-500 dark:text-gray-400">Last checked</th>
                                <td class="py-3 text-gray-900 dark:text-gray-100">{{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '—' }}</td>
                            </tr>
                            <tr class="border-t border-gray-200 dark:border-[#262632]">
                                <th class="py-3 font-medium text-gray-500 dark:text-gray-400">Cloudflare error</th>
                                <td class="py-3 text-gray-900 dark:text-gray-100">{{ $domain->cf_error ?: 'none' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        @if ($domain->cf_error)
            <div class="mt-6">
                <x-alert variant="error" title="Cloudflare error">
                    {{ $domain->cf_error }}
                </x-alert>
            </div>
        @endif
    </div>
</x-app-layout>
