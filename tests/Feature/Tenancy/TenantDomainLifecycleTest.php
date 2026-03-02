<?php

namespace Tests\Feature\Tenancy;

use App\Http\Controllers\Tenant\DomainController;
use App\Http\Middleware\EnsureVerifiedTenantDomain;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\TenantDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TenantDomainLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_custom_domain_is_blocked_on_tenant_routes(): void
    {
        $this->insertTenantWithDomain('t930', 'blocked.example.test', false);

        $tenant = Tenant::query()->findOrFail('t930');
        tenancy()->initialize($tenant);

        $middleware = app(EnsureVerifiedTenantDomain::class);
        $request = Request::create('http://blocked.example.test/dashboard', 'GET');

        try {
            $middleware->handle($request, fn () => response('OK'));
            $this->fail('Expected middleware to block an unverified tenant host.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        } finally {
            tenancy()->end();
        }
    }

    public function test_verify_action_sets_verified_at_on_custom_domain(): void
    {
        $domainId = $this->insertTenantWithDomain('t931', 'verify.example.test', false);
        $tenant = Tenant::query()->findOrFail('t931');
        tenancy()->initialize($tenant);

        $service = Mockery::mock(TenantDomainService::class)->makePartial();
        $service->shouldReceive('checkDnsTxtVerification')->andReturnTrue();
        $controller = new DomainController($service);

        $request = Request::create("http://verify.example.test/domains/{$domainId}/verify", 'POST', [], [], [], [
            'HTTP_REFERER' => 'http://verify.example.test/domains',
        ]);
        $this->app->instance('request', $request);

        $response = $controller->verify(Domain::query()->findOrFail($domainId));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNotNull(Domain::query()->whereKey($domainId)->value('verified_at'));

        tenancy()->end();
    }

    private function insertTenantWithDomain(string $tenantId, string $domain, bool $verified): int
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'data' => json_encode(['name' => 'Tenant ' . $tenantId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('domains')->insertGetId([
            'domain' => $domain,
            'tenant_id' => $tenantId,
            'verification_code' => 'pending-code',
            'verified_at' => $verified ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
