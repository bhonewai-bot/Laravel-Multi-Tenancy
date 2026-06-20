<?php

namespace Modules\Product\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Product\Services\Imports\ProductImportService;
use Stancl\Tenancy\Facades\Tenancy;

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

        if (! $tenant) {
            return;
        }

        Tenancy::initialize($tenant);

        try {
            $service->import($this->url);
        } finally {
            Tenancy::end();
        }
    }
}
