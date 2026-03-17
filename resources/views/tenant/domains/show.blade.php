<x-app-layout>
    @php
        $statusMeta = function (?string $status, string $type): array {
            $map = match ($status) {
                'active' => ['label' => 'Active', 'badge' => 'bg-green-100 text-green-700', 'desc' => 'Cloudflare reports this as healthy.'],
                'pending_validation' => ['label' => 'Pending Validation', 'badge' => 'bg-amber-100 text-amber-700', 'desc' => 'Waiting for DNS and certificate validation.'],
                'initializing' => ['label' => 'Initializing', 'badge' => 'bg-sky-100 text-sky-700', 'desc' => 'Cloudflare is preparing this resource.'],
                'pending' => ['label' => 'Pending', 'badge' => 'bg-stone-100 text-stone-700', 'desc' => 'Cloudflare has not activated this stage yet.'],
                default => ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-700', 'desc' => 'Status is still pending.'],
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
        $stateTone = $isActive
            ? 'border-green-200 bg-green-50 text-green-800'
            : ($domain->cf_error ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-800');
        $stateTitle = $isActive ? 'Ready to serve traffic' : ($domain->cf_error ? 'Cloudflare needs attention' : 'Activation still in progress');
        $stateDescription = $isActive
            ? 'Hostname routing and SSL are both active, so this domain is verified.'
            : ($domain->cf_error
                ? 'The last sync returned an error. Review the details below, fix the DNS or Cloudflare issue, then check status again.'
                : 'This domain has been created, but Cloudflare has not finished validating it yet.');
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Domain Setup</h2>
            <div class="flex items-center gap-2">
                @if ($isActive)
                    <a href="https://{{ $domain->domain }}" target="_blank"
                        class="inline-flex items-center rounded-md border border-green-600 bg-green-50 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-green-700 hover:bg-green-100">
                        Visit Site
                    </a>
                @endif
                <a href="{{ route('tenant.domains.index', absolute: false) }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                    All Domains
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @foreach (['success', 'error', 'warning', 'info'] as $msg)
                @if (session($msg))
                    @php
                        $style = match ($msg) {
                            'success' => 'border-green-200 bg-green-50 text-green-700',
                            'error' => 'border-red-200 bg-red-50 text-red-700',
                            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
                            default => 'border-sky-200 bg-sky-50 text-sky-700',
                        };
                    @endphp
                    <div class="rounded-md border p-4 text-sm {{ $style }}">
                        {{ session($msg) }}
                    </div>
                @endif
            @endforeach

            <div class="rounded-md border p-4 text-sm {{ $stateTone }}">
                <p class="font-semibold">{{ $stateTitle }}</p>
                <p class="mt-1">{{ $stateDescription }}</p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $domain->domain }}</h3>
                    <p class="mt-1 text-sm text-gray-500">Added {{ optional($domain->created_at)->format('F d, Y') }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Hostname Routing</p>
                                <p class="mt-2 text-sm text-gray-600">{{ $host['desc'] }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $host['badge'] }}">{{ $host['label'] }}</span>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">SSL Certificate</p>
                                <p class="mt-2 text-sm text-gray-600">{{ $ssl['desc'] }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $ssl['badge'] }}">{{ $ssl['label'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Verification</p>
                        <p class="mt-2 text-sm font-medium {{ $isActive ? 'text-green-700' : 'text-amber-700' }}">{{ $isActive ? 'Verified' : 'Not verified yet' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Verified at: {{ optional($domain->verified_at)->format('M d, Y H:i') ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Cloudflare Hostname ID</p>
                        <p class="mt-2 break-all font-mono text-sm text-gray-800">{{ $domain->cf_hostname_id ?: '-' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Used to poll Cloudflare status for this domain.</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Last Sync</p>
                        <p class="mt-2 text-sm font-medium text-gray-800">{{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '-' }}</p>
                        <p class="mt-1 text-xs text-gray-500">This updates whenever create or check-status runs.</p>
                    </div>
                </div>

                @if (! $isPrimary)
                    <div class="pt-5">
                        <div class="rounded-lg bg-stone-50 p-5 text-sm text-stone-700 ring-1 ring-stone-200">
                            <p class="font-semibold">DNS configuration</p>
                            <p class="mt-1">Ask the tenant to create this record.</p>

                            <div class="mt-4 overflow-x-auto rounded-lg bg-white ring-1 ring-stone-200">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                        <tr>
                                            <th class="px-4 py-3">Type</th>
                                            <th class="px-4 py-3">Name / Host</th>
                                            <th class="px-4 py-3">Target / Value</th>
                                            <th class="px-4 py-3">Proxy</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white text-gray-700">
                                        <tr>
                                            <td class="px-4 py-3 font-semibold">CNAME</td>
                                            <td class="px-4 py-3 font-mono text-xs">{{ $cnameName }}</td>
                                            <td class="px-4 py-3 break-all font-mono text-xs">{{ $fallbackOrigin }}</td>
                                            <td class="px-4 py-3">DNS only</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p class="mt-3 text-xs text-stone-600">If the tenant also uses Cloudflare DNS, the record should stay DNS only during verification.</p>
                        </div>
                    </div>
                @endif

                <div class="mt-5 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Last checked: {{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '-' }}
                    </div>

                    @if ($canCheckStatus)
                        <form method="POST" action="{{ route('tenant.domains.check-status', $domain, absolute: false) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                Check Status
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Domain Diagnostics</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <th class="w-56 py-3 font-medium text-gray-500">Domain</th>
                                <td class="py-3 text-gray-900">{{ $domain->domain }}</td>
                            </tr>
                            <tr>
                                <th class="py-3 font-medium text-gray-500">Hostname status</th>
                                <td class="py-3 text-gray-900">{{ $domain->cf_hostname_status ?? 'not available' }}</td>
                            </tr>
                            <tr>
                                <th class="py-3 font-medium text-gray-500">SSL status</th>
                                <td class="py-3 text-gray-900">{{ $domain->cf_ssl_status ?? 'not available' }}</td>
                            </tr>
                            <tr>
                                <th class="py-3 font-medium text-gray-500">Verified</th>
                                <td class="py-3 text-gray-900">{{ $isActive ? 'yes' : 'no' }}</td>
                            </tr>
                            <tr>
                                <th class="py-3 font-medium text-gray-500">Last checked</th>
                                <td class="py-3 text-gray-900">{{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="py-3 font-medium text-gray-500">Cloudflare error</th>
                                <td class="py-3 text-gray-900">{{ $domain->cf_error ?: 'none' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($domain->cf_error)
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Cloudflare error</p>
                    <p class="mt-1">{{ $domain->cf_error }}</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
