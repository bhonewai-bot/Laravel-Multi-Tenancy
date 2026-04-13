<?php

namespace Modules\Product\Services\Imports\Importers;

class ShopeeProductImporter
{
    public function supports(string $url): bool
    {
        return str_contains(parse_url($url, PHP_URL_HOST) ?? '', 'shopee');
    }

    public function import(string $url) 
    {
        
    }
}