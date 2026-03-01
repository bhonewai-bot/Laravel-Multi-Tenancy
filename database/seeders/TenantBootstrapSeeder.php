<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantBootstrapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(TenantRbacSeeder::class);

        $tenantId = (string) (tenant('id') ?? 'tenant');
        $defaultPassword = (string) env('TENANT_DEFAULT_ADMIN_PASSWORD', 'ChangeMe123!');

        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $admin = User::firstOrCreate(
            ['email' => "admin@{$tenantId}.local"],
            // ['email' => "admin@example.local"],
            [
                'name' => 'Admin User',
                'password' => $defaultPassword,
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]
        );

        if ((int) $admin->role_id !== (int) $adminRole->id) {
            $admin->update(['role_id' => $adminRole->id]);
        }

        // Optional: seed staff user later from a dedicated onboarding flow.
    }
}
