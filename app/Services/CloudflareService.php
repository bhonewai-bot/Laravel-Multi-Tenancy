<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Encapsulates Cloudflare Custom Hostnames API calls used by tenant domain onboarding.
 *
 * Keeping the integration behind a service centralizes configuration validation, retry
 * behavior, and response mapping so controllers can focus on tenant business rules.
 */
class CloudflareService
{
    /**
     * Create a Cloudflare custom hostname for a tenant domain.
     *
     * Side effects:
     * - Performs an outbound HTTP request to Cloudflare.
     */
    public function createHostname(string $hostname): array
    {
        $this->ensureConfigured();

        // Retry is intentionally bounded because repeated hostname creation attempts can produce duplicate operational noise.
        try {
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
                    ],
                ]);
        } catch (RequestException $e) {
            $json = $e->response->json() ?? [];

            throw new RuntimeException($this->extractError($json, $e->getMessage()));
        }

        $json = $response->json() ?? [];
        if (! $response->successful() || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->extractError($json, $response->body()));
        }

        return $json;
    }

    /**
     * List custom hostnames, optionally filtered by hostname.
     *
     * Side effects:
     * - Performs an outbound HTTP request to Cloudflare.
     *
     * @return array<int, array>
     */
    public function listHostnames(?string $hostname = null): array
    {
        $this->ensureConfigured();

        $query = $hostname ? ['hostname' => $hostname] : [];

        $response = Http::timeout((int) config('cloudflare.api.timeout', 15))
            ->retry(
                (int) config('cloudflare.api.retry_times', 2),
                (int) config('cloudflare.api.retry_sleep_ms', 200)
            )
            ->withToken((string) config('cloudflare.api.token'))
            ->get($this->endpoint('/custom_hostnames'), $query);

        $json = $response->json() ?? [];

        if (! $response->successful() || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->extractError($json, $response->body()));
        }

        return $json['result'] ?? [];
    }

    /**
     * Fetch the latest Cloudflare status for an existing custom hostname.
     *
     * Side effects:
     * - Performs an outbound HTTP request to Cloudflare.
     */
    public function getHostname(string $cloudflareId): array
    {
        $this->ensureConfigured();

        $response = Http::timeout((int) config('cloudflare.api.timeout', 15))
            ->retry(
                (int) config('cloudflare.api.retry_times', 2),
                (int) config('cloudflare.api.retry_sleep_ms', 200)
            )
            ->withToken((string) config('cloudflare.api.token'))
            ->get($this->endpoint('/custom_hostnames/'.$cloudflareId));

        $json = $response->json() ?? [];

        if (! $response->successful() || ! ($json['success'] ?? false)) {
            throw new RuntimeException($this->extractError($json, $response->body()));
        }

        return $json;
    }

    /**
     * Normalize Cloudflare's response into the fields persisted on the domain model.
     */
    public function mapStatuses(array $result): array
    {
        $hostnameStatus = data_get($result, 'result.status');
        $sslStatus = data_get($result, 'result.ssl.status');

        // Validation errors are flattened so the UI and logs surface the actionable Cloudflare reason.
        $errors = collect(data_get($result, 'result.ssl.validation_errors', []))
            ->pluck('message')
            ->filter()
            ->implode(' | ');

        return [
            'cf_hostname_id' => data_get($result, 'result.id'),
            'cf_hostname_status' => $hostnameStatus,
            'cf_ssl_status' => $sslStatus,
            'cf_error' => $errors !== '' ? $errors : null,
            'cf_payload' => $result,
        ];
    }

    /**
     * Backward-compatible alias while call sites are migrated to the pluralized method name.
     */
    public function mapStatus(array $result): array
    {
        return $this->mapStatuses($result);
    }

    /**
     * Fail fast when Cloudflare integration is enabled but incomplete.
     */
    private function ensureConfigured(): void
    {
        if (! config('cloudflare.enabled')) {
            throw new RuntimeException('Cloudflare integration is disabled.');
        }

        $missing = [];

        if (trim((string) config('cloudflare.api.token')) === '') {
            $missing[] = 'CLOUDFLARE_API_TOKEN';
        }

        if (trim((string) config('cloudflare.api.zone_id')) === '') {
            $missing[] = 'CLOUDFLARE_ZONE_ID';
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Cloudflare is enabled but missing configuration: '.implode(', ', $missing).'.'
            );
        }
    }

    /**
     * Build a zone-scoped Cloudflare API endpoint.
     */
    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('cloudflare.api.base_url'), '/');
        $zoneId = (string) config('cloudflare.api.zone_id');

        return "{$baseUrl}/zones/{$zoneId}{$path}";
    }

    /**
     * Collapse Cloudflare's error payload into a single message suitable for logs and UI flashes.
     */
    private function extractError(array $json, string $fallback): string
    {
        $msg = collect($json['errors'] ?? [])->pluck('message')->implode(' | ');

        return $msg ?: $fallback;
    }
}
