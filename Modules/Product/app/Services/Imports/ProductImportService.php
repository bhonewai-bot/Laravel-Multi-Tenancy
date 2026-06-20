<?php

namespace Modules\Product\Services\Imports;

use Modules\Product\Models\Product;

class ProductImportService
{
    public function __construct(
        private ProductImporterResolver $resolver
    ) {}

    public function import(string $url)
    {
        $importer = $this->resolver->resolve($url);

        $data = $importer->import($url);

        return Product::create([
            'name' => $data->name,
            'sku' => $data->sku,
            'price' => $data->price,
            'quantity' => $data->quantity,
            'description' => $data->description,
            'image' => $data->image
        ]);
    }
}