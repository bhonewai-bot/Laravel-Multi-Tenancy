<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $centralDomain = config('tenancy.central_domains.0');

        if (is_string($centralDomain) && $centralDomain !== '') {
            $this->withServerVariables([
                'HTTP_HOST' => $centralDomain,
            ]);
        }
    }
}
