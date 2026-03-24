<x-product::layouts.master title="Product Inventory" subtitle="Manage your catalog, stock levels, and product images.">
    @php
        $inventoryCount = (int) $products->sum('quantity');
        $catalogValue = $products->sum(fn ($product) => (float) $product->price * (int) $product->quantity);
        $lowInventoryCount = (int) $products->filter(fn ($product) => (int) $product->quantity > 0 && (int) $product->quantity <= 10)->count();
    @endphp

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card__label">Total Products</div>
            <div class="stat-card__value">{{ $products->total() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">In Stock</div>
            <div class="stat-card__value">{{ number_format($inventoryCount) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">Low Inventory</div>
            <div class="stat-card__value" style="color: {{ $lowInventoryCount > 0 ? 'var(--danger)' : 'var(--text)' }};">
                {{ $lowInventoryCount }}
            </div>
        </div>
        <div class="stat-card stat-card--primary">
            <div class="stat-card__label">Inventory Value</div>
            <div class="stat-card__value">${{ number_format($catalogValue, 2) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="catalog-header">
            <div>
                <h2 class="catalog-title">Catalog Management</h2>
                <p class="catalog-subtitle">Review, update, and maintain your tenant products.</p>
            </div>

            <div class="actions">
                <a href="{{ route('product.create') }}" class="btn">New Product</a>
            </div>
        </div>

        <div class="card-body table-wrap" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th class="center">Image</th>
                        <th class="center">Name</th>
                        <th class="center">SKU</th>
                        <th class="center">Price</th>
                        <th class="center">Quantity</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            $imageUrl = $product->image ? tenant_asset($product->image) : null;
                        @endphp
                        <tr>
                            <td class="center">
                                @if ($imageUrl)
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="{{ $product->name }}"
                                        class="thumb"
                                        style="margin: 0 auto;"
                                    >
                                @else
                                    <div class="thumb-placeholder" style="margin: 0 auto;">No image</div>
                                @endif
                            </td>
                            <td class="center">
                                <p class="item-title">{{ $product->name }}</p>
                            </td>
                            <td class="center">
                                <span class="sku-badge">{{ $product->sku }}</span>
                            </td>
                            <td class="center">${{ number_format((float) $product->price, 2) }}</td>
                            <td class="center" style="color: {{ (int) $product->quantity <= 10 ? 'var(--danger)' : 'var(--text)' }}; font-weight: 600;">
                                {{ number_format((int) $product->quantity) }}
                            </td>
                            <td class="center">
                                <div class="actions" style="justify-content: center;">
                                    <a href="{{ route('product.show', $product) }}" class="btn-secondary">View</a>
                                    <a href="{{ route('product.edit', $product) }}" class="btn-secondary">Edit</a>
                                    <form action="{{ route('product.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div style="padding: 24px 18px; text-align: center;">
                                    <p class="item-title">No products found</p>
                                    <p class="item-text">Create your first product to start building the catalog.</p>
                                    <div class="actions" style="margin-top: 16px; justify-content: center;">
                                        <a href="{{ route('product.create') }}" class="btn">Create Product</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination">
        {{ $products->links() }}
    </div>
</x-product::layouts.master>
