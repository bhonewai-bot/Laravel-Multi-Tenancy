<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\ModuleInstallation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantModelTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(string $id): Tenant
    {
        DB::table('tenants')->insert([
            'id' => $id,
            'data' => json_encode(['name' => $id]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Tenant::find($id);
    }

    public function test_installations_relationship(): void
    {
        $tenant = $this->createTenant('tenant_a');
        $module = Module::create(['name' => 'Product', 'slug' => 'product', 'version' => '1.0.0', 'is_active' => true]);

        ModuleInstallation::create(['tenant_id' => 'tenant_a', 'module_id' => $module->id, 'installed_at' => now()]);

        $this->assertCount(1, $tenant->installations);
    }

    public function test_installed_modules_relationship(): void
    {
        $tenant = $this->createTenant('tenant_b');
        $module = Module::create(['name' => 'Blog', 'slug' => 'blog', 'version' => '1.0.0', 'is_active' => true]);

        ModuleInstallation::create(['tenant_id' => 'tenant_b', 'module_id' => $module->id, 'installed_at' => now()]);

        $this->assertTrue($tenant->installedModules->contains('slug', 'blog'));
    }

    public function test_is_installed(): void
    {
        $tenant = $this->createTenant('tenant_c');
        $module = Module::create(['name' => 'Shop', 'slug' => 'shop', 'version' => '1.0.0', 'is_active' => true]);

        $this->assertFalse($tenant->isInstalled('shop'));

        ModuleInstallation::create(['tenant_id' => 'tenant_c', 'module_id' => $module->id, 'installed_at' => now()]);

        $this->assertTrue($tenant->isInstalled('shop'));
    }

    public function test_primary_domain(): void
    {
        $tenant = $this->createTenant('mytenant');
        $centralDomain = config('tenancy.central_domains.0');

        DB::table('domains')->insert([
            'tenant_id' => 'mytenant',
            'domain' => strtolower("mytenant.{$centralDomain}"),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $primary = $tenant->primaryDomain();
        $this->assertNotNull($primary);
        $this->assertSame(strtolower("mytenant.{$centralDomain}"), $primary->domain);
    }
}
