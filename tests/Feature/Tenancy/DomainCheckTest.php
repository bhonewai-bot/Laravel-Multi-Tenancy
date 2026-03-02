<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DomainCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('DOMAIN_CHECK_TOKEN=testing-domain-token');
        $_ENV['DOMAIN_CHECK_TOKEN'] = 'testing-domain-token';
        $_SERVER['DOMAIN_CHECK_TOKEN'] = 'testing-domain-token';
    }

    public function test_domain_check_allows_central_domain(): void
    {
        $response = $this->get('/internal/domain-check?domain=app.localhost&token=testing-domain-token');

        $response
            ->assertOk()
            ->assertSeeText('OK');
    }

    public function test_domain_check_allows_verified_custom_domain(): void
    {
        $this->insertTenantWithDomain('t900', 'sale.example.test', true);

        $response = $this->get('/internal/domain-check?domain=sale.example.test&token=testing-domain-token');

        $response
            ->assertOk()
            ->assertSeeText('OK');
    }

    public function test_domain_check_rejects_unverified_custom_domain(): void
    {
        $this->insertTenantWithDomain('t901', 'pending.example.test', false);

        $response = $this->get('/internal/domain-check?domain=pending.example.test&token=testing-domain-token');

        $response->assertNotFound();
    }

    public function test_domain_check_rejects_invalid_token(): void
    {
        $this->insertTenantWithDomain('t902', 'token.example.test', true);

        $response = $this->get('/internal/domain-check?domain=token.example.test&token=wrong-token');

        $response->assertForbidden();
    }

    private function insertTenantWithDomain(string $tenantId, string $domain, bool $verified): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'data' => json_encode(['name' => 'Tenant ' . $tenantId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('domains')->insert([
            'domain' => $domain,
            'tenant_id' => $tenantId,
            'verification_code' => $verified ? 'code' : 'pending-code',
            'verified_at' => $verified ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
