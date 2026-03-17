<x-app-layout>
    @php
        $statusMeta = function (?string $status): array {
            return match ($status) {
                'active' => ['label' => 'Active', 'badge' => 'bg-green-100 text-green-700'],
                'pending_validation' => ['label' => 'Pending Validation', 'badge' => 'bg-amber-100 text-amber-700'],
                'initializing' => ['label' => 'Initializing', 'badge' => 'bg-sky-100 text-sky-700'],
                'pending' => ['label' => 'Pending', 'badge' => 'bg-stone-100 text-stone-700'],
                default => ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-700'],
            };
        };

        $primaryDomain = $domains->first(fn ($domain) => $domainService->isPrimarySubDomain($tenant, $domain->domain));
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Custom Domains</h2>
            <a href="{{ route('tenant.domains.create', absolute: false) }}"
                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">
                Add Domain
            </a>
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

            <!-- <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Domains</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $domains->count() }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Verified</p>
                    <p class="mt-2 text-2xl font-semibold text-green-700">{{ $domains->filter(fn ($domain) => $domain->verified_at)->count() }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Needs Attention</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-700">{{ $domains->filter(fn ($domain) => ! $domain->verified_at || $domain->cf_error)->count() }}</p>
                </div>
            </div> -->

            <div class="bg-white overflow-visible shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($domains->isNotEmpty())
                        <div class="overflow-visible">
                            <table class="w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Domain</th>
                                        <!-- <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">State</th> -->
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Hostname</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">SSL</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Check</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($domains as $domain)
                                        @php
                                            $isPrimary = $domainService->isPrimarySubDomain($tenant, $domain->domain);
                                            $host = $isPrimary
                                                ? ['label' => 'Trusted', 'badge' => 'bg-indigo-100 text-indigo-700']
                                                : $statusMeta($domain->cf_hostname_status);
                                            $ssl = $isPrimary
                                                ? ['label' => 'Local TLS', 'badge' => 'bg-indigo-100 text-indigo-700']
                                                : $statusMeta($domain->cf_ssl_status);
                                        @endphp
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-900">{{ $domain->domain }}</div>
                                                @if ($isPrimary)
                                                    <div class="mt-1 text-xs text-indigo-700">Primary</div>
                                                @elseif ($domain->verified_at)
                                                    <div class="mt-1 text-xs text-green-700">Live with SSL</div>
                                                @elseif ($domain->cf_error)
                                                    <div class="mt-1 text-xs text-red-700">Cloudflare needs attention</div>
                                                @else
                                                    <div class="mt-1 text-xs text-amber-700">Waiting for activation</div>
                                                @endif
                                            </td>
                                            <!-- <td class="px-6 py-4">
                                                @if ($isPrimary)
                                                    <div class="text-sm font-medium text-indigo-700">Trusted primary domain</div>
                                                    <div class="mt-1 text-xs text-gray-500">No custom hostname setup required.</div>
                                                @elseif ($domain->verified_at)
                                                    <div class="text-sm font-medium text-green-700">Verified</div>
                                                    <div class="mt-1 text-xs text-gray-500">Hostname and SSL are both active.</div>
                                                @elseif ($domain->cf_error)
                                                    <div class="text-sm font-medium text-red-700">Blocked</div>
                                                    <div class="mt-1 max-w-xs truncate text-xs text-gray-500" title="{{ $domain->cf_error }}">{{ $domain->cf_error }}</div>
                                                @else
                                                    <div class="text-sm font-medium text-amber-700">Pending</div>
                                                    <div class="mt-1 text-xs text-gray-500">Cloudflare is still validating this domain.</div>
                                                @endif
                                            </td> -->
                                            <td class="px-6 py-4">
                                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $host['badge'] }}">{{ $host['label'] }}</span>
                                                <!-- @if ($domain->cf_hostname_id)
                                                    <div class="mt-1 font-mono text-[11px] text-gray-500">{{ $domain->cf_hostname_id }}</div>
                                                @endif -->
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $ssl['badge'] }}">{{ $ssl['label'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                <div>{{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '-' }}</div>
                                                <div class="mt-1 text-xs text-gray-500">Added {{ optional($domain->created_at)->format('M d, Y') ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    <a href="{{ route('tenant.domains.show', $domain, absolute: false) }}"
                                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                                                        {{ $domain->verified_at ? 'View' : 'Setup' }}
                                                    </a>

                                                    @if (! $isPrimary)
                                                        <form method="POST" action="{{ route('tenant.domains.destroy', $domain, absolute: false) }}"
                                                            onsubmit="return confirm('Remove this custom domain?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="inline-flex items-center rounded-md border border-red-600 bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-500">
                                                                Remove
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="py-10 text-center">
                            <h3 class="text-sm font-medium text-gray-900">No custom domains yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Add your first custom domain to start setup.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
