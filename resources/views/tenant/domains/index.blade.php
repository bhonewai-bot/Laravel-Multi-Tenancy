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

    <div class="py-8">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            <x-page-header title="Custom Domains" description="Manage custom domains for your application.">
                <x-slot name="actions">
                    <a href="{{ route('tenant.domains.create', absolute: false) }}">
                        <x-primary-button>Add Domain</x-primary-button>
                    </a>
                </x-slot>
            </x-page-header>

            @foreach (['success', 'error', 'warning', 'info'] as $msg)
                @if (session($msg))
                    @php
                        $variant = match ($msg) {
                            'success' => 'success',
                            'error' => 'danger',
                            'warning' => 'warning',
                            default => 'info',
                        };
                    @endphp
                    <div class="rounded-lg border p-4 text-sm {{ $variant === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800' : ($variant === 'danger' ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800' : ($variant === 'warning' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800' : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800')) }}">
                        {{ session($msg) }}
                    </div>
                @endif
            @endforeach

            <x-card>
                @if ($domains->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-[#262632] text-sm">
                            <thead class="bg-gray-50 dark:bg-[#0e0e15]">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Domain</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Hostname</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">SSL</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Last Check</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-[#101016] divide-y divide-gray-200 dark:divide-[#262632]">
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
                                    <tr class="border-t border-gray-200 dark:border-[#262632] hover:bg-gray-50 dark:hover:bg-[#181820]">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $domain->domain }}</div>
                                            @if ($isPrimary)
                                                <div class="mt-1"><x-badge variant="brand">Primary</x-badge></div>
                                            @elseif ($domain->verified_at)
                                                <div class="mt-1"><x-badge variant="success">Live with SSL</x-badge></div>
                                            @elseif ($domain->cf_error)
                                                <div class="mt-1"><x-badge variant="danger">Needs attention</x-badge></div>
                                            @else
                                                <div class="mt-1"><x-badge variant="warning">Waiting for activation</x-badge></div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-badge :variant="$host['variant']">{{ $host['label'] }}</x-badge>
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-badge :variant="$ssl['variant']">{{ $ssl['label'] }}</x-badge>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                            <div>{{ optional($domain->cf_last_checked_at)->format('M d, Y H:i') ?? '-' }}</div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">Added {{ optional($domain->created_at)->format('M d, Y') ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('tenant.domains.show', $domain, absolute: false) }}">
                                                    <x-secondary-button>{{ $domain->verified_at ? 'View' : 'Setup' }}</x-secondary-button>
                                                </a>

                                                @if (! $isPrimary)
                                                    <form method="POST" action="{{ route('tenant.domains.destroy', $domain, absolute: false) }}"
                                                        onsubmit="return confirm('Remove this custom domain?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="inline-flex items-center rounded-lg border border-red-600 bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500 transition">
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
                    <x-empty-state title="No custom domains yet" description="Add your first custom domain to start setup.">
                        <x-slot name="action">
                            <a href="{{ route('tenant.domains.create', absolute: false) }}">
                                <x-primary-button>Add Domain</x-primary-button>
                            </a>
                        </x-slot>
                    </x-empty-state>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
