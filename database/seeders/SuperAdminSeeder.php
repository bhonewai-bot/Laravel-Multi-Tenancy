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
        $email = trim((string) config('auth.central_admin.email'));
        $name = trim((string) config('auth.central_admin.name', 'Super Admin'));
        $password = (string) config('auth.central_admin.password');

        if ($email === '' || $password === '') {
            return;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name !== '' ? $name : 'Super Admin',
            'password' => $password,
            'email_verified_at' => now(),
        ])->save();
    }
}
