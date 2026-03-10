<?php

namespace Tests\Feature\Tenancy;

use App\Http\Controllers\Tenant\DomainController;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\CloudflareService;
use App\Services\TenantDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CloudflareDomainStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        tenancy()->end();
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_saves_cloudflare_pending_statuses_for_new_domain(): void
    {
        config(['cloudflare.enabled' => true]);

        $tenant = $this->insertTenant('t940');
        tenancy()->initialize($tenant);

        $request = Request::create("http://{$tenant->id}.app.localhost/domains", 'POST', [
            'domain' => "shop.{$tenant->id}.example.test",
        ]);

        $this->app->instance('request', $request);

        $cloudflare = Mockery::mock(CloudflareService::class);
        $cloudflare->shouldReceive('createHostname')->once()->andReturn(['success' => true]);
        $cloudflare->shouldReceive('mapStatuses')->once()->andReturn([
            'cf_hostname_id' => 'cf-host-001',
            'cf_hostname_status' => 'pending_validation',
            'cf_ssl_status' => 'initializing',
            'cf_error' => null,
            'cf_payload' => ['result' => ['id' => 'cf-host-001']],
        ]);

        $controller = new DomainController(app(TenantDomainService::class), $cloudflare);
        $response = $controller->store($request);

        $this->assertSame(302, $response->getStatusCode());

        $domain = Domain::query()
            ->where('tenant_id', $tenant->id)
            ->where('domain', "shop.{$tenant->id}.example.test")
            ->firstOrFail();

        $this->assertSame('cf-host-001', $domain->cf_hostname_id);
        $this->assertSame('pending_validation', $domain->cf_hostname_status);
        $this->assertSame('initializing', $domain->cf_ssl_status);
        $this->assertNull($domain->verified_at);
        $this->assertNotNull($domain->cf_last_checked_at);
    }

    public function test_check_status_sets_verified_at_when_hostname_and_ssl_are_active(): void
    {
        $tenant = $this->insertTenant('t941');

        $domainId = (int) DB::table('domains')->insertGetId([
            'domain' => "live.{$tenant->id}.example.test",
            'tenant_id' => $tenant->id,
            'cf_hostname_id' => 'cf-host-live',
            'cf_hostname_status' => 'pending_validation',
            'cf_ssl_status' => 'pending_validation',
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        tenancy()->initialize($tenant);

        $request = Request::create("http://{$tenant->id}.app.localhost/domains/{$domainId}/check-status", 'POST', [], [], [], [
            'HTTP_REFERER' => "http://{$tenant->id}.app.localhost/domains/{$domainId}",
        ]);
        $this->app->instance('request', $request);

        $cloudflare = Mockery::mock(CloudflareService::class);
        $cloudflare->shouldReceive('getHostname')->once()->andReturn(['success' => true]);
        $cloudflare->shouldReceive('mapStatuses')->once()->andReturn([
            'cf_hostname_id' => 'cf-host-live',
            'cf_hostname_status' => 'active',
            'cf_ssl_status' => 'active',
            'cf_error' => null,
            'cf_payload' => ['result' => ['id' => 'cf-host-live']],
        ]);

        $controller = new DomainController(app(TenantDomainService::class), $cloudflare);
        $response = $controller->checkStatus(Domain::query()->findOrFail($domainId));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNotNull(Domain::query()->whereKey($domainId)->value('verified_at'));
    }

    public function test_check_status_keeps_verified_at_null_when_hostname_pending_but_ssl_active(): void
    {
        $tenant = $this->insertTenant('t942');

        $domainId = (int) DB::table('domains')->insertGetId([
            'domain' => "pending.{$tenant->id}.example.test",
            'tenant_id' => $tenant->id,
            'cf_hostname_id' => 'cf-host-pending',
            'cf_hostname_status' => 'pending_validation',
            'cf_ssl_status' => 'pending_validation',
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        tenancy()->initialize($tenant);

        $request = Request::create("http://{$tenant->id}.app.localhost/domains/{$domainId}/check-status", 'POST', [], [], [], [
            'HTTP_REFERER' => "http://{$tenant->id}.app.localhost/domains/{$domainId}",
        ]);
        $this->app->instance('request', $request);

        $cloudflare = Mockery::mock(CloudflareService::class);
        $cloudflare->shouldReceive('getHostname')->once()->andReturn(['success' => true]);
        $cloudflare->shouldReceive('mapStatuses')->once()->andReturn([
            'cf_hostname_id' => 'cf-host-pending',
            'cf_hostname_status' => 'pending',
            'cf_ssl_status' => 'active',
            'cf_error' => null,
            'cf_payload' => ['result' => ['id' => 'cf-host-pending']],
        ]);

        $controller = new DomainController(app(TenantDomainService::class), $cloudflare);
        $response = $controller->checkStatus(Domain::query()->findOrFail($domainId));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNull(Domain::query()->whereKey($domainId)->value('verified_at'));
        $this->assertSame('pending', Domain::query()->whereKey($domainId)->value('cf_hostname_status'));
        $this->assertSame('active', Domain::query()->whereKey($domainId)->value('cf_ssl_status'));
    }

    private function insertTenant(string $tenantId): Tenant
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'data' => json_encode(['name' => 'Tenant ' . $tenantId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Tenant::query()->findOrFail($tenantId);
    }
}

