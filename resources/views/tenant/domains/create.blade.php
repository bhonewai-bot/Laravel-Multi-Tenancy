<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Custom Domain</h2>
            <a href="{{ route('tenant.domains.index', absolute: false) }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                My Domains
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
                    <p class="font-semibold">Domain setup failed</p>
                    <p class="mt-1">{{ session('error') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-12">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 xl:col-span-8">
                    <h3 class="text-lg font-semibold text-gray-900">Custom Domain Setup</h3>
                    <p class="mt-1 text-sm text-gray-600">Enter your domain host, then verify ownership with DNS TXT.</p>

                    <form method="POST" action="{{ route('tenant.domains.store', absolute: false) }}" class="mt-6 space-y-5">
                        @csrf
                        <div>
                            <x-input-label for="domain" value="Domain Name" />
                            <x-text-input id="domain" name="domain" type="text" class="mt-1 block w-full" placeholder="shop.example.com" :value="old('domain')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                        </div>

                        <div class="rounded-lg bg-indigo-50 p-4 text-sm text-indigo-900 ring-1 ring-indigo-100">
                            <p class="font-semibold">Input format</p>
                            <p class="mt-1">Use host only (example: <span class="font-semibold">shop.example.com</span>).</p>
                            <p>Do not include <span class="font-semibold">http://</span>, <span class="font-semibold">https://</span>, or URL paths.</p>
                        </div>

                        <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 ring-1 ring-gray-100">
                            <p class="font-semibold text-gray-900">After adding the domain</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                <li>Copy TXT record name/value from <span class="font-semibold">My Domains</span> and verify ownership.</li>
                                <li>Add an <span class="font-semibold">A</span> or <span class="font-semibold">CNAME</span> record for traffic routing.</li>
                                <li>Use <span class="font-semibold">https://your-domain</span> (no port 8000).</li>
                            </ul>
                        </div>

                        <div class="flex justify-end gap-2">
                            <a href="{{ route('tenant.domains.index', absolute: false) }}"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <x-primary-button>Add Domain</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="space-y-6 xl:col-span-4">
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700">Verification Flow</h3>

                        <div class="mt-4 space-y-4 text-sm text-gray-700">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="font-semibold">1) Add Domain</p>
                                <p class="mt-1">Save your custom domain from this form.</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="font-semibold">2) Add DNS TXT</p>
                                <p class="mt-1">On My Domains page, copy the TXT name and TXT value.</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="font-semibold">3) Verify</p>
                                <p class="mt-1">Click Verify after DNS propagation. Only verified domains are allowed.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700">DNS Record Quick Guide</h3>
                        <div class="mt-4 space-y-3 text-sm text-gray-700">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="font-semibold text-gray-900">Ownership (required)</p>
                                <p class="mt-1">Add the TXT record name/value from <span class="font-semibold">My Domains</span>.</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="font-semibold text-gray-900">Routing (required)</p>
                                <p class="mt-1">Add <span class="font-semibold">A</span> (to server IP) or <span class="font-semibold">CNAME</span> (to your target host).</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <p class="font-semibold text-gray-900">Access URL</p>
                                <p class="mt-1">Use <span class="font-semibold">https://your-domain</span> after DNS propagates.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
