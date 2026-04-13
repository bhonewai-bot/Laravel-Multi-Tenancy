<?php

namespace Modules\Product\Services\Imports;

use InvalidArgumentException;
use Modules\Product\Services\Imports\Importers\LazadaProductImporter;
use Modules\Product\Services\Imports\Importers\ShopeeProductImporter;

class ProductImporterResolver
{
    public function __construct(
        private LazadaProductImporter $lazada,
        private ShopeeProductImporter $shopee,
    ) {}

    public function resolve(string $url)
    {
        if ($this->lazada->supports($url)) {
            return $this->lazada;
        }

        if ($this->shopee->supports($url)) {
            return $this->shopee;
        }

        throw new InvalidArgumentException("Unknown importer for URL: {$url}");
    }
}