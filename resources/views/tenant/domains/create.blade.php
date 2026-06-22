<x-app-layout>
    <div class="py-8">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            <x-page-header title="Add Custom Domain" description="Register a new custom domain for your application.">
                <x-slot name="actions">
                    <a href="{{ route('tenant.domains.index', absolute: false) }}">
                        <x-secondary-button>Back to Domains</x-secondary-button>
                    </a>
                </x-slot>
            </x-page-header>

            @if (session('error'))
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-800 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-800 dark:text-red-400">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-4">
                <x-card>
                    <form method="POST" action="{{ route('tenant.domains.store', absolute: false) }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="domain" value="Domain" />
                            <x-text-input
                                id="domain"
                                name="domain"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-[#2a2a38] dark:bg-[#14141c] dark:text-gray-100 focus:border-brand-500 focus:ring-brand-500"
                                placeholder="shop.example.com"
                                :value="old('domain')"
                                required
                            />
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Enter the custom domain you own (e.g. <span class="font-semibold">shop.example.com</span> or <span class="font-semibold">www.example.com</span>).</p>
                            <x-input-error class="mt-2" :messages="$errors->get('domain')" />
                        </div>

                        <div class="flex justify-end gap-2">
                            <a href="{{ route('tenant.domains.index', absolute: false) }}">
                                <x-secondary-button type="button">Cancel</x-secondary-button>
                            </a>
                            <x-primary-button>Add Domain</x-primary-button>
                        </div>
                    </form>
                </x-card>

                <x-card>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">After adding the domain</p>
                        <ul class="list-disc space-y-1 pl-5">
                            <li>Create a <span class="font-semibold">CNAME</span> to <span class="font-semibold">{{ config('cloudflare.fallback_origin') }}</span>.</li>
                            <li>If DNS is also on Cloudflare, keep the record as <span class="font-semibold">DNS only</span>.</li>
                            <li>Open the setup page and click <span class="font-semibold">Check Status</span> until Hostname and SSL are active.</li>
                        </ul>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
