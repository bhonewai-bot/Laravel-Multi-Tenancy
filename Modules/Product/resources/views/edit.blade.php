<x-product::layouts.master title="Edit Product" subtitle="Update product details.">
    @livewire(\Modules\Product\Livewire\ProductEditForm::class, ['product' => $product])
</x-product::layouts.master>
