<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use Database\Seeders\TenantBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class TenantBootstrapSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Bring in RBAC-only tenant tables without re-running tenant users migration.
        foreach ([
            '2026_03_01_064405_create_roles_table.php',
            '2026_03_01_064412_create_features_table.php',
            '2026_03_01_064419_create_permissions_table.php',
            '2026_03_01_064426_create_role_permissions_table.php',
        ] as $migrationFile) {
            Artisan::call('migrate', [
                '--path' => database_path("migrations/tenant/{$migrationFile}"),
                '--realpath' => true,
                '--force' => true,
            ]);
        }

        if (! Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->index();
            });
        }
    }

    public function test_tenant_bootstrap_seeder_is_idempotent_and_sets_hashed_password(): void
    {
        putenv('TENANT_DEFAULT_ADMIN_PASSWORD=Testing123!');
        $_ENV['TENANT_DEFAULT_ADMIN_PASSWORD'] = 'Testing123!';
        $_SERVER['TENANT_DEFAULT_ADMIN_PASSWORD'] = 'Testing123!';

        $seeder = app(TenantBootstrapSeeder::class);

        $seeder->run();
        $seeder->run();

        $this->assertDatabaseCount('users', 1);

        $admin = User::firstOrFail();

        $this->assertSame('admin@tenant.local', $admin->email);
        $this->assertTrue(Hash::check('Testing123!', $admin->password));
    }
}
