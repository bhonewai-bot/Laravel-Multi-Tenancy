<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Custom Domain</h2>
            <a href="{{ route('tenant.domains.index', absolute: false) }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                Back to Domains
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-4 xl:grid-cols-[1.45fr_0.95fr]">
                <div class="rounded-lg bg-white p-6 pt-2 shadow-sm ring-1 ring-gray-100">

                    <form method="POST" action="{{ route('tenant.domains.store', absolute: false) }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="domain" value="Domain" />
                            <x-text-input id="domain" name="domain" type="text" class="mt-1 block w-full" placeholder="shop.example.com" :value="old('domain')" required />
                            <p class="mt-2 text-sm text-gray-500">Enter the custom domain you own (e.g. <span class="font-semibold">shop.example.com</span> or <span class="font-semibold">www.example.com</span>).</p>
                            <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                        </div>

                        <div class="flex justify-end gap-2">
                            <a href="{{ route('tenant.domains.index', absolute: false) }}"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">
                                Add Domain
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-lg bg-stone-50 p-5 text-sm text-stone-700 ring-1 ring-stone-200">
                    <p class="font-semibold">After adding the domain</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>Create a <span class="font-semibold">CNAME</span> to <span class="font-semibold">{{ config('cloudflare.fallback_origin') }}</span>.</li>
                        <li>If DNS is also on Cloudflare, keep the record as <span class="font-semibold">DNS only</span>.</li>
                        <li>Open the setup page and click <span class="font-semibold">Check Status</span> until Hostname and SSL are active.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
