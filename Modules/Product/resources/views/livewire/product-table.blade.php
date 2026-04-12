<div wire:key="product-table-component" class="space-y-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex w-full flex-col gap-3 sm:max-w-2xl sm:flex-row sm:items-center">
            <div class="w-full max-w-md">
                <label for="product-search" class="sr-only">Search products</label>
                <input
                    id="product-search"
                    type="text"
                    wire:model.live="search"
                    placeholder="Search by product name or SKU..."
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                />
            </div>

            <button
                type="button"
                wire:click="$set('showImportModal', true)"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Import
            </button>
        </div>
        <p class="text-sm text-slate-500">
            {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            <button wire:click="sortBy('name')" type="button" class="inline-flex items-center gap-1 hover:text-slate-700">
                                <span>Name</span>
                                @if ($sortField === 'name')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            <button wire:click="sortBy('sku')" type="button" class="inline-flex items-center gap-1 hover:text-slate-700">
                                <span>SKU</span>
                                @if ($sortField === 'sku')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            <button wire:click="sortBy('price')" type="button" class="inline-flex items-center gap-1 hover:text-slate-700">
                                <span>Price</span>
                                @if ($sortField === 'price')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            <button wire:click="sortBy('quantity')" type="button" class="inline-flex items-center gap-1 hover:text-slate-700">
                                <span>Quantity</span>
                                @if ($sortField === 'quantity')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($products as $product)
                        <tr wire:key="product-row-{{ $product->id }}" class="hover:bg-slate-50/80">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($product->image)
                                        <img
                                            src="{{ tenant_asset($product->image) }}"
                                            alt="{{ $product->name }}"
                                            class="h-12 w-12 rounded-xl border border-slate-200 object-cover"
                                        >
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-xs font-medium text-slate-400">
                                            N/A
                                        </div>
                                    @endif

                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                                        <!-- <p class="text-sm text-slate-500">
                                            {{ $product->description ?: 'No description provided.' }}
                                        </p> -->
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    {{ $product->sku }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm font-medium text-slate-800">
                                ${{ number_format((float) $product->price, 2) }}
                            </td>
                            <td class="px-4 py-4 text-sm font-medium {{ (int) $product->quantity <= 5 ? 'text-rose-600' : 'text-slate-700' }}">
                                {{ number_format((int) $product->quantity) }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('product.show', $product) }}"
                                        class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        View
                                    </a>
                                    <a
                                        href="{{ route('product.edit', $product) }}"
                                        class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="confirmDelete({{ $product->id }})"
                                        class="inline-flex items-center rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                No products found for the current search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $products->links() }}
    </div>

    <x-product::ui.modal :show="$showImportModal" maxWidth="lg">
        <form wire:submit.prevent="import" class="space-y-4 p-6">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Import Product</h3>
                <p class="mt-1 text-sm text-slate-600">Paste a product URL to start the import.</p>
            </div>

            <div>
                <label for="product-import-url" class="sr-only">Product URL</label>
                <input
                    id="product-import-url"
                    type="url"
                    wire:model="url"
                    placeholder="https://example.com/product"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                >
                @error('url') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    wire:click="$set('showImportModal', false)"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                >
                    Import
                </button>
            </div>
        </form>
    </x-product::ui.modal>

    <x-product::ui.modal :show="(bool) $confirmingDeleteId" maxWidth="md">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900">Delete Product</h3>

            <p class="mt-3 text-sm leading-6 text-slate-600">
                Are you sure you want to delete
                <span class="font-semibold text-slate-900">{{ $confirmingDeleteName }}</span>?
                This action cannot be undone.
            </p>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    wire:click="cancelDelete"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    wire:click="deleteProduct"
                    class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-rose-700"
                >
                    Delete Product
                </button>
            </div>
        </div>
    </x-product::ui.modal>
</div>
