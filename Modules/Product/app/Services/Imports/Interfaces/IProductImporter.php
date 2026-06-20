<?php

namespace Modules\Product\Services\Imports\Interfaces;

use Modules\Product\Services\Imports\DTOs\ProductDto;

interface IProductImporter
{
    public function supports(string $url): bool;

    public function import(string $url): ProductDto;
}
