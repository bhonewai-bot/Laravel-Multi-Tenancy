<?php

namespace Modules\Product\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Product\Models\Product;

class ProductCreateForm extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $sku = '';
    public string $price = '';
    public string $quantity = '';
    public string $description = '';
    public $image = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        if ($this->image) {
            $validated['image'] = $this->image->store('products', 'public');
        }
        
        Product::query()->create($validated);

        session()->flash('success', 'Product created successfully.');

        return $this->redirectRoute('product.index');
    }

    public function render()
    {
        return view('product::livewire.product-create-form');
    }
}
