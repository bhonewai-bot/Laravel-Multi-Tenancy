<x-app-layout>
    <div class="animate-fade-up">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Upload Module</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new module to the platform</p>
            </div>
            <a href="{{ route('modules.index') }}">
                <x-secondary-button type="button">Back to Modules</x-secondary-button>
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('error'))
            <div class="mb-6">
                <x-alert variant="error">{{ session('error') }}</x-alert>
            </div>
        @endif

            <x-card>
                <form method="POST" action="{{ route('modules.store') }}" enctype="multipart/form-data" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf

                    <div class="mb-6">
                        <x-input-label for="module_file" :value="__('Module ZIP File')" />
                        <div class="mt-1 flex justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-[#262632] px-6 pb-6 pt-5 transition-colors hover:border-blue-400 dark:hover:border-blue-500">
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
                                    <label for="module_file" class="relative cursor-pointer rounded-lg bg-white dark:bg-[#101016] font-medium text-blue-600 dark:text-blue-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-2 hover:text-blue-500">
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
                        <a href="{{ route('modules.index') }}">
                            <x-secondary-button type="button">
                                Cancel
                            </x-secondary-button>
                        </a>
                        <x-primary-button x-bind:disabled="submitting" type="submit">
                            <span x-show="!submitting">Upload Module</span>
                            <span x-show="submitting" x-cloak class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                Uploading...
                            </span>
                        </x-primary-button>
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
