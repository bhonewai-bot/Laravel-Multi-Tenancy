@php
    $isEditing = isset($product);
    $imageUrl = $isEditing && ! empty($product->image)
        ? tenant_asset($product->image)
        : null;
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Product Imagery</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEditing ? 'Update product image' : 'Upload product image' }}</h2>
            <p class="mt-2 text-sm text-slate-600">Choose a clear product image to make the catalog easier to scan.</p>
        </div>

        <div class="p-6">
            <div class="space-y-4">
                <label for="image" class="flex min-h-56 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-blue-400 hover:bg-blue-50/40">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-white text-xl text-blue-600 shadow-sm">↑</span>
                    <p class="mt-4 text-base font-medium text-slate-900">Click to upload a product image</p>
                    <p class="mt-2 text-sm text-slate-500">PNG, JPG, JPEG or WebP up to 2MB</p>
                </label>

                <input id="image" name="image" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp">

                @if ($isEditing && ! empty($product->image))
                    <p class="text-sm text-slate-500">Leave the image field empty to keep the current image.</p>
                @endif

                @error('image')
                    <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                @enderror

                @if ($imageUrl)
                    <div>
                        <p class="mb-3 text-sm font-medium text-slate-700">Current Image</p>
                        <img
                            src="{{ $imageUrl }}"
                            alt="{{ $product->name }}"
                            class="h-56 w-56 rounded-2xl border border-slate-200 object-cover shadow-sm"
                        >
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Basic Information</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEditing ? 'Edit product details' : 'Add new product' }}</h2>
            <p class="mt-2 text-sm text-slate-600">Fill in the core product information for inventory and pricing.</p>
        </div>

        <div class="p-6">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Product Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $product->name ?? '') }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        placeholder="e.g. Product Test"
                        required
                    >
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sku" class="mb-2 block text-sm font-medium text-slate-700">SKU</label>
                    <input
                        id="sku"
                        name="sku"
                        type="text"
                        value="{{ old('sku', $product->sku ?? '') }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        placeholder="SKU-001"
                        required
                    >
                    @error('sku')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="quantity" class="mb-2 block text-sm font-medium text-slate-700">Quantity</label>
                    <input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="0"
                        value="{{ old('quantity', $product->quantity ?? '') }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        placeholder="0"
                        required
                    >
                    @error('quantity')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="price" class="mb-2 block text-sm font-medium text-slate-700">Unit Price</label>
                    <input
                        id="price"
                        name="price"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('price', $product->price ?? '') }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        placeholder="12.00"
                        required
                    >
                    @error('price')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        placeholder="Describe the product specifications and use case..."
                    >{{ old('description', $product->description ?? '') }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{ route('product.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Cancel
            </a>
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
            >
                {{ $submitLabel }}
            </button>
        </div>
    </section>
</div>
