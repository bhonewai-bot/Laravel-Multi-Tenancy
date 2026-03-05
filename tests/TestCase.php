<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $centralDomain = config('tenancy.central_domains.0')
            ?: parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST)
            ?: 'localhost';

        $this->withServerVariables([
            'HTTP_HOST' => $centralDomain,
        ]);
    }
}
