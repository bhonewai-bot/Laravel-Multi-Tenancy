<?php

namespace Modules\Product\Livewire;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Product\Models\Product;

class ProductTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public ?int $confirmingDeleteId = null;
    public ?string $confirmingDeleteName = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function confirmDelete(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->confirmingDeleteId = $product->id;
        $this->confirmingDeleteName = $product->name;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
        $this->confirmingDeleteName = null;
    }

    public function deleteProduct(): void
    {
        $product = Product::query()->findOrFail($this->confirmingDeleteId);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        $this->cancelDelete();

        session()->flash('success', 'Product deleted successfully.');
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->search !== '', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%');
                })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(5);

        return view('product::livewire.product-table', [
            'products' => $products,
        ]);
    }
}
