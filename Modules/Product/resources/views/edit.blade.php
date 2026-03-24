<x-product::layouts.master title="Edit Product" subtitle="Update product details.">
    <form action="{{ route('product.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('product::partials.form', ['product' => $product, 'submitLabel' => 'Update Product'])
    </form>
</x-product::layouts.master>
