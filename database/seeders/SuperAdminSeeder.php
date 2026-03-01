<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('CENTRAL_SUPERADMIN_EMAIL', 'superadmin@example.com')],
            [
                'name' => env('CENTRAL_SUPERADMIN_NAME', 'Super Admin'),
                'password' => env('CENTRAL_SUPERADMIN_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
