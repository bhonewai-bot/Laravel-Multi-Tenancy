<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Ensures the central application always has the configured super-admin account.
 *
 * This service runs during application boot in the central context so operators can
 * access the admin surface without depending on manual registration. It must never
 * run inside a tenant context, otherwise tenant user tables could be modified by mistake.
 */
class CentralAdminService
{
    /**
     * Create or update the configured central super-admin when the central users table is available.
     *
     * Side effects:
     * - Reads central auth configuration.
     * - Writes to the central users table.
     * - Emits operational log entries when the bootstrap is skipped or completed.
     */
    public function ensureConfiguredSuperAdminExists(): void
    {
        // Tenant requests must never mutate tenant-local users from central bootstrap logic.
        if (function_exists('tenant') && tenant()) {
            return;
        }

        try {
            // Boot can run before migrations or database connectivity are ready in production.
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

        // Empty credentials should fail closed so production does not create a partially configured admin.
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

        // firstOrNew keeps the operation idempotent across repeated boots and deploys.
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name !== '' ? $name : 'Super Admin',
            'password' => $password,
            'email_verified_at' => Carbon::now(),
        ])->save();

        Log::info('central_admin.ensured', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
