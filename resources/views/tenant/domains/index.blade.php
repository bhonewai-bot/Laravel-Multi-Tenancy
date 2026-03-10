<x-app-layout>
    @php
        $statusMeta = function (?string $status): array {
            return match ($status) {
                'active' => ['label' => 'Active', 'badge' => 'bg-green-100 text-green-700', 'hint' => 'Healthy'],
                'pending_validation' => ['label' => 'Pending Validation', 'badge' => 'bg-amber-100 text-amber-700', 'hint' => 'Waiting DNS'],
                'initializing' => ['label' => 'Initializing', 'badge' => 'bg-sky-100 text-sky-700', 'hint' => 'Provisioning'],
                'pending_deployment' => ['label' => 'Pending Deployment', 'badge' => 'bg-indigo-100 text-indigo-700', 'hint' => 'Deploying cert'],
                'moved' => ['label' => 'Moved', 'badge' => 'bg-orange-100 text-orange-700', 'hint' => 'DNS mismatch'],
                'deleted' => ['label' => 'Deleted', 'badge' => 'bg-gray-200 text-gray-700', 'hint' => 'Removed'],
                'expired' => ['label' => 'Expired', 'badge' => 'bg-rose-100 text-rose-700', 'hint' => 'Certificate expired'],
                default => ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-700', 'hint' => 'Not ready'],
            };
        };
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
        <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
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

            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                <p class="font-semibold">Cloudflare activation flow</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Add domain in app, then create a CNAME to <span class="font-semibold">{{ config('cloudflare.fallback_origin') }}</span>.</li>
                    <li>Set DNS proxy to <span class="font-semibold">DNS only</span> (grey cloud) when customer DNS is also Cloudflare.</li>
                    <li>Click <span class="font-semibold">Check Status</span> until both Hostname and SSL are <span class="font-semibold">Active</span>.</li>
                </ul>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Domain</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Hostname</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">SSL</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Added On</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($domains as $domain)
                                    @php
                                        $isPrimary = $domainService->isPrimarySubDomain($tenant, $domain->domain);
                                        $host = $statusMeta($domain->cf_hostname_status);
                                        $ssl = $statusMeta($domain->cf_ssl_status);
                                    @endphp
                                    <tr>
                                        <td class="px-5 py-4">
                                            <div class="font-medium text-gray-900">{{ $domain->domain }}</div>
                                            @if ($domain->verified_at)
                                                <div class="mt-1 text-xs text-green-700">Live with SSL</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-gray-700">
                                            {{ $isPrimary ? 'Primary' : 'Custom' }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $host['badge'] }}">{{ $host['label'] }}</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $ssl['badge'] }}">{{ $ssl['label'] }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-gray-600">
                                            {{ optional($domain->created_at)->format('M d, Y') ?? '-' }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('tenant.domains.show', $domain, absolute: false) }}"
                                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                                                    View
                                                </a>

                                                @if (! $isPrimary)
                                                    <form method="POST" action="{{ route('tenant.domains.check-status', $domain, absolute: false) }}">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                                            Check Status
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('tenant.domains.destroy', $domain, absolute: false) }}" onsubmit="return confirm('Remove this custom domain?');">
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
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500">No domains found.</td>
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
