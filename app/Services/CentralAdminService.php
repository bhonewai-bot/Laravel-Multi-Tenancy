<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CentralAdminService
{
    public function ensureConfiguredSuperAdminExists(): void
    {
        if (function_exists('tenant') && tenant()) {
            return;
        }

        try {
            if (! Schema::hasTable('users')) {
                return;
            }
        } catch (Throwable $e) {
            Log::warning('central_admin.ensure_skipped', [
                'reason' => 'database_unavailable',
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $email = trim((string) config('auth.central_admin.email'));
        $name = trim((string) config('auth.central_admin.name', 'Super Admin'));
        $password = (string) config('auth.central_admin.password');

        if ($email === '' || $password === '') {
            Log::warning('central_admin.ensure_skipped', [
                'reason' => 'missing_credentials',
                'email_present' => $email !== '',
                'password_present' => $password !== '',
            ]);

            return;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name !== '' ? $name : 'Super Admin',
            'password' => $password,
            'email_verified_at' => now(),
        ])->save();

        Log::info('central_admin.ensured', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
