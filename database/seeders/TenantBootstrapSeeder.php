<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TenantBootstrapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = (string) (tenant('id') ?? 'tenant');
        $defaultPassword = (string) env('TENANT_DEFAULT_ADMIN_PASSWORD', 'ChangeMe123!');

        User::firstOrCreate(
            ['email' => "admin@{$tenantId}.local"],
            // ['email' => "admin@example.local"],
            [
                'name' => 'Admin User',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]
        );
    }
}
