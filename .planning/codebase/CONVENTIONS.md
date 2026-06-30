# Coding Conventions

**Analysis Date:** 2026-06-25

## Naming Patterns

**Files:**
- Classes: PascalCase — `TenantController`, `CreateTenantAction`, `InstallTenantModule`
- Config files: snake_case — `tenancy.php`, `cloudflare.php`
- Blade views: dot notation with lowercase — `tenant.index`, `module-requests.index`, `components.page-header`

**Functions/Methods:**
- Actions: `execute()` method in Action classes — `CreateTenantAction::execute()`
- Controller methods: verb-noun — `toggleStatus()`, `store()`, `approve()`, `reject()`
- Service methods: descriptive verbs — `markModuleOperationRunning()`, `ensureConfiguredSuperAdminExists()`
- Private helpers: verb-adjective — `ensureConfigured()`, `extractError()`, `normalize()`

**Variables:**
- PHP: camelCase — `$tenantDomain`, `$moduleInfo`, `$normalizedDomain`
- Blade views: snake_case — `@php $tenant_id = ...`, `$module_requests`

**Types/Enums:**
- Constants: SCREAMING_SNAKE_CASE — `ACTION_INSTALL`, `OP_STATUS_RUNNING`
- Enum keys: PascalCase (as per CLAUDE.md) — not heavily used in this codebase

**Properties:**
- Typed properties with constructor promotion — `private CreateTenantAction $createTenant`
- No nullable type used for required dependencies — `?string` only for optional parameters

## Code Style

**Formatting:**
- Tool: Laravel Pint (vendor/bin/pint)
- Run with: `vendor/bin/pint --dirty --format agent` after PHP file changes
- Indentation: 4 spaces (PSR-12 standard)
- Line length: No strict limit, but reasonable line breaks for readability

**Key Style Rules:**
- Curly braces on control structures even for single-line bodies (per CLAUDE.md)
- PHP 8 constructor property promotion — `public function __construct(private TenantDomainService $domainService) {}`
- Explicit return types on all methods — `public function store(): RedirectResponse`
- Typed parameters — `function execute(array $data): Tenant`
- PHPDoc blocks over inline comments
- Array shapes in PHPDoc when complexity warrants

**Linting:**
- Laravel Pint is the enforced formatter
- No ESLint for PHP; JavaScript files follow basic Vite/npm conventions

## Import Organization

**Order (PSR-12 compliant):**
1. Namespace declaration
2. Use statements (grouped by: own app classes, Laravel/Illuminate, Stancl/Third-party, PHP built-ins)
3. Blank line before class

**Example Pattern:**
```php
namespace App\Http\Controllers;

use App\Actions\Tenants\CreateTenantAction;
use App\Actions\Tenants\UpdateTenantAction;
use App\Http\Requests\TenantStoreRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
```

**Path Aliases:**
- No aliases; standard Laravel autoloading via composer.json `autoload`
- Classes resolved via PSR-4: `App\` → `app/`, `Tests\` → `tests/`

## Error Handling

**Patterns:**
- Controllers: Redirect with flash message — `return back()->with('error', $e->getMessage())`
- Actions: Let exceptions propagate to controller try/catch
- Services: Throw `RuntimeException` with descriptive messages — `CloudflareService::ensureConfigured()`
- Jobs: `failed()` method captures exceptions and logs them
- Form Requests: Custom validation messages via `messages()` method

**Error Response Shape:**
```php
// Controllers
return back()->withInput()->with('error', $e->getMessage());

// With success
return redirect()->route('tenants.index')->with('success', 'Tenant created successfully.');
```

**Side Effects Documentation:**
- PHPDoc blocks on methods with side effects include `Side effects:` comment
- Examples: `TenantController::store()`, `InstallTenantModule::handle()`, `CloudflareService::createHostname()`

## Logging

**Framework:** Laravel's built-in `logger()` facade (Log channel)

**Patterns:**
- `logger()->info('message.', ['context' => $value])` — structured context arrays
- `logger()->warning('message.', [...])` — non-fatal issues
- `logger()->error('message.', [...])` — failed operations
- Used in Jobs (install/uninstall failures) and Services (Cloudflare issues)

**Example:**
```php
logger()->warning('InstallTenantModule skipped: missing tenant/module.', [
    'tenant_id' => $this->tenantId,
    'module_id' => $this->moduleId,
]);
```

## Comments

**When to Comment:**
- PHPDoc blocks on all public methods (required)
- `Side effects:` annotations on methods with external writes
- Inline comments for complex logic only (rarely used)

**PHPDoc Pattern:**
```php
/**
 * Approve a pending module request.
 *
 * Side effects:
 * - Writes review state to the central module_requests table.
 */
public function approve(ModuleRequest $moduleRequest): RedirectResponse
```

**Avoid:**
- Inline comments explaining obvious code
- TODO/FIXME markers (none found in codebase)
- Commented-out code blocks

## Function Design

**Size:**
- Controller methods: 10-30 lines typical
- Action classes: Single responsibility, `execute()` method 20-40 lines
- Service methods: 15-40 lines; private helpers are 5-15 lines

**Parameters:**
- Type-hinted always — `string $tenantId`, `?string $module = null`
- Arrays typed as `array` (not `array<string, mixed>` in signatures, but documented in PHPDoc)
- Nullable for optional params: `?string $path = null`

**Return Values:**
- Always explicit — `Tenant`, `void`, `RedirectResponse`, `View`, `array`
- Actions return the affected model: `CreateTenantAction::execute() → Tenant`
- Services return arrays for complex data — `CloudflareService::mapStatuses() → array`

## Module Design

**Exports:**
- No barrel files; standard Laravel PSR-4 autoloading
- Classes resolved by namespace from `app/` or `tests/`

**Controller Pattern:**
- Extend base `Controller` class
- Use dependency injection via constructor promotion
- Delegate to Action classes for write operations
- Use Form Requests for validation
- Return `View` or `RedirectResponse` types

**Action Pattern:**
- Single `execute()` method
- Injected dependencies via constructor
- No static methods
- Return the affected model

**Service Pattern:**
- Public methods for business logic
- Private helpers for internal operations
- Configuration validation in `ensureConfigured()` or similar
- No state stored between calls (stateless services)

**Job Pattern:**
- Implements `ShouldQueue`
- Uses `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels` traits
- Has `$tries` and `$timeout` properties
- `handle()` method receives dependencies via injection
- `failed()` method for terminal failures
- `backoff()` for retry schedule

## Middleware Design

**Pattern:**
```php
public function handle(Request $request, Closure $next, string $module): Response
{
    $tenant = tenant();
    
    if (! $tenant) {
        abort(404, 'Tenant context is required.');
    }
    
    // Guard logic
    
    return $next($request);
}
```

**Key Points:**
- Always check tenant context exists before accessing tenant data
- Use `abort()` for guard failures (403 for authorization, 404 for missing context)
- Parameterized middleware (e.g., `module:customer`)

## Policy Design

**Pattern:**
```php
public function viewAny(User $user): bool
{
    return $user->hasRole('admin') || $user->hasPermission('module.read');
}
```

**Approach:**
- Check role OR permission (admin has all permissions)
- Named abilities: `viewAny`, `request`, `install`, `uninstall`
- No complex logic; simple boolean checks

## Blade View Conventions

**Layout:**
- Master layout: `layouts/app.blade.php`
- Components: `resources/views/components/` directory
- Namespaced view paths: `tenant.index`, `modules.create`, `module-requests.index`

**Component Usage:**
- Reusable components: `x-page-header`, `x-primary-button`, `x-data-table`
- Livewire for dynamic components (Livewire v4)

---

*Convention analysis: 2026-06-25*
