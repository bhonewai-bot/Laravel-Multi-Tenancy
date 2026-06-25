# Testing Patterns

**Analysis Date:** 2026-06-25

## Test Framework

**Runner:**
- PHPUnit 11.5+ for test execution
- Config: `phpunit.xml` in project root
- Use Laravel's `artisan test` command wrapper

**Assertion Library:**
- PHPUnit native assertions
- Laravel test helpers: `assertDatabaseHas()`, `assertModelExists()`, `assertSessionHas()`
- HTTP assertions: `assertRedirect()`, `assertForbidden()`, `assertOk()`

**Run Commands:**
```bash
php artisan test --compact                    # Run all tests
php artisan test --compact tests/Feature/Tenancy  # Run specific directory
php artisan test --compact --filter=testName # Run specific test
```

## Test File Organization

**Location:**
- Feature tests: `tests/Feature/`
- Unit tests: `tests/Unit/`
- Test directory structure mirrors app structure

**Naming:**
- Feature tests: `{FeatureName}Test.php` (e.g., `TenantOnboardingTest.php`)
- Unit tests: `{ClassName}Test.php`
- Test methods: `test_{descriptive_snake_case}` (e.g., `test_central_admin_can_create_tenant_and_domain`)

**Structure:**
```
tests/
├── TestCase.php                    # Base test class
├── Feature/
│   ├── Auth/                       # Authentication tests
│   ├── Tenancy/                    # Multi-tenancy tests
│   └── ...
└── Unit/
    └── ExampleTest.php
```

## Test Structure

**Base Test Class:**
- Custom `TestCase.php` extends `Illuminate\Foundation\Testing\TestCase`
- Configures central domain for tenancy tests
- Disables Vite for faster test runs

**Feature Test Example:**
```php
<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_create_tenant_and_domain(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post('/tenants', [
                'tenant_id' => 't100',
                'name' => 'Tenant 100',
                'email' => 'tenant100@example.com',
                'domain' => 't100.app.localhost',
            ]);

        $response
            ->assertRedirect('/tenants')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', ['id' => 't100']);
        $this->assertDatabaseHas('domains', [
            'tenant_id' => 't100',
            'domain' => 't100.app.localhost',
        ]);
    }
}
```

## Database Management

**RefreshDatabase:**
- Use `RefreshDatabase` trait for most tests
- Resets database between tests for isolation
- Preferred for feature tests

**DatabaseMigrations:**
- Use for complex scenarios needing migration structure
- Preserves schema across tests
- Use when testing migration-related functionality

**Manual DB Setup:**
```php
private function insertTenantWithDomain(string $tenantId, string $domain, bool $verified): void
{
    DB::table('tenants')->insert([
        'id' => $tenantId,
        'data' => json_encode(['name' => 'Tenant '.$tenantId]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('domains')->insert([
        'domain' => $domain,
        'tenant_id' => $tenantId,
        'verification_code' => $verified ? 'code' : 'pending-code',
        'verified_at' => $verified ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

## Mocking

**Framework:** Mockery

**Patterns:**
```php
protected function tearDown(): void
{
    Mockery::close();
    parent::tearDown();
}

public function test_central_admin_auto_syncs_custom_domain_with_cloudflare(): void
{
    config(['cloudflare.enabled' => true]);

    $cloudflare = Mockery::mock(CloudflareService::class);
    $cloudflare->shouldReceive('createHostname')
        ->once()
        ->with('rift.example.test')
        ->andReturn(['success' => true]);
    $cloudflare->shouldReceive('mapStatuses')
        ->once()
        ->andReturn([
            'cf_hostname_id' => 'cf-central-001',
            'cf_hostname_status' => 'pending',
            // ...
        ]);

    $this->app->instance(CloudflareService::class, $cloudflare);

    // Test logic
}
```

**What to Mock:**
- External HTTP calls (Cloudflare API, third-party services)
- Time-dependent operations
- Event dispatches: `Event::fake([TenantCreated::class])`
- File system operations

**What NOT to Mock:**
- Database operations (use RefreshDatabase instead)
- Business logic in Actions and Services (test them directly)
- Configuration values (use `config([...])` helper)

## HTTP Testing

**Request Pattern:**
```php
$response = $this
    ->actingAs($admin)
    ->post('http://app.localhost/tenants', [
        'tenant_id' => 't100',
        'name' => 'Tenant 100',
    ]);

$response
    ->assertRedirect('/tenants')
    ->assertSessionHas('success');
```

**Multi-Tenant Requests:**
```php
$tenantHost = "{$tenant->id}.app.localhost";

$response = $this
    ->actingAs($tenantAdmin)
    ->from("http://{$tenantHost}/modules")
    ->post("http://{$tenantHost}/modules/request", [
        'module_id' => $module->id,
    ]);

$response->assertSessionHas('success');
```

**Assertions:**
- `assertStatus(200)`, `assertRedirect()`, `assertForbidden()`
- `assertSessionHas('success')`, `assertSessionHasErrors()`
- `assertDatabaseHas()`, `assertDatabaseMissing()`
- `assertAuthenticated()`, `assertGuest()`
- `assertSeeText()`, `assertSeeInOrder()`

## Event Testing

```php
use Illuminate\Support\Facades\Event;

Event::fake([TenantCreated::class]);

// Perform action

Event::assertDispatched(TenantCreated::class);
```

## Testing Helpers

**Custom Helpers in Tests:**
```php
private function makeTenantId(string $prefix): string
{
    return $prefix . Str::random(10);
}

private function createTenantAndSeedAdmin(string $tenantId): array
{
    // Setup tenant and admin user
    return [$tenant, $tenantAdmin];
}
```

**PHPUnit Assert Methods:**
```php
$this->assertSame('expected', $actual);
$this->assertNull($value);
$this->assertNotNull($value);
$this->assertTrue($condition);
$this->assertContains('item', $array);
$this->assertStringContainsString('needle', 'haystack');
```

## Test Coverage

**Coverage Requirements:**
- Not enforced via CI configuration
- Recommended to run coverage reports locally before finalizing

**View Coverage:**
```bash
php artisan test --compact --coverage
```

**Priority Areas:**
- Core tenancy logic (tenant creation, domain verification)
- Security-critical paths (authentication, authorization)
- External integrations (Cloudflare API, module installation)
- Form Request validation

## Test Types

**Unit Tests:**
- Test individual methods and classes in isolation
- No database or HTTP layer
- Fast execution

**Feature Tests:**
- Test full request lifecycle through HTTP layer
- Use RefreshDatabase for state management
- Test authentication, authorization, and validation

**End-to-End Tests:**
- Multi-step workflows spanning multiple requests
- Test tenant provisioning and module installation flows
- Use DatabaseMigrations for consistent schema

**Example - End-to-End Flow:**
```php
public function test_request_approve_install_flow_updates_install_state(): void
{
    [$tenant, $tenantAdmin] = $this->createTenantAndSeedAdmin($this->makeTenantId('ri'));

    // Step 1: Tenant requests module
    $requestResponse = $this
        ->actingAs($tenantAdmin)
        ->from("http://{$tenantHost}/modules")
        ->post("http://{$tenantHost}/modules/request", ['module_id' => $module->id]);

    // Step 2: Admin approves request
    $centralAdmin = User::factory()->create();
    $approveResponse = $this
        ->actingAs($centralAdmin)
        ->post("http://app.localhost/module-requests/{$moduleRequest->id}/approve");

    // Step 3: Tenant installs module
    $installResponse = $this
        ->actingAs($tenantAdmin)
        ->post("http://{$tenantHost}/modules/install", ['module_id' => $module->id]);

    // Assert state changes
    $installedModules = $tenant->fresh()->getAttribute('installed_modules') ?? [];
    $this->assertContains('product', $installedModules);
}
```

## Common Testing Patterns

**Config Override:**
```php
config(['cloudflare.enabled' => true]);
```

**Environment Variables:**
```php
putenv('DOMAIN_CHECK_TOKEN=testing-domain-token');
$_ENV['DOMAIN_CHECK_TOKEN'] = 'testing-domain-token';
$_SERVER['DOMAIN_CHECK_TOKEN'] = 'testing-domain-token';
```

**Tenancy Context:**
```php
tenancy()->initialize($tenant);
// Perform tenant-specific action
tenancy()->end();
```

**Fake Timers:**
```php
$this->travel(5)->minutes();
// Test time-dependent logic
$this->travelBack();
```

---

*Testing analysis: 2026-06-25*
