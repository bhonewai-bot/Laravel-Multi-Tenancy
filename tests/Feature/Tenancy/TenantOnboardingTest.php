<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantCreated;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_create_tenant_and_domain(): void
    {
        Event::fake([TenantCreated::class]);

        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post('/tenants', [
                'tenant_id' => 't100',
                'name' => 'Tenant 100',
                'email' => 'tenant100@example.com',
                'domain' => 't100.app.localhost',
                'description' => 'CI tenant onboarding check',
            ]);

        $response
            ->assertRedirect('/tenants')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', [
            'id' => 't100',
        ]);

        $this->assertDatabaseHas('domains', [
            'tenant_id' => 't100',
            'domain' => 't100.app.localhost',
        ]);

        Event::assertDispatched(TenantCreated::class);
    }
}
