<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\CentralAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralAdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_central_admin_is_created_idempotently(): void
    {
        config([
            'auth.central_admin.email' => 'boss@example.com',
            'auth.central_admin.name' => 'Boss Admin',
            'auth.central_admin.password' => 'secret-pass',
        ]);

        $service = app(CentralAdminService::class);
        $service->ensureConfiguredSuperAdminExists();
        $service->ensureConfiguredSuperAdminExists();

        $user = User::query()->where('email', 'boss@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Boss Admin', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(password_verify('secret-pass', (string) $user->password));
        $this->assertSame(1, User::query()->where('email', 'boss@example.com')->count());
    }

    public function test_register_route_is_disabled_when_public_registration_is_off(): void
    {
        config(['auth.allow_registration' => false]);

        $this->get('/register')->assertNotFound();
    }
}
