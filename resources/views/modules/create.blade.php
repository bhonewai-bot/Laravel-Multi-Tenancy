<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Module</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($errors->any())
                        <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700 border border-red-200">
                            <p class="font-semibold">Please fix the following errors:</p>
                            <ul class="mt-2 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('modules.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" class="mt-1 block w-full" type="text" name="name"
                                    :value="old('name')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="slug" :value="__('Slug')" />
                                <x-text-input id="slug" class="mt-1 block w-full" type="text" name="slug"
                                    :value="old('slug')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                            </div>

                            <div>
                                <x-input-label for="version" :value="__('Version')" />
                                <x-text-input id="version" class="mt-1 block w-full" type="text" name="version"
                                    :value="old('version', '1.0.0')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('version')" />
                            </div>

                            <div>
                                <x-input-label for="icon_path" :value="__('Icon Path')" />
                                <x-text-input id="icon_path" class="mt-1 block w-full" type="text" name="icon_path"
                                    :value="old('icon_path')" />
                                <x-input-error class="mt-2" :messages="$errors->get('icon_path')" />
                            </div>

                            <div>
                                <x-input-label for="price" :value="__('Price')" />
                                <x-text-input id="price" class="mt-1 block w-full" type="number" name="price"
                                    step="0.01" min="0" :value="old('price', '0')" />
                                <x-input-error class="mt-2" :messages="$errors->get('price')" />
                            </div>

                            <div>
                                <x-input-label for="is_active" :value="__('Active')" />
                                <select id="is_active" name="is_active"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>No</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('modules.index') }}"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">
                                Cancel
                            </a>
                            <x-primary-button>
                                Save Module
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
