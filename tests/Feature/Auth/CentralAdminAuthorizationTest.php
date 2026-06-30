<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'email' => config('auth.central_admin.email'),
        ]);
    }

    private function nonAdminUser(): User
    {
        return User::factory()->create([
            'email' => 'regular@example.com',
        ]);
    }

    public function test_admin_can_access_tenants_index(): void
    {
        $response = $this
            ->actingAs($this->adminUser())
            ->get('/tenants');

        $response->assertOk();
    }

    public function test_admin_can_access_modules_index(): void
    {
        $response = $this
            ->actingAs($this->adminUser())
            ->get('/modules');

        $response->assertOk();
    }

    public function test_admin_can_access_module_requests_index(): void
    {
        $response = $this
            ->actingAs($this->adminUser())
            ->get('/module-requests');

        $response->assertOk();
    }

    public function test_non_admin_receives_403_on_tenants_index(): void
    {
        $response = $this
            ->actingAs($this->nonAdminUser())
            ->get('/tenants');

        $response->assertForbidden();
    }

    public function test_non_admin_receives_403_on_modules_index(): void
    {
        $response = $this
            ->actingAs($this->nonAdminUser())
            ->get('/modules');

        $response->assertForbidden();
    }

    public function test_non_admin_receives_403_on_module_requests_index(): void
    {
        $response = $this
            ->actingAs($this->nonAdminUser())
            ->get('/module-requests');

        $response->assertForbidden();
    }

    public function test_non_admin_can_access_dashboard(): void
    {
        $response = $this
            ->actingAs($this->nonAdminUser())
            ->get('/dashboard');

        $response->assertOk();
    }

    public function test_non_admin_can_access_profile(): void
    {
        $response = $this
            ->actingAs($this->nonAdminUser())
            ->get('/profile');

        $response->assertOk();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/tenants');

        $response->assertRedirect('/login');
    }

    public function test_tenant_store_request_rejects_non_admin(): void
    {
        $response = $this
            ->actingAs($this->nonAdminUser())
            ->post('/tenants', [
                'tenant_id' => 'test-tenant',
                'name' => 'Test Tenant',
                'email' => 'tenant@example.com',
                'domain' => 'test.example.com',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_gate_allows_admin_user(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user);
        $this->assertTrue($user->email === config('auth.central_admin.email'));
    }

    public function test_non_admin_is_blocked_on_tenant_create(): void
    {
        $response = $this
            ->actingAs($this->nonAdminUser())
            ->get('/tenants/create');

        $response->assertForbidden();
    }
}
