<?php

namespace Modules\Product\Services\Imports\Importers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Product\Services\Imports\DTOs\ProductDto;
use Modules\Product\Services\Imports\Interfaces\IProductImporter;
use Modules\Product\Services\Imports\ScrapingBeeClient;
use Symfony\Component\DomCrawler\Crawler;

class ShopeeProductImporter implements IProductImporter
{
    public function __construct(
        private ScrapingBeeClient $scrapingBee
    ) {}

    public function supports(string $url): bool
    {
        return str_contains(parse_url($url, PHP_URL_HOST) ?? '', 'shopee');
    }

    public function import(string $url): ProductDto
    {
        $html = $this->scrapingBee->fetchHtml($url);

        $crawler = new Crawler($html);

        $name = $crawler->filter('meta[property="og:title"')->attr('content');
        $description = $crawler->filter('meta[property="og:description"')->attr('content');
        $imageUrl = $crawler->filter('meta[property="og:image"')->attr('content');

        $imagePath = null;

        if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $imageResponse = Http::timeout(30)->get($imageUrl);

            if ($imageResponse->successful()) {
                $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
                $filename = 'products/'.Str::uuid().'.'.strtolower($extension);

                Storage::disk('public')->put($filename, $imageResponse->body());

                $imagePath = $filename;
            }

            $price = 1;

            if (preg_match('/฿\\s*([\\d,]+(?:\\.\\d+)?)/u', $crawler->text(''), $matches)) {
                $price = (float) str_replace(',', '', $matches[1]);
            }

            $sku = preg_match('/i\\.(\\d+)\\.(\\d+)/', $url, $matches)
                ? $matches[1].'-'.$matches[2]
                : (string) Str::uuid();
        }

        return new ProductDto(
            name: $name,
            sku: $sku,
            price: $price,
            quantity: 1,
            description: $description,
            image: $imagePath
        );
    }
}
