<?php

namespace Modules\Product\Services\Imports\DTOs;

class ProductDto
{
    public function __construct(
        public string $name,
        public string $sku,
        public float $price,
        public int $quantity,
        public ?string $description,
        public ?string $image
    ) {}
}
