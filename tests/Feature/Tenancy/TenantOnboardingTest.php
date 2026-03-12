<?php

namespace Tests\Feature\Tenancy;

use App\Models\Domain;
use App\Models\User;
use App\Services\CloudflareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Stancl\Tenancy\Events\TenantCreated;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_central_admin_can_create_tenant_and_domain(): void
    {
        Event::fake([TenantCreated::class]);

        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post('/tenants', [
                'tenant_id' => 't100',
                'name' => 'Tenant 100',
                'email' => 'tenant100@example.com',
                'domain' => 't100.app.localhost',
                'description' => 'CI tenant onboarding check',
            ]);

        $response
            ->assertRedirect('/tenants')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', [
            'id' => 't100',
        ]);

        $this->assertDatabaseHas('domains', [
            'tenant_id' => 't100',
            'domain' => 't100.app.localhost',
        ]);

        Event::assertDispatched(TenantCreated::class);
    }

    public function test_central_admin_auto_syncs_custom_domain_with_cloudflare(): void
    {
        Event::fake([TenantCreated::class]);
        config(['cloudflare.enabled' => true]);

        $cloudflare = Mockery::mock(CloudflareService::class);
        $cloudflare->shouldReceive('createHostname')->once()->with('rift.example.test')->andReturn(['success' => true]);
        $cloudflare->shouldReceive('mapStatuses')->once()->andReturn([
            'cf_hostname_id' => 'cf-central-001',
            'cf_hostname_status' => 'pending',
            'cf_ssl_status' => 'initializing',
            'cf_error' => null,
            'cf_payload' => ['result' => ['id' => 'cf-central-001']],
        ]);
        $this->app->instance(CloudflareService::class, $cloudflare);

        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post('/tenants', [
                'tenant_id' => 'rift',
                'name' => 'Rift',
                'email' => 'rift@example.com',
                'domain' => 'rift.example.test',
                'description' => 'Custom domain onboarding check',
            ]);

        $response
            ->assertRedirect('/tenants')
            ->assertSessionHas('success');

        $domain = Domain::query()
            ->where('tenant_id', 'rift')
            ->where('domain', 'rift.example.test')
            ->firstOrFail();

        $this->assertSame('cf-central-001', $domain->cf_hostname_id);
        $this->assertSame('pending', $domain->cf_hostname_status);
        $this->assertSame('initializing', $domain->cf_ssl_status);
        $this->assertNull($domain->verified_at);
        $this->assertNotNull($domain->cf_last_checked_at);

        Event::assertDispatched(TenantCreated::class);
    }
}
