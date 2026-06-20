<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('CLOUDFLARE_ENABLED', false),
    'async_polling' => (bool) env('CLOUDFLARE_ASYNC_POLLING', false),

    'api' => [
        'base_url' => env('CLOUDFLARE_API_BASE_URL', 'https://api.cloudflare.com/client/v4'),
        'token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'zone_domain' => env('CLOUDFLARE_ZONE_DOMAIN'),
        'timeout' => (int) env('CLOUDFLARE_TIMEOUT', 15),
        'retry_times' => (int) env('CLOUDFLARE_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('CLOUDFLARE_RETRY_SLEEP_MS', 200),
    ],

    'fallback_origin' => env('CLOUDFLARE_FALLBACK_ORIGIN', env('TENANCY_CENTRAL_DOMAIN')),

    'validation_method' => env('CLOUDFLARE_VALIDATION_METHOD', 'http'),
];
