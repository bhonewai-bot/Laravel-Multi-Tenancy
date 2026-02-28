<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use Database\Seeders\TenantBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantBootstrapSeederTest extends TestCase
{
    use RefreshDatabase;

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
