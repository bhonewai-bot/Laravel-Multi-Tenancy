<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudflareService
{
    public function createHostname(string $hostname): array
    {
        $response = Http::timeout((int) config('cloudflare.api.timeout', 15))
            ->retry(
                (int) config('cloudflare.api.retry_times', 2),
                (int) config('cloudflare.api.retry_sleep_ms', 200)
            )
            ->withToken((string) config('cloudflare.api.token'))
            ->post($this->endpoint('/custom_hostnames'), [
                'hostname' => $hostname,
                'ssl' => [
                    'method' => (string) config('cloudflare.validation_method', 'http'),
                    'type' => 'dv',
                    'settings' => ['min_tls_version' => '1.2'],
                ]
            ]);

        $json = $response->json() ?? [];
        if (! $response->successful() || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->extractError($json, $response->body()));
        }

        return $json;
    }

    public function getHostname(string $cloudflareId): array
    {
        $response = Http::timeout((int) config('cloudflare.api.timeout', 15))
            ->retry(
                (int) config('cloudflare.api.retry_times', 2),
                (int) config('cloudflare.api.retry_sleep_ms', 200)
            )
            ->withToken((string) config('cloudflare.api.token'))
            ->get($this->endpoint('/custom_hostnames/' . $cloudflareId));

        $json = $response->json() ?? [];

        if (! $response->successful() || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->extractError($json, $response->body()));
        }

        return $json;
    }

    public function mapStatus(array $result): array
    {
        $hostnameStatus = data_get($result, 'result.status');
        $sslStatus = data_get($result, 'result.ssl.status');

        $errors = collect(data_get($result, 'result.ssl.validation_errors', []))
            ->pluck('message')
            ->filter()
            ->implode(' | ');

        return [
            'cf_hostname_id' => data_get($result, 'result.id'),
            'cf_hostname_status' => $hostnameStatus,
            'cf_ssl_status' => $sslStatus,
            'cf_error' => $errors !== '' ? $errors : null,
            'cf_payload' => $result
        ];
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('cloudflare.api.base_url'), '/');
        $zoneId = (string) config('cloudflare.api.zone_id');

        return "{$baseUrl}/zones/{$zoneId}{$path}";
    }

    private function extractError(array $json, string $fallback): string
    {
        $msg = collect($json['errors'] ?? [])->pluck('message')->implode(' | ');
        return $msg ?: $fallback;
    }
}
