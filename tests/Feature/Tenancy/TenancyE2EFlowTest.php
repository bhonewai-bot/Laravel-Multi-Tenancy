<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\EnsureVerifiedTenantDomain;
use App\Models\Domain;
use App\Models\Module;
use App\Models\ModuleRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDomainService;
use Database\Seeders\TenantBootstrapSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Mockery;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TestCase;

class TenancyE2EFlowTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        tenancy()->end();
        Mockery::close();
        parent::tearDown();
    }

    public function test_tenant_provisioning_flow_from_central_route(): void
    {
        Event::fake([TenantCreated::class]);

        $tenantId = $this->makeTenantId('tp');
        $tenantDomain = "{$tenantId}.app.localhost";

        $centralAdmin = User::factory()->create();

        $response = $this
            ->actingAs($centralAdmin)
            ->post('http://app.localhost/tenants', [
                'tenant_id' => $tenantId,
                'name' => "Tenant {$tenantId}",
                'email' => "{$tenantId}@example.com",
                'domain' => $tenantDomain,
                'description' => 'Step 16 provisioning check',
            ]);

        $response
            ->assertRedirect('/tenants')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', ['id' => $tenantId]);
        $this->assertDatabaseHas('domains', [
            'tenant_id' => $tenantId,
            'domain' => $tenantDomain,
        ]);

        Event::assertDispatched(TenantCreated::class);
    }

    public function test_tenant_signup_isolation_from_central_users(): void
    {
        $tenant = $this->createTenantWithPrimaryDomain($this->makeTenantId('si'));
        $tenantHost = "{$tenant->id}.app.localhost";

        $this->migrateTenantDatabase($tenant);

        $centralConnection = (string) config('tenancy.database.central_connection', config('database.default'));
        $centralUsersBefore = DB::connection($centralConnection)->table('users')->count();

        $tenantEmail = "new.user@{$tenant->id}.local";

        $response = $this
            ->post("http://{$tenantHost}/register", [
                'name' => 'Tenant User',
                'email' => $tenantEmail,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertRedirect('/dashboard');

        // Central users table should remain unchanged.
        $this->assertSame(
            $centralUsersBefore,
            DB::connection($centralConnection)->table('users')->count()
        );

        // New user must exist in tenant DB.
        tenancy()->initialize($tenant);
        $this->assertDatabaseHas('users', ['email' => $tenantEmail]);
        tenancy()->end();
    }

    public function test_request_approve_install_flow_updates_install_state(): void
    {
        [$tenant, $tenantAdmin] = $this->createTenantAndSeedAdmin($this->makeTenantId('ri'));
        $tenantHost = "{$tenant->id}.app.localhost";

        $module = Module::create([
            'name' => 'Product',
            'slug' => 'product',
            'version' => '1.0.0',
            'is_active' => true,
            'price' => 0,
        ]);

        $requestResponse = $this
            ->actingAs($tenantAdmin)
            ->from("http://{$tenantHost}/modules")
            ->post("http://{$tenantHost}/modules/request", [
                'module_id' => $module->id,
            ]);

        $requestResponse->assertSessionHas('success');

        $moduleRequest = ModuleRequest::query()
            ->where('module_id', $module->id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $this->assertSame('pending', $moduleRequest->status);

        $centralAdmin = User::factory()->create();

        $approveResponse = $this
            ->actingAs($centralAdmin)
            ->post("http://app.localhost/module-requests/{$moduleRequest->id}/approve");

        $approveResponse->assertSessionHas('success');

        $moduleRequest->refresh();
        $this->assertSame('approved', $moduleRequest->status);

        $installResponse = $this
            ->actingAs($tenantAdmin)
            ->from("http://{$tenantHost}/modules")
            ->post("http://{$tenantHost}/modules/install", [
                'module_id' => $module->id,
            ]);

        $installResponse->assertSessionHas('success');

        $installedModules = $tenant->fresh()->getAttribute('installed_modules') ?? [];
        $this->assertContains('product', $installedModules);
    }

    public function test_module_guard_returns_403_then_200_after_install_state(): void
    {
        [$tenant, $tenantAdmin] = $this->createTenantAndSeedAdmin($this->makeTenantId('mg'));
        $tenantHost = "{$tenant->id}.app.localhost";

        // Stable probe route for module middleware in test env.
        if (! Route::has('tenant.module.guard.probe')) {
            Route::middleware([
                'web',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                EnsureVerifiedTenantDomain::class,
                'auth',
                'module:customer',
            ])->get('/_e2e/module-guard-probe', fn () => response('OK', 200))
                ->name('tenant.module.guard.probe');
        }

        $blocked = $this
            ->actingAs($tenantAdmin)
            ->get("http://{$tenantHost}/_e2e/module-guard-probe");

        $blocked->assertForbidden();
        tenancy()->end();

        $tenant = Tenant::query()->findOrFail($tenant->id);
        $tenant->setAttribute('installed_modules', ['customer']);
        $tenant->save();
        $this->assertContains('customer', Tenant::query()->findOrFail($tenant->id)->getAttribute('installed_modules') ?? []);

        $allowed = $this
            ->actingAs($tenantAdmin)
            ->get("http://{$tenantHost}/_e2e/module-guard-probe");

        $allowed->assertOk();
        tenancy()->end();
    }

    public function test_custom_domain_add_and_verify_flow_with_domain_check_gate(): void
    {
        [$tenant, $tenantAdmin] = $this->createTenantAndSeedAdmin($this->makeTenantId('cd'));
        $tenantHost = "{$tenant->id}.app.localhost";
        $customHost = "shop.{$tenant->id}.example.test";

        $storeResponse = $this
            ->actingAs($tenantAdmin)
            ->from("http://{$tenantHost}/domains/create")
            ->post("http://{$tenantHost}/domains", [
                'domain' => $customHost,
            ]);

        $storeResponse->assertSessionHas('success');

        $customDomain = Domain::query()
            ->where('tenant_id', $tenant->id)
            ->where('domain', $customHost)
            ->firstOrFail();

        $this->assertNull($customDomain->verified_at);
        $this->assertNotNull($customDomain->verification_code);

        $service = Mockery::mock(TenantDomainService::class)->makePartial();
        $service->shouldReceive('checkDnsTxtVerification')->once()->andReturnTrue();
        $this->app->instance(TenantDomainService::class, $service);

        $verifyResponse = $this
            ->actingAs($tenantAdmin)
            ->from("http://{$tenantHost}/domains")
            ->post("http://{$tenantHost}/domains/{$customDomain->id}/verify");

        $verifyResponse->assertSessionHas('success');

        $customDomain->refresh();
        $this->assertNotNull($customDomain->verified_at);

        $domainCheck = $this
            ->get("http://app.localhost/internal/domain-check?domain={$customHost}&token=testing-domain-token");

        $domainCheck->assertOk()->assertSeeText('OK');
    }

    private function createTenantWithPrimaryDomain(string $tenantId): Tenant
    {
        $tenant = Event::fakeFor(fn () => Tenant::create([
            'id' => $tenantId,
            'name' => "Tenant {$tenantId}",
            'email' => "{$tenantId}@example.com",
        ]));

        $tenant->domains()->create([
            'domain' => "{$tenantId}.app.localhost",
            'verification_code' => null,
            'verified_at' => now(),
        ]);

        return $tenant->fresh();
    }

    private function migrateTenantDatabase(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        tenancy()->end();
    }

    private function createTenantAndSeedAdmin(string $tenantId): array
    {
        $tenant = $this->createTenantWithPrimaryDomain($tenantId);

        tenancy()->initialize($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        app(TenantBootstrapSeeder::class)->run();

        $tenantAdmin = User::query()
            ->where('email', 'admin@example.com')
            ->firstOrFail();

        tenancy()->end();

        return [$tenant->fresh(), $tenantAdmin];
    }

    private function makeTenantId(string $prefix): string
    {
        return strtolower('t' . $prefix . Str::random(6));
    }
}
