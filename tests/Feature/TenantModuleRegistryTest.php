<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\ModuleInstallation;
use App\Models\Tenant;
use App\Services\TenantModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantModuleRegistryTest extends TestCase
{
    use RefreshDatabase;

    private TenantModuleRegistry $registry;

    private Tenant $tenant;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = app(TenantModuleRegistry::class);
        $this->tenant = $this->createTenant('tenant_abc');
        $this->module = Module::create([
            'name' => 'Product',
            'slug' => 'product',
            'version' => '1.0.0',
            'is_active' => true,
        ]);
    }

    /**
     * Insert a tenant record directly to avoid triggering Stancl database creation.
     */
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

    // ----------------------------------------------------------------
    // STATE-01: module_installations table
    // ----------------------------------------------------------------

    public function test_get_installed_modules_returns_empty_for_new_tenant(): void
    {
        $this->assertSame([], $this->registry->getInstalledModules($this->tenant));
    }

    public function test_mark_installed_creates_database_record(): void
    {
        $this->registry->markInstalled($this->tenant, 'product');

        $this->assertDatabaseHas('module_installations', [
            'tenant_id' => 'tenant_abc',
            'module_id' => $this->module->id,
        ]);

        $installed = $this->registry->getInstalledModules($this->tenant);
        $this->assertSame(['product'], $installed);
    }

    public function test_mark_installed_is_idempotent(): void
    {
        $this->registry->markInstalled($this->tenant, 'product');
        $this->registry->markInstalled($this->tenant, 'product');

        $this->assertSame(1, ModuleInstallation::where('tenant_id', 'tenant_abc')->count());
    }

    public function test_mark_uninstalled_removes_database_record(): void
    {
        $this->registry->markInstalled($this->tenant, 'product');
        $this->registry->markUninstalled($this->tenant, 'product');

        $this->assertDatabaseMissing('module_installations', [
            'tenant_id' => 'tenant_abc',
            'module_id' => $this->module->id,
        ]);

        $this->assertSame([], $this->registry->getInstalledModules($this->tenant));
    }

    public function test_mark_uninstalled_is_safe_for_missing_installation(): void
    {
        $this->registry->markUninstalled($this->tenant, 'product');

        $this->assertSame([], $this->registry->getInstalledModules($this->tenant));
    }

    // ----------------------------------------------------------------
    // STATE-02: module_operations table
    // ----------------------------------------------------------------

    public function test_start_module_operation_creates_queued_record(): void
    {
        $this->registry->startModuleOperation($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL, 'Queued');

        $this->assertDatabaseHas('module_operations', [
            'tenant_id' => 'tenant_abc',
            'module_slug' => 'product',
            'action' => 'install',
            'status' => 'queued',
            'message' => 'Queued',
        ]);
    }

    public function test_mark_running_updates_status(): void
    {
        $this->registry->startModuleOperation($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL);
        $this->registry->markModuleOperationRunning($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL, 'Running now');

        $op = $this->registry->getModuleOperation($this->tenant, 'product');
        $this->assertSame('running', $op['status']);
        $this->assertSame('Running now', $op['message']);
    }

    public function test_mark_succeeded_updates_status(): void
    {
        $this->registry->startModuleOperation($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL);
        $this->registry->markModuleOperationSucceeded($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL, 'Done');

        $op = $this->registry->getModuleOperation($this->tenant, 'product');
        $this->assertSame('success', $op['status']);
        $this->assertSame('Done', $op['message']);
    }

    public function test_mark_failed_updates_status(): void
    {
        $this->registry->startModuleOperation($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL);
        $this->registry->markModuleOperationFailed($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL, 'Error');

        $op = $this->registry->getModuleOperation($this->tenant, 'product');
        $this->assertSame('failed', $op['status']);
        $this->assertSame('Error', $op['message']);
    }

    public function test_get_module_operations_returns_all_for_tenant(): void
    {
        $this->registry->startModuleOperation($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL, 'Queued');

        Module::create(['name' => 'Blog', 'slug' => 'blog', 'version' => '1.0.0', 'is_active' => true]);
        $this->registry->startModuleOperation($this->tenant, 'blog', TenantModuleRegistry::ACTION_UNINSTALL, 'Removing');

        $ops = $this->registry->getModuleOperations($this->tenant);

        $this->assertCount(2, $ops);
        $this->assertArrayHasKey('product', $ops);
        $this->assertArrayHasKey('blog', $ops);
        $this->assertSame('install', $ops['product']['action']);
        $this->assertSame('uninstall', $ops['blog']['action']);
    }

    public function test_clear_module_operation_removes_record(): void
    {
        $this->registry->startModuleOperation($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL);
        $this->registry->clearModuleOperation($this->tenant, 'product');

        $this->assertDatabaseMissing('module_operations', [
            'tenant_id' => 'tenant_abc',
            'module_slug' => 'product',
        ]);
    }

    public function test_get_module_operation_returns_null_for_missing(): void
    {
        $this->assertNull($this->registry->getModuleOperation($this->tenant, 'nonexistent'));
    }

    // ----------------------------------------------------------------
    // STATE-03: Atomic transactions — tenant isolation
    // ----------------------------------------------------------------

    public function test_operations_do_not_leak_across_tenants(): void
    {
        $tenant2 = $this->createTenant('tenant_xyz');

        $this->registry->markInstalled($this->tenant, 'product');
        $this->registry->startModuleOperation($this->tenant, 'product', TenantModuleRegistry::ACTION_INSTALL, 'Working');

        $this->assertSame([], $this->registry->getInstalledModules($tenant2));
        $this->assertSame([], $this->registry->getModuleOperations($tenant2));
    }

    // ----------------------------------------------------------------
    // Status helpers
    // ----------------------------------------------------------------

    public function test_is_terminal_status(): void
    {
        $this->assertTrue($this->registry->isTerminalStatus('success'));
        $this->assertTrue($this->registry->isTerminalStatus('failed'));
        $this->assertFalse($this->registry->isTerminalStatus('queued'));
        $this->assertFalse($this->registry->isTerminalStatus('running'));
        $this->assertFalse($this->registry->isTerminalStatus(null));
    }

    public function test_is_processing_status(): void
    {
        $this->assertTrue($this->registry->isProcessingStatus('queued'));
        $this->assertTrue($this->registry->isProcessingStatus('running'));
        $this->assertFalse($this->registry->isProcessingStatus('success'));
        $this->assertFalse($this->registry->isProcessingStatus('failed'));
        $this->assertFalse($this->registry->isProcessingStatus(null));
    }

    // ----------------------------------------------------------------
    // STATE-04: Data migration (JSON blob to tables)
    // ----------------------------------------------------------------

    public function test_data_migration_moves_json_blob_to_tables(): void
    {
        // Insert a tenant with legacy JSON blob data.
        DB::table('tenants')->insert([
            'id' => 'tenant_json',
            'data' => json_encode([
                'installed_modules' => ['product'],
                'module_operations' => [
                    'product' => [
                        'action' => 'install',
                        'status' => 'success',
                        'message' => 'Installed successfully.',
                        'updated_at' => '2026-06-25 10:00:00',
                    ],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run the migration logic directly (RefreshDatabase already ran all migrations on empty tables).
        TenantModuleRegistry::migrateFromJsonBlobs();

        // Verify data was moved to new tables.
        $this->assertDatabaseHas('module_installations', [
            'tenant_id' => 'tenant_json',
            'module_id' => $this->module->id,
        ]);

        $this->assertDatabaseHas('module_operations', [
            'tenant_id' => 'tenant_json',
            'module_slug' => 'product',
            'action' => 'install',
            'status' => 'success',
            'message' => 'Installed successfully.',
        ]);
    }
}
