<?php

namespace Modules\Product\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;
use Modules\Product\Services\Imports\ProductImportService;
use Stancl\Tenancy\Facades\Tenancy;
use Symfony\Component\DomCrawler\Crawler;

class ImportProductFromUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public string $tenantId,
        public string $url,
    ) {}

    public function handle(ProductImportService $service): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            return;
        }

        Tenancy::initialize($tenant);

        try {
            $service->import($this->url);
            /* $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/237.84.2.178 Safari/537.36'
            ])->get($this->url);

            if (!$response->successful()) {
                return;
            }

            $crawler = new Crawler($response->body());

            $jsonLdTag = $crawler->filter('script[type="application/ld+json"]');
            if ($jsonLdTag->count() === 0) {
                return;
            }

            $data = json_decode($jsonLdTag->first()->text(), true);

            $imagePath = null;
            $imageUrl = $data['image'][0] ?? null;

            if (is_string($imageUrl) && str_starts_with($imageUrl, '//')) {
                $imageUrl = 'https:' . $imageUrl;
            }

            if (is_string($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $imageResponse = Http::timeout(20)->get($imageUrl);

                if ($imageResponse->successful()) {
                    $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = 'products/' . Str::uuid() . '.' . strtolower($extension);

                    Storage::disk('public')->put($filename, $imageResponse->body());

                    $imagePath = $filename;
                }
            }

            Product::create([
                'name' => $data['name'] ?? '',
                'sku' => $data['sku'] ?? '',
                'price' => $data['price'] ?? 1,
                'quantity' => $data['quantity'] ?? 1,
                'description' => $data['description'] ?? '',
                'image' => $imagePath,
            ]); */
        } finally {
            Tenancy::end();
        }
    }
}