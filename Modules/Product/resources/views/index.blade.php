<x-product::layouts.master title="Product Inventory" subtitle="Manage your catalog, stock levels, and product images.">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total Products</p>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($stats['totalProducts']) }}</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">In Stock</p>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($stats['inventoryCount']) }}</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Low Inventory</p>
            <p class="mt-4 text-3xl font-semibold tracking-tight {{ $stats['lowInventoryCount'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                {{ number_format($stats['lowInventoryCount']) }}
            </p>
        </div>

        <div class="rounded-3xl bg-blue-600 p-6 text-white shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-100">Inventory Value</p>
            <p class="mt-4 text-3xl font-semibold tracking-tight">${{ number_format($stats['catalogValue'], 2) }}</p>
        </div>
    </div>

    <section class="mt-6 rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Catalog Management</h2>
                <p class="mt-1 text-sm text-slate-600">Search, sort, and review products for this tenant workspace.</p>
            </div>

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <a
                        href="{{ route('product.create') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                    >
                        New Product
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            @livewire(\Modules\Product\Livewire\ProductTable::class)
        </div>
    </section>
</x-product::layouts.master>
