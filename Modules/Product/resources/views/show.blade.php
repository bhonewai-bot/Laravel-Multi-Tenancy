@php
    $imageUrl = $product->image ? tenant_asset($product->image) : null;
@endphp

<x-product::layouts.master title="Product Details" subtitle="View product information.">
    <div class="detail-grid">
        <div class="card">
            <div class="card-body">
            <div class="preview-box">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $product->name }}">
                @else
                    <div class="help" style="padding: 20px; text-align: center;">
                        <strong>No image uploaded</strong>
                        <p style="margin: 8px 0 0;">No product image available.</p>
                    </div>
                @endif
            </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
            <div class="actions" style="margin-bottom: 18px;">
                <a href="{{ route('product.edit', $product) }}" class="btn">Edit</a>
                <a href="{{ route('product.index') }}" class="btn-secondary">Back</a>
                <form action="{{ route('product.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            </div>

            <div class="detail-list">
                <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value">{{ $product->name }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">SKU</div>
                    <div class="detail-value">{{ $product->sku }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Price</div>
                    <div class="detail-value">${{ number_format((float) $product->price, 2) }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Quantity</div>
                    <div class="detail-value">{{ number_format((int) $product->quantity) }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Description</div>
                    <div class="detail-value" style="font-weight: 500;">{{ $product->description ?: 'No description' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Created</div>
                    <div class="detail-value">{{ $product->created_at?->format('M d, Y h:i A') }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Updated</div>
                    <div class="detail-value">{{ $product->updated_at?->format('M d, Y h:i A') }}</div>
                </div>
            </div>
            </div>
        </div>
    </div>
</x-product::layouts.master>
