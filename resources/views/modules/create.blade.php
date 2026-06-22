<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Upload Module" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <x-card>
                <form method="POST" action="{{ route('modules.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div class="mb-6">
                        <x-input-label for="module_file" :value="__('Module ZIP File')" />
                        <div class="mt-1 flex justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-[#2a2a38] px-6 pb-6 pt-5 transition-colors hover:border-blue-400 dark:hover:border-blue-500">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                    />
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="module_file" class="relative cursor-pointer rounded-lg bg-white dark:bg-[#14141c] font-medium text-blue-600 dark:text-blue-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-2 hover:text-blue-500">
                                        <span>Upload a file</span>
                                        <input
                                            id="module_file"
                                            name="module_file"
                                            type="file"
                                            accept=".zip"
                                            required
                                            class="sr-only"
                                        />
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ZIP files up to 50MB</p>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('module_file')" class="mt-2" />
                    </div>

                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:bg-yellow-900/20 dark:border-yellow-800">
                        <h3 class="mb-2 font-semibold text-yellow-900 dark:text-yellow-400">Security Note</h3>
                        <p class="text-sm text-yellow-800 dark:text-yellow-500">
                            Only upload modules from trusted sources. Uploaded packages become part of the app codebase after validation.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('modules.index') }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 dark:border-[#2a2a38] bg-white dark:bg-[#1e1e28] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-[#2a2a38] transition-colors">
                            Cancel
                        </a>
                        <x-primary-button>Upload Module</x-primary-button>
                    </div>
                </form>
            </x-card>

            <div class="mt-6">
                <x-card>
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Expected Module Structure</h3>
                    <div class="rounded-lg bg-gray-50 dark:bg-[#0e0e15] p-4 font-mono text-sm">
                        <pre class="overflow-x-auto text-gray-800 dark:text-gray-300">ModuleName/
├── module.json          (Required)
├── config/
│   └── config.php
├── database/
│   ├── migrations/      (Required for Phase 1)
│   └── seeders/
├── app/
│   └── Providers/
├── resources/
│   └── views/
└── routes/
    └── web.php</pre>
                    </div>

                    <div class="mt-4 rounded-lg bg-gray-50 dark:bg-[#0e0e15] p-4">
                        <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Sample `module.json`</h4>
                        <pre class="overflow-x-auto text-sm text-gray-800 dark:text-gray-300"><code>{
  "name": "Product",
  "alias": "product",
  "version": "1.0.0",
  "description": "Product management module",
  "price": 0.00,
  "icon": null
}</code></pre>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('module_file');

        fileInput?.addEventListener('change', function (event) {
            const fileName = event.target.files[0]?.name;

            if (! fileName) {
                return;
            }

            const label = document.querySelector('label[for="module_file"] span');

            if (label) {
                label.textContent = fileName;
            }
        });
    </script>
</x-app-layout>
