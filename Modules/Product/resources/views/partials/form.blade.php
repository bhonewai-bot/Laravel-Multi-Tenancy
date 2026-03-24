@php
    $isEditing = isset($product);
    $imageUrl = $isEditing && ! empty($product->image)
        ? tenant_asset($product->image)
        : null;
@endphp

<div class="form-shell">
    <div class="form-section">
        <div class="form-section__header">
            <p class="form-section__eyebrow">Product Imagery</p>
            <h2 class="form-section__title">{{ $isEditing ? 'Update product image' : 'Upload product image' }}</h2>
            <p class="form-section__copy">Choose a clear product image to make the catalog easier to scan.</p>
        </div>

        <div class="form-section__body">
            <div class="upload-panel">
                <label for="image" class="upload-dropzone">
                    <div>
                        <div class="upload-dropzone__icon">↑</div>
                        <p class="upload-dropzone__title">Click to upload or drag and drop</p>
                        <p class="upload-dropzone__hint">PNG, JPG, JPEG or WebP up to 2MB</p>
                    </div>
                </label>

                <input id="image" name="image" type="file" class="file-input" accept=".jpg,.jpeg,.png,.webp" style="display: none;">

                @if ($isEditing && ! empty($product->image))
                    <div class="help">Leave the image field empty to keep the current image.</div>
                @endif

                @error('image')
                    <div class="error">{{ $message }}</div>
                @enderror

                @if ($imageUrl)
                    <div>
                        <div class="label" style="margin-bottom: 12px;">Current Image</div>
                        <img
                            src="{{ $imageUrl }}"
                            alt="{{ $product->name }}"
                            style="max-width: 260px; border-radius: 12px; border: 1px solid var(--border);"
                        >
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section__header">
            <p class="form-section__eyebrow">Basic Information</p>
            <h2 class="form-section__title">{{ $isEditing ? 'Edit product details' : 'Add new product' }}</h2>
            <p class="form-section__copy">Fill in the core product information for inventory and pricing.</p>
        </div>

        <div class="form-section__body">
            <div class="form-grid">
                <div class="field-full">
                    <label for="name" class="label">Product Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $product->name ?? '') }}"
                        class="input"
                        placeholder="e.g. Product Test"
                        required
                    >
                    @error('name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="sku" class="label">SKU</label>
                    <input
                        id="sku"
                        name="sku"
                        type="text"
                        value="{{ old('sku', $product->sku ?? '') }}"
                        class="input"
                        placeholder="SKU-001"
                        required
                    >
                    @error('sku')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="quantity" class="label">Quantity</label>
                    <input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="0"
                        value="{{ old('quantity', $product->quantity ?? '') }}"
                        class="input"
                        placeholder="0"
                        required
                    >
                    @error('quantity')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field-full">
                    <label for="price" class="label">Unit Price</label>
                    <input
                        id="price"
                        name="price"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('price', $product->price ?? '') }}"
                        class="input"
                        placeholder="12.00"
                        required
                    >
                    @error('price')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field-full">
                    <label for="description" class="label">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        class="textarea"
                        placeholder="Describe the product specifications and use case..."
                    >{{ old('description', $product->description ?? '') }}</textarea>
                    @error('description')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="form-actions-bar">
            <a href="{{ route('product.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn">{{ $submitLabel }}</button>
        </div>
    </div>
</div>
