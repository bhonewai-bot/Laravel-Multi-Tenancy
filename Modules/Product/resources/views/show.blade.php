@php
    $imageUrl = $product->image ? tenant_asset($product->image) : null;
@endphp

<x-product::layouts.master title="Product Details" subtitle="View product information.">
    <div class="grid gap-6 lg:grid-cols-[340px,1fr]">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="p-6">
                <div class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="px-6 text-center text-sm text-slate-500">
                            <p class="font-medium text-slate-700">No image uploaded</p>
                            <p class="mt-2">No product image is available for this record yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ $product->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Review product details, pricing, and inventory state.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a
                            href="{{ route('product.edit', $product) }}"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                        >
                            Edit
                        </a>
                        <a
                            href="{{ route('product.index') }}"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            Back
                        </a>
                        <form action="{{ route('product.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-medium text-rose-600 shadow-sm transition hover:bg-rose-50"
                            >
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 p-6 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">SKU</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $product->sku }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Price</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">${{ number_format((float) $product->price, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Quantity</p>
                    <p class="mt-2 text-base font-semibold {{ (int) $product->quantity <= 5 ? 'text-rose-600' : 'text-slate-900' }}">
                        {{ number_format((int) $product->quantity) }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Created</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $product->created_at?->format('M d, Y h:i A') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Description</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $product->description ?: 'No description' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Updated</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $product->updated_at?->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </section>
    </div>
</x-product::layouts.master>
