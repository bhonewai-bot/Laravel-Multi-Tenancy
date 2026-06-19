<?php

namespace Modules\Product\Livewire;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Product\Jobs\ImportProductFromUrl;
use Modules\Product\Models\Product;
use Modules\Product\Services\Imports\ScrapingBeeClient;
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
            'url' => ['required', 'url'],
        ]);

        $scrapingBee = new ScrapingBeeClient;

        $html = $scrapingBee->fetchHtml($this->url);
        $crawler = new Crawler($html);

        dd([
            'og_title' => $crawler->filter('meta[property="og:title"]')->count(),
            'og_description' => $crawler->filter('meta[property="og:description"]')->count(),
            'og_image' => $crawler->filter('meta[property="og:image"]')->count(),
        ]);

        $name = $crawler->filter('meta[property="og:title"]')->count()
            ? trim((string) $crawler->filter('meta[property="og:title"]')->attr('content'))
            : null;

        $description = $crawler->filter('meta[property="og:description"]')->count()
            ? trim((string) $crawler->filter('meta[property="og:description"]')->attr('content'))
            : null;

        $imageUrl = $crawler->filter('meta[property="og:image"]')->count()
            ? trim((string) $crawler->filter('meta[property="og:image"]')->attr('content'))
            : null;

        $sku = preg_match('/i\.(\d+)\.(\d+)/', $this->url, $matches)
            ? $matches[1].'-'.$matches[2]
            : Str::uuid();

        $price = 1;
        if (preg_match('/฿\s*([\d,]+(?:\.\d+)?)/u', $crawler->text(''), $matches)) {
            $price = (float) str_replace(',', '', $matches[1]);
        }

        $imagePath = null;

        if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $imageResponse = Http::timeout(30)->get($imageUrl);

            if ($imageResponse->successful()) {
                $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
                $filename = 'products/'.Str::uuid().'.'.strtolower($extension);

                Storage::disk('public')->put($filename, $imageResponse->body());

                $imagePath = $filename;
            }
        }

        Product::create([
            'name' => $name,
            'sku' => $sku,
            'price' => $price,
            'quantity' => 1,
            'description' => $description,
            'image_path' => $imagePath,
        ]);

        /*  $scrapingBee = new ScrapingBeeClient();

         $html = $scrapingBee->fetchHtml($this->url);

         $crawler = new Crawler($html);

         $name = $crawler->filter('meta[property="og:title"]')->attr('content');
         $description = $crawler->filter('meta[property="og:description"]')->attr('content');
         $image = $crawler->filter('meta[property="og:image"]')->attr('content');


         dd($image); */

        /* $api = $this->shopeeApiPayload($this->url);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get($api['endpoint'], $api['query']); */

        /* dd($response->body());

        if (!$response->successful()) {
            throw new \RuntimeException("Shopee API request failed.");
        }

        $payload = $response->json();

        $item = $payload['data']['item'] ?? null;

        if (!$item) {
            throw new \RuntimeException('Product data not found.');
        } */

        // ImportProductFromUrl::dispatch(tenant()->id, $this->url);

        $this->reset('url');

        $this->showImportModal = false;

        session()->flash('success', 'Import started. The product will appear shortly.');
    }

    /* private function shopeeApiPayload(string $url): array
    {
        $parts = parse_url($url);

        parse_str($parts['query'] ?? '', $query);

        $extraParams = json_decode($query['extraParams'] ?? '{}', true);

        preg_match('/i\.(\d+)\.(\d+)/', $url, $matches);

        $shopId = $matches[1] ?? null;
        $itemId = $matches[2] ?? null;
        $displayModelId = $extraParams['display_model_id'] ?? null;
        $modelSelectionLogic = $extraParams['model_selection_logic'] ?? null;

        if (!$shopId || !$itemId || !$displayModelId) {
            throw new \RuntimeException('Invalid Shopee URL.');
        }

        return [
            'endpoint' => 'https://shopee.co.th/api/v4/pdp/get_pc',
            'query' => [
                'item_id' => $itemId,
                'shop_id' => $shopId,
                'display_model_id' => $displayModelId,
                'model_selection_logic' => $modelSelectionLogic ?? 0
            ]
        ];
    } */

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
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(5);

        return view('product::livewire.product-table', [
            'products' => $products,
        ]);
    }
}
