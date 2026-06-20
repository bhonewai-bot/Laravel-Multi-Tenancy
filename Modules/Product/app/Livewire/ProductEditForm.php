<?php

namespace Modules\Product\Livewire;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Product\Models\Product;

class ProductEditForm extends Component
{
    use WithFileUploads;

    public Product $product;

    public string $name = '';
    public string $sku = '';
    public string $price = '';
    public string $quantity = '';
    public string $description = '';
    public $image = null;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->name = (string) $product->name;
        $this->sku = (string) $product->sku;
        $this->price = (string) $product->price;
        $this->quantity = (string) $product->quantity;
        $this->description = (string) $product->description ?? '';
    }

    protected function rules(): array
    {
        return[
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('products', 'sku')->ignore($this->product->id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        unset($validated['image']);

        if ($this->image) {
            if ($this->product->image) {
                Storage::disk('public')->delete($this->product->image);
            }

            $validated['image'] = $this->image->store('products', 'public');
        }

        $this->product->update($validated);

        session()->flash('success', 'Product updated successfully.');

        return $this->redirectRoute('product.index');
    }

    public function render()
    {
        return view('product::livewire.product-edit-form');
    }
}
