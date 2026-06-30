<?php

namespace Tests\Feature\Tenancy;

use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\TenantBootstrapSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class HostAccessPolicyTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }

    public function test_central_guests_are_redirected_to_central_login(): void
    {
        $this->get('http://app.localhost/tenants')
            ->assertRedirect('/login');
    }

    public function test_verified_tenant_login_screen_can_be_rendered(): void
    {
        $tenant = $this->createTenantWithPrimaryDomain($this->makeTenantId('vr'));
        $tenantHost = "{$tenant->id}.app.localhost";

        $this->migrateTenantDatabase($tenant);

        $this->get("http://{$tenantHost}/login")
            ->assertOk();
    }

    public function test_tenant_guests_are_redirected_to_tenant_login_when_hitting_dashboard(): void
    {
        $tenant = $this->createTenantWithPrimaryDomain($this->makeTenantId('gd'));
        $tenantHost = "{$tenant->id}.app.localhost";

        $this->migrateTenantDatabase($tenant);

        $this->get("http://{$tenantHost}/dashboard")
            ->assertRedirect('/login');
    }

    public function test_unknown_host_returns_404(): void
    {
        $this->get('http://unknown.example.test/login')
            ->assertNotFound();
    }

    public function test_deleted_tenant_host_returns_404(): void
    {
        $tenant = $this->createTenantWithPrimaryDomain($this->makeTenantId('dl'));
        $tenantHost = "{$tenant->id}.app.localhost";

        Domain::query()
            ->where('tenant_id', $tenant->id)
            ->where('domain', $tenantHost)
            ->delete();

        $this->get("http://{$tenantHost}/login")
            ->assertNotFound();
    }

    public function test_unverified_custom_domain_returns_403(): void
    {
        $tenant = Event::fakeFor(fn () => Tenant::create([
            'id' => $this->makeTenantId('uv'),
            'name' => 'Pending Tenant',
            'email' => 'pending@example.com',
        ]));

        $tenant->domains()->create([
            'domain' => 'pending.example.test',
            'verification_code' => 'pending-code',
            'verified_at' => null,
        ]);

        $this->get('http://pending.example.test/login')
            ->assertForbidden();
    }

    public function test_tenant_users_are_redirected_to_dashboard_after_login(): void
    {
        putenv('TENANT_DEFAULT_ADMIN_PASSWORD=ChangeMe123!');
        $_ENV['TENANT_DEFAULT_ADMIN_PASSWORD'] = 'ChangeMe123!';
        $_SERVER['TENANT_DEFAULT_ADMIN_PASSWORD'] = 'ChangeMe123!';

        [$tenant] = $this->createTenantAndSeedAdmin($this->makeTenantId('lg'));
        $tenantHost = "{$tenant->id}.app.localhost";

        $response = $this->post("http://{$tenantHost}/login", [
            'email' => 'admin@example.com',
            'password' => 'ChangeMe123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
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
        return strtolower('t'.$prefix.Str::random(6));
    }
}
