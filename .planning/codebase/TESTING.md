# Testing Patterns

**Analysis Date:** 2026-06-25

## Test Framework

**Runner:**
- PHPUnit 11 (configured in `phpunit.xml`)
- Laravel's testing wrapper (`Illuminate\Foundation\Testing\TestCase`)

**Assertion Library:**
- PHPUnit assertions + Laravel HTTP testing helpers (`assertDatabaseHas`, `assertSessionHas`, etc.)

**Run Commands:**
```bash
php artisan test --compact                              # Run all tests
php artisan test --compact tests/Feature/AuthTest.php   # Run specific file
php artisan test --compact --filter=testName            # Filter by test name
```

## Test File Organization

**Location:**
- Tests live in `tests/` directory (Laravel default)
- Feature tests: `tests/Feature/`
- Unit tests: `tests/Unit/`

**Naming:**
- Files: PascalCase with `Test.php` suffix — `TenancyE2EFlowTest.php`, `AuthenticationTest.php`
- Methods: `test_` prefix with snake_case — `test_central_admin_can_create_tenant_and_domain()`
- Classes: PascalCase, descriptive — `TenantOnboardingTest`, `DomainCheckTest`

**Structure:**
```
tests/
├── TestCase.php                    # Base test case with setUp()
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── CentralAdminBootstrapTest.php
│   │   ├── EmailVerificationTest.php
│   │   └── PasswordConfirmationTest.php
│   ├── Tenancy/
│   │   ├── TenancyE2EFlowTest.php
│   │   ├── TenantOnboardingTest.php
│   │   ├── TenantBootstrapSeederTest.php
│   │   ├── DomainCheckTest.php
│   │   ├── TenantDomainLifecycleTest.php
│   │   ├── HostAccessPolicyTest.php
│   │   └── CloudflareDomainStatusSyncTest.php
│   ├── ExampleTest.php
│   └── ProfileTest.php
└── Unit/
    └── ExampleTest.php
```

**Total: 16 test files, 51 test methods**

## Test Structure

**Base TestCase:**
```php
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $centralDomain = config('tenancy.central_domains.0')
            ?: parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST)
            ?: 'localhost';

        $this->withServerVariables([
            'HTTP_HOST' => $centralDomain,
        ]);
    }
}
```

**Suite Organization:**
```php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_central_admin_can_create_tenant_and_domain(): void
    {
        // Arrange
        $admin = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($admin)
            ->post('/tenants', [...]);

        // Assert
        $response
            ->assertRedirect('/tenants')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', [...]);
    }
}
```

**Patterns:**
- `RefreshDatabase` trait for tests needing clean database (most tests)
- `DatabaseMigrations` trait for tests needing schema without resetting (E2E tests)
- `tearDown()` to close Mockery and end tenancy context
- `actingAs()` for authentication in tests

## Mocking

**Framework:** Mockery (integrated with Laravel testing)

**Patterns:**
```php
use Mockery;
use App\Services\CloudflareService;

// Create mock
$cloudflare = Mockery::mock(CloudflareService::class);

// Set expectations
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

// Bind to service container
$this->app->instance(CloudflareService::class, $cloudflare);
```

**What to Mock:**
- External API calls (Cloudflare, payment gateways, etc.)
- Services with side effects (filesystem, HTTP, etc.)
- Any dependency not under test control

**What NOT to Mock:**
- Eloquent models (use factories instead)
- Database operations
- Internal Laravel services
- The class under test

**Teardown Pattern:**
```php
protected function tearDown(): void
{
    tenancy()->end();
    Mockery::close();
    parent::tearDown();
}
```

## Fixtures and Factories

**Test Data:**
- Use model factories — `User::factory()->create()`
- Custom factory states for variations — `$this->unverified()` on UserFactory
- Manual creation when factories don't exist — `Module::create([...])`

**Factory Pattern:**
```php
use Database\Factories\UserFactory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

**Manual Creation (for models without factories):**
```php
$tenant = Tenant::create([
    'id' => 't100',
    'name' => 'Tenant 100',
    'email' => 'tenant100@example.com',
]);

$domain = $tenant->domains()->create([
    'domain' => 't100.app.localhost',
    'verification_code' => null,
    'verified_at' => now(),
]);
```

**Helper Methods in Tests:**
```php
private function createTenantWithPrimaryDomain(string $tenantId): Tenant
{
    $tenant = Event::fakeFor(fn () => Tenant::create([
        'id' => $tenantId,
        'name' => "Tenant {$tenantId}",
        'email' => "{$tenantId}@example.com",
    ]));

    $tenant->domains()->create([
        'domain' => "{$tenantId}.app.localhost",
        'verification_code' => null,
        'verified_at' => now(),
    ]);

    return $tenant->fresh();
}
```

**Location:**
- Factory classes: `database/factories/`
- Test helpers: Private methods within test classes

## Coverage

**Requirements:** None enforced (no coverage thresholds)

**View Coverage:**
```bash
php artisan test --coverage --min=80  # If needed
```

**PHPUnit Config:**
- Source directory: `app/` (in `phpunit.xml`)
- Testing environment: SQLite in-memory (`:memory:`)

## Test Types

**Unit Tests:**
- Location: `tests/Unit/`
- Scope: Single class methods, isolated
- Status: Minimal (only `ExampleTest`)

**Feature Tests:**
- Location: `tests/Feature/`
- Scope: HTTP requests, controller logic, middleware
- Most common type in codebase

**E2E Tests:**
- Location: `tests/Feature/Tenancy/`
- Examples: `TenancyE2EFlowTest`
- Scope: Multi-step flows (onboarding → provisioning → module install)
- Use `DatabaseMigrations` instead of `RefreshDatabase`
- Manual tenant initialization/teardown

## Common Patterns

**Auth Testing:**
```php
$user = User::factory()->create();

$response = $this
    ->actingAs($user)
    ->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

$this->assertAuthenticated();
$response->assertRedirect(AppHome::path());
```

**HTTP Request Testing:**
```php
$response = $this
    ->actingAs($admin)
    ->from("http://app.localhost/modules")
    ->post("http://{$tenantHost}/modules/request", [
        'module_id' => $module->id,
    ]);

$response->assertSessionHas('success');
```

**Database Assertion Testing:**
```php
$this->assertDatabaseHas('tenants', ['id' => $tenantId]);
$this->assertDatabaseHas('domains', [
    'tenant_id' => $tenantId,
    'domain' => $tenantDomain,
]);
$this->assertDatabaseMissing('tenants', ['id' => 'deleted']);
```

**Event Testing:**
```php
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantCreated;

Event::fake([TenantCreated::class]);

// ... perform action ...

Event::assertDispatched(TenantCreated::class);
```

**Session/Flash Message Testing:**
```php
$response->assertSessionHas('success');
$response->assertSessionHas('error');
$response->assertSessionHasErrors(['email']);
```

**Tenancy Testing:**
```php
use Stancl\Tenancy\Facades\Tenancy;

// Initialize tenant context
tenancy()->initialize($tenant);

// Run tenant operations...

// Always clean up
tenancy()->end();
```

**Module Guard Testing (E2E):**
```php
// Test guard blocks access
$blocked = $this
    ->actingAs($tenantAdmin)
    ->get("http://{$tenantHost}/_e2e/module-guard-probe");
$blocked->assertForbidden();
tenancy()->end();

// Install module and retry
$tenant->setAttribute('installed_modules', ['customer']);
$tenant->save();

$allowed = $this
    ->actingAs($tenantAdmin)
    ->get("http://{$tenantHost}/_e2e/module-guard-probe");
$allowed->assertOk();
tenancy()->end();
```

**Async Job Testing:**
```php
use App\Jobs\InstallTenantModule;

// Dispatch and test state changes
dispatch(new InstallTenantModule($tenant->id, $module->id));

// Verify registry state
$installedModules = $tenant->fresh()->getAttribute('installed_modules') ?? [];
$this->assertContains('product', $installedModules);
```

**Custom Route Definition in Tests:**
```php
if (! Route::has('tenant.module.guard.probe')) {
    Route::middleware([
        'web',
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        EnsureVerifiedTenantDomain::class,
        'auth',
        'module:customer',
    ])->get('/_e2e/module-guard-probe', fn () => response('OK', 200))
        ->name('tenant.module.guard.probe');
}
```

## PHPUnit Configuration

**Environment Variables (phpunit.xml):**
```xml
<env name="APP_ENV" value="testing"/>
<env name="APP_URL" value="http://app.localhost"/>
<env name="TENANCY_CENTRAL_DOMAIN" value="app.localhost"/>
<env name="DOMAIN_CHECK_TOKEN" value="testing-domain-token"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="PULSE_ENABLED" value="false"/>
<env name="TELESCOPE_ENABLED" value="false"/>
```

**Key Points:**
- In-memory SQLite for speed
- Sync queue for synchronous job execution
- Array cache/session for isolation
- Disabled monitoring in tests

## Test Data Setup

**Central vs Tenant Databases:**
```php
private function createTenantAndSeedAdmin(string $tenantId): array
{
    // Create tenant in central database
    $tenant = $this->createTenantWithPrimaryDomain($tenantId);

    // Initialize tenancy context
    tenancy()->initialize($tenant);

    // Migrate tenant database
    Artisan::call('migrate', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--force' => true,
    ]);

    // Seed tenant data
    app(TenantBootstrapSeeder::class)->run();

    // Get tenant admin user
    $tenantAdmin = User::query()
        ->where('email', 'admin@example.com')
        ->firstOrFail();

    // End tenancy context
    tenancy()->end();

    return [$tenant->fresh(), $tenantAdmin];
}
```

## Running Specific Test Suites

**By Directory:**
```bash
php artisan test --compact tests/Feature/Auth/
php artisan test --compact tests/Feature/Tenancy/
```

**By Class:**
```bash
php artisan test --compact tests/Feature/Tenancy/TenantOnboardingTest.php
```

**By Method:**
```bash
php artisan test --compact --filter=test_central_admin_can_create_tenant_and_domain
```

---

*Testing analysis: 2026-06-25*
