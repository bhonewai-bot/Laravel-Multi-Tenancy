<?php

namespace Modules\Product\Livewire;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Product\Models\Product;
use Symfony\Component\DomCrawler\Crawler;

class ProductTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public string $url = '';

    public bool $showImportModal = false;
    public ?int $confirmingDeleteId = null;
    public ?string $confirmingDeleteName = null;

    public function import(): void
    {
        

        $this->validate([
            'url' => ['required', 'url']
        ]);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ])->get($this->url);

        if ($response->successful()) {
            $html = $response->body();

            $crawler = new Crawler($html);

            $jsonLdTag = $crawler->filter('script[type="application/ld+json"]');
            
            if ($jsonLdTag->count() > 0) {
                $jsonContent = $jsonLdTag->first()->text();

                $data = json_decode($jsonContent, true);

                $imagePath = null;
                $imageUrl = $data['image'][0] ?? null;

                if (str_starts_with($imageUrl, '//')) {
                    $imageUrl = 'https:' . $imageUrl;
                }

                if (is_string($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageResponse = Http::timeout(20)->get($imageUrl);
                }

                if ($imageResponse->successful()) {
                    $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
                    $extension = $extension ?: 'jpg';

                    $filename = 'products/' . Str::uuid() . '.' . strtolower($extension);

                    Storage::disk('public')->put($filename, $imageResponse->body());

                    $imagePath = $filename;
                }
            
                Product::create([
                    'name' => $data['name'] ?? '',
                    'sku' => $data['sku'] ?? '',
                    'price' => $data['price'] ?? 1,
                    'quantity' => $data['quantity'] ?? 1,
                    'description' => $data['description'] ?? '',
                    'image' => $imagePath
                ]);
            }

        }
    }

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
