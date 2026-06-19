<?php

namespace Modules\Product\Services\Imports;

use Illuminate\Support\Facades\Http;

class ScrapingBeeClient
{
    public function fetchHtml(string $url, array $params = []): string
    {
        $apiKey = config('services.scrapingbee.api_key');

        if (! $apiKey) {
            throw new \Exception('Missing ScrapingBee API key');
        }

        $cleanUrl = $this->normalizeShopeeUrl($url);

        $response = Http::timeout(180)->get(
            config('services.scrapingbee.base_url'),
            array_merge([
                'api_key' => $apiKey,
                'url' => $cleanUrl,
                'render_js' => 'true',
                // 'premium_proxy' => 'true',
                'stealth_proxy' => 'true',
                'country_code' => 'th',
                'block_resources' => 'false',
                'wait_browser' => 'networkidle2',
                'wait' => 4000,
            ], $params)
        );

        dd([
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->body(),
        ]);

        if (! $response->successful()) {
            'ScrapingBee request failed: '.$response->status().' '.$response->body();
        }

        return $response->body();
    }

    private function normalizeShopeeUrl(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? 'https')
            .'://'
            .($parts['host'] ?? '')
            .($parts['path'] ?? '');
    }
}
