<x-app-layout>
    @php
        $recommendedCnameTarget = config('tenancy.central_domains.0') ?: parse_url(config('app.url'), PHP_URL_HOST);
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Domains</h2>
            <a href="{{ route('tenant.domains.create', absolute: false) }}"
                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">
                Add Domain
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div x-data="{
            expandedDomainId: null,
            copiedKey: null,
            async copy(text, key) {
                try {
                    await navigator.clipboard.writeText(text);
                    this.copiedKey = key;
                    setTimeout(() => { this.copiedKey = null; }, 2000);
                } catch (e) {
                    this.copiedKey = null;
                }
            }
        }" class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                <p class="font-semibold">Domain setup checklist</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Add TXT record exactly as shown in this table, then click <span class="font-semibold">Verify</span>.</li>
                    <li>Add an <span class="font-semibold">A</span> or <span class="font-semibold">CNAME</span> record so the custom domain points to your app server.</li>
                    <li>Open domains with <span class="font-semibold">https://your-domain</span> (do not use port 8000).</li>
                </ul>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-[35%] px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Domain</th>
                                    <th class="w-[12%] px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                    <th class="w-[13%] px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="w-[40%] px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($domains as $domain)
                                    @php
                                        $isPrimary = $domainService->isPrimarySubDomain($tenant, $domain->domain);
                                        $isVerified = $isPrimary || $domain->verified_at !== null;
                                        $domainId = (int) $domain->id;
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $domain->domain }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $isPrimary ? 'Primary' : 'Custom' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($isVerified)
                                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Verified</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-700">Pending DNS</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                @if (! $isPrimary)
                                                    <button type="button"
                                                        @click="expandedDomainId = expandedDomainId === {{ $domainId }} ? null : {{ $domainId }}"
                                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">
                                                        <span x-text="expandedDomainId === {{ $domainId }} ? 'Hide Setup' : 'Setup'"></span>
                                                    </button>
                                                @endif

                                                @if (! $isVerified && ! $isPrimary)
                                                    <form method="POST" action="{{ route('tenant.domains.verify', $domain, absolute: false) }}">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-indigo-500">
                                                            Verify DNS
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (! $isPrimary)
                                                    <form method="POST" action="{{ route('tenant.domains.destroy', $domain, absolute: false) }}" onsubmit="return confirm('Remove this custom domain?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center rounded-md border border-red-600 bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-red-500">
                                                            Remove
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    @if (! $isPrimary)
                                        <template x-if="expandedDomainId === {{ $domainId }}">
                                            <tr class="bg-gray-50">
                                                <td colspan="4" class="px-6 py-4">
                                                    <div class="grid gap-4 lg:grid-cols-2">
                                                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                                                            <h4 class="text-sm font-semibold text-gray-900">DNS setup</h4>
                                                            <p class="mt-1 text-xs text-gray-500">Add these records in your DNS provider dashboard.</p>

                                                            <div class="mt-3 space-y-3 text-xs">
                                                                <div class="rounded-lg bg-gray-50 p-3">
                                                                    <p class="mb-1 font-semibold text-gray-700">TXT Name</p>
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <code class="block max-w-[80%] overflow-x-auto rounded bg-white px-2 py-1 font-mono text-gray-800">{{ $domainService->verificationRecordName($domain->domain) }}</code>
                                                                        <button type="button"
                                                                            @click="copy('{{ $domainService->verificationRecordName($domain->domain) }}', 'txt-name-{{ $domainId }}')"
                                                                            class="rounded border border-gray-300 px-2 py-1 font-semibold text-gray-600 hover:bg-gray-100"
                                                                            x-text="copiedKey === 'txt-name-{{ $domainId }}' ? 'Copied!' : 'Copy'"></button>
                                                                    </div>
                                                                </div>

                                                                <div class="rounded-lg bg-gray-50 p-3">
                                                                    <p class="mb-1 font-semibold text-gray-700">TXT Value</p>
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <code class="block max-w-[80%] overflow-x-auto rounded bg-white px-2 py-1 font-mono text-gray-800">{{ $domain->verification_code }}</code>
                                                                        <button type="button"
                                                                            @click="copy('{{ $domain->verification_code }}', 'txt-value-{{ $domainId }}')"
                                                                            class="rounded border border-gray-300 px-2 py-1 font-semibold text-gray-600 hover:bg-gray-100"
                                                                            x-text="copiedKey === 'txt-value-{{ $domainId }}' ? 'Copied!' : 'Copy'"></button>
                                                                    </div>
                                                                </div>

                                                                <div class="rounded-lg bg-gray-50 p-3">
                                                                    <p class="mb-1 font-semibold text-gray-700">Suggested CNAME target</p>
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <code class="block max-w-[80%] overflow-x-auto rounded bg-white px-2 py-1 font-mono text-gray-800">{{ $recommendedCnameTarget }}</code>
                                                                        <button type="button"
                                                                            @click="copy('{{ $recommendedCnameTarget }}', 'cname-target-{{ $domainId }}')"
                                                                            class="rounded border border-gray-300 px-2 py-1 font-semibold text-gray-600 hover:bg-gray-100"
                                                                            x-text="copiedKey === 'cname-target-{{ $domainId }}' ? 'Copied!' : 'Copy'"></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                                                            <h4 class="text-sm font-semibold text-gray-900">Connection lifecycle</h4>
                                                            <ol class="mt-3 space-y-3 text-sm">
                                                                <li class="flex items-start gap-3">
                                                                    <span class="mt-0.5 h-5 w-5 rounded-full bg-green-100 text-center text-xs font-bold leading-5 text-green-700">1</span>
                                                                    <div>
                                                                        <p class="font-medium text-gray-800">Domain Added</p>
                                                                        <p class="text-xs text-gray-500">Saved in your tenant workspace.</p>
                                                                    </div>
                                                                </li>
                                                                <li class="flex items-start gap-3">
                                                                    <span class="mt-0.5 h-5 w-5 rounded-full {{ $isVerified ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700' }} text-center text-xs font-bold leading-5">2</span>
                                                                    <div>
                                                                        <p class="font-medium text-gray-800">DNS Records Detected</p>
                                                                        <p class="text-xs {{ $isVerified ? 'text-green-600' : 'text-indigo-600' }}">
                                                                            {{ $isVerified ? 'TXT verification passed.' : 'Waiting for TXT record propagation.' }}
                                                                        </p>
                                                                    </div>
                                                                </li>
                                                                <li class="flex items-start gap-3">
                                                                    <span class="mt-0.5 h-5 w-5 rounded-full {{ $isVerified ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} text-center text-xs font-bold leading-5">3</span>
                                                                    <div>
                                                                        <p class="font-medium text-gray-800">SSL Issuance</p>
                                                                        <p class="text-xs {{ $isVerified ? 'text-green-600' : 'text-gray-500' }}">
                                                                            {{ $isVerified ? 'Eligible for TLS at edge.' : 'Starts after DNS is verified.' }}
                                                                        </p>
                                                                    </div>
                                                                </li>
                                                                <li class="flex items-start gap-3">
                                                                    <span class="mt-0.5 h-5 w-5 rounded-full {{ $isVerified ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} text-center text-xs font-bold leading-5">4</span>
                                                                    <div>
                                                                        <p class="font-medium text-gray-800">Live</p>
                                                                        <p class="text-xs {{ $isVerified ? 'text-green-600' : 'text-gray-500' }}">
                                                                            {{ $isVerified ? 'Use https://' . $domain->domain : 'Available after SSL is ready.' }}
                                                                        </p>
                                                                    </div>
                                                                </li>
                                                            </ol>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-sm text-gray-500">No domains found.</td>
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
