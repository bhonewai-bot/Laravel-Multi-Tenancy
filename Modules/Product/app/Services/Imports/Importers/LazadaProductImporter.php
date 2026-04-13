<?php

namespace Modules\Product\Services\Imports\Importers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class LazadaProductImporter
{
    public function supports(string $url): bool
    {
        return str_contains(parse_url($url, PHP_URL_HOST) ?? '', 'lazada');
    }   

    public function import(string $url)
    {
        $response =  Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ])->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to fetch Lazada page.');
        }

        $crawler = new Crawler($response->body());
        $jsonLdTag = $crawler->filter('script[type="application/ld+json"]');

        if ($jsonLdTag->count() === 0) {
            throw new \RuntimeException('Lazada product data not found.');
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

        return (object) [
            'name' => $data['name'] ?? '',
            'sku' => $data['sku'] ?? '',
            'price' => $data['offers']['price'] ?? 1,
            'quantity' => $data['offers']['quantity'] ?? 1,
            'description' => $data['description'] ?? null,
            'image' => $imagePath
        ];
    }
}