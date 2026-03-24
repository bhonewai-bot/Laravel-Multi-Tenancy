<x-product::layouts.master title="Create Product" subtitle="Add a new product to the catalog.">
    <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('product::partials.form', ['submitLabel' => 'Save Product'])
    </form>
</x-product::layouts.master>
