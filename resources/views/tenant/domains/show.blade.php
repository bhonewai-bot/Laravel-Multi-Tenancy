<x-app-layout>
    @php
        $statusMeta = function (?string $status): array {
            return match ($status) {
                'active' => ['label' => 'Active', 'badge' => 'bg-green-100 text-green-700', 'desc' => 'Cloudflare reports this status as healthy.'],
                'pending_validation' => ['label' => 'Pending Validation', 'badge' => 'bg-amber-100 text-amber-700', 'desc' => 'Waiting for DNS/CNAME validation.'],
                'initializing' => ['label' => 'Initializing', 'badge' => 'bg-sky-100 text-sky-700', 'desc' => 'Cloudflare is creating this resource.'],
                'pending_deployment' => ['label' => 'Pending Deployment', 'badge' => 'bg-indigo-100 text-indigo-700', 'desc' => 'Certificate deployment is in progress.'],
                'moved' => ['label' => 'Moved', 'badge' => 'bg-orange-100 text-orange-700', 'desc' => 'Hostname points to a different target.'],
                'expired' => ['label' => 'Expired', 'badge' => 'bg-rose-100 text-rose-700', 'desc' => 'Certificate expired.'],
                default => ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-700', 'desc' => 'Status is pending.'],
            };
        };

        $host = $statusMeta($domain->cf_hostname_status);
        $ssl = $statusMeta($domain->cf_ssl_status);
        $isActive = $domain->verified_at !== null;
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Domain Setup</h2>
            <a href="{{ route('tenant.domains.index', absolute: false) }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                Back to Domains
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

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="space-y-6 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">{{ $domain->domain }}</h3>
                            <p class="mt-1 text-sm text-gray-500">Added {{ optional($domain->created_at)->format('F d, Y') }}</p>
                        </div>
                        @if ($isActive)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">
                                Live with SSL
                            </span>
                        @endif
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Hostname Routing</p>
                            <div class="mt-2">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $host['badge'] }}">{{ $host['label'] }}</span>
                            </div>
                            <p class="mt-3 text-sm text-gray-600">{{ $host['desc'] }}</p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">SSL Certificate</p>
                            <div class="mt-2">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $ssl['badge'] }}">{{ $ssl['label'] }}</span>
                            </div>
                            <p class="mt-3 text-sm text-gray-600">{{ $ssl['desc'] }}</p>
                        </div>
                    </div>

                    @if ($isActive)
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-700">
                            <p class="text-sm font-semibold">Domain is fully active.</p>
                            <a href="https://{{ $domain->domain }}" target="_blank" class="mt-1 inline-block text-sm font-semibold underline">
                                Visit https://{{ $domain->domain }}
                            </a>
                        </div>
                    @else
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                            <p class="font-semibold">DNS configuration</p>
                            <p class="mt-2">Add this CNAME in your DNS provider before checking status.</p>

                            <div class="mt-4 grid gap-3 rounded-lg bg-white p-4 ring-1 ring-indigo-100 sm:grid-cols-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase text-gray-500">Type</p>
                                    <p class="mt-1 font-mono text-sm text-gray-800">CNAME</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase text-gray-500">Name</p>
                                    <p class="mt-1 font-mono text-sm text-gray-800">{{ $cnameName }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase text-gray-500">Target</p>
                                    <p class="mt-1 break-all font-mono text-sm text-gray-800">{{ $fallbackOrigin }}</p>
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-indigo-700">If customer DNS is Cloudflare, set proxy to DNS only (grey cloud).</p>
                        </div>
                    @endif

                    @if ($domain->cf_error)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            <p class="font-semibold">Cloudflare note</p>
                            <p class="mt-1">{{ $domain->cf_error }}</p>
                        </div>
                    @endif

                    <div class="flex items-center justify-end">
                        <form method="POST" action="{{ route('tenant.domains.check-status', $domain, absolute: false) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                                Check Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
