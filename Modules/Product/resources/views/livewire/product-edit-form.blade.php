<form wire:submit="save" class="space-y-6">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Basic Information</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Update product</h2>
            <p class="mt-2 text-sm text-slate-600">Start with the core product details first. We can add image upload next.</p>
        </div>

        <div class="space-y-4 p-6">
            <div>
                <input
                    id="image"
                    type="file"
                    wire:model="image"
                    accept=".jpg,.jpeg,.png,.webp"
                    class="sr-only"
                >

                <label
                    for="image"
                    class="flex min-h-56 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-blue-400 hover:bg-blue-50/40"
                >
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-white text-xl text-blue-600 shadow-sm">
                        ↑
                    </span>

                    <p class="mt-4 text-base font-medium text-slate-900">
                        Click to upload a product image
                    </p>

                    <p class="mt-2 text-sm text-slate-500">
                        PNG, JPG, JPEG or WebP up to 2MB
                    </p>

                    @if ($image)
                        <p class="mt-3 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 shadow-sm">
                            Selected: {{ $image->getClientOriginalName() }}
                        </p>
                    @endif
                </label>

                <div wire:loading wire:target="image" class="mt-3 text-sm text-slate-500">
                    Uploading image...
                </div>

                @error('image')
                    <p class="mt-3 text-sm font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            @if ($product->image)
                <div>
                    <p class="mb-3 text-sm font-medium text-slate-700">Current Image</p>
                    <img
                        src="{{ $product->image }}"
                        alt="{{ $product->name }}"
                        class="h-64 w-64 rounded-2xl border border-slate-200 object-cover shadow-sm"
                    >
                </div>
            @endif
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">Product Name</label>
                <input wire:model="name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                @error('name') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">SKU</label>
                <input wire:model="sku" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                @error('sku') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Quantity</label>
                <input wire:model="quantity" type="number" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                @error('quantity') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">Price</label>
                <input wire:model="price" type="number" step="0.01" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                @error('price') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                <textarea wire:model="description" rows="5" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></textarea>
                @error('description') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('product.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                Save Product
            </button>
        </div>
    </section>
</form>