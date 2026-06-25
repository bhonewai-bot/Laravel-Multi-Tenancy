# Coding Conventions

**Analysis Date:** 2026-06-25

## Naming Patterns

**Files:**
- PascalCase for all PHP classes and Blade components: `CreateTenantAction.php`, `TenantController.php`
- Kebab-case for Blade views and routes: `tenant/index.blade.php`, `tenants.index`
- Config files follow Laravel standard: `cloudflare.php` in `/config`

**Classes:**
- Services: VerbNoun pattern with clear domain purpose: `TenantDomainService`, `CloudflareService`
- Actions: VerbNoun pattern for single-purpose operations: `CreateTenantAction`, `SyncCloudflareDomainAction`
- Controllers: NounController pattern: `TenantController`, `DomainController`
- Form Requests: NounStoreRequest or NounUpdateRequest: `TenantStoreRequest`, `TenantUpdateRequest`
- Policies: NounPolicy: `ModuleRequestPolicy`, `RolePolicy`

**Methods:**
- camelCase with clear verb: `execute()`, `normalize()`, `shouldRetry()`
- Return type declarations on all methods: `public function normalize(string $domain): string`
- Boolean methods start with `is`, `has`, or `can`: `isCentralDomain()`, `hasPermission()`, `canUseAsTenantDomain()`

**Variables:**
- camelCase: `$tenantDomain`, `$cloudflareService`, `$normalizedDomain`
- Use descriptive names that explain purpose, not abbreviations

**Types:**
- PHP 8.2+ features used throughout: constructor property promotion, return type hints
- PHPDoc blocks with array shapes for complex structures
- Nullable types with `?string`, `?int` notation

## Code Style

**Formatting:**
- Laravel Pint for PHP formatting: `vendor/bin/pint --dirty --format agent`
- 4-space indentation (configured in `.editorconfig`)
- UTF-8 charset, LF line endings
- Trim trailing whitespace and insert final newline

**Linting:**
- No dedicated linting config detected (uses Pint defaults)
- Follow Laravel Pint ruleset (PSR-12 + Laravel conventions)

**Blade Components:**
- Anonymous components with `@props` directive
- Dark mode support using class-based toggles: `dark:bg-[#101016]`
- Merge attributes in components: `{{ $attributes->merge([...]) }}`

## Import Organization

**Order:**
1. PHP native functions
2. Application namespaces (App\)
3. Laravel framework namespaces (Illuminate\)
4. Package namespaces (Stancl\Tenancy\, Mockery\)
5. PHP built-in classes

**Grouping:**
- Separate groups by blank lines
- Alphabetical within groups (Laravel convention)

**Path Aliases:**
- PSR-4 autoload mapping: `App\` → `app/`
- No custom aliases configured

## PHP Code Patterns

**Constructor Property Promotion:**
```php
public function __construct(
    private CreateTenantAction $createTenant,
    private UpdateTenantAction $updateTenant,
) {}
```

**Method Signatures:**
```php
public function normalize(string $domain): string
public function isVerifiedCustomDomain(Tenant $tenant, string $domain): bool
public function execute(array $data): Tenant
```

**Eloquent Usage:**
- Use query builder and Eloquent fluent syntax: `Domain::query()->where(...)->first()`
- Always use model factories in tests, not manual creation
- Local scopes for reusable constraints
- Relationship return type declarations: `BelongsTo`, `HasMany`

**Config Access:**
- Use `config()` helper consistently: `config('cloudflare.api.token')`
- Fail fast with RuntimeException when required config is missing

## Error Handling

**Exception Strategy:**
- Throw RuntimeException for domain logic errors with descriptive messages
- Implement `failed()` method on jobs for queue failures
- Log errors with structured context arrays

**Patterns:**
```php
throw new RuntimeException('Cloudflare integration is disabled.');

// In Jobs
public function failed(Throwable $exception): void
{
    logger()->error('Job failed.', [
        'domain_id' => $this->domainId,
        'error' => $exception->getMessage(),
    ]);
}
```

**Validation:**
- Form Request classes for all input validation
- Custom validation messages via `messages()` method
- Authorization via `authorize()` method checking user permissions

## Documentation

**PHPDoc Blocks:**
- Required on all public methods
- Document side effects explicitly in multi-line blocks
- Use PHPDoc type hints for arrays: `@return array<string, string>`
- Document complex logic with inline comments explaining "why", not "what"

**Example:**
```php
/**
 * Side effects:
 * - Performs an outbound HTTP request to Cloudflare.
 * - Writes hostname status to the domain model.
 */
public function createHostname(string $hostname): array
```

## Authorization & Security

**Policies:**
- Check roles and permissions: `$user->hasRole('admin') || $user->hasPermission('module.read')`
- Permission keys use dot notation: `module.read`, `module.request`, `user.manage`
- Apply policies via middleware or Gates

**Middleware:**
- Custom middleware for tenant isolation: `EnsureTenantRole`, `EnsureTenantPermission`, `EnsureModuleInstalled`
- Verify domain ownership before allowing access
- Validate tokens using environment variables

**Input Validation:**
- Never trust user input - always validate through Form Requests
- Use Laravel validation rules, not manual checks
- Sanitize domain names via normalizer methods

## Module Organization

**Services Directory:**
- Single responsibility per service class
- Services handle business logic and external integrations
- Constructor injection for dependencies

**Actions Directory:**
- Single-purpose, stateless operations
- Compose multiple services for complex workflows
- Return Eloquent models or primitives

**Jobs Directory:**
- Implement `ShouldQueue` for async processing
- Define `$tries`, `$timeout`, and retry logic
- Handle failures explicitly in `failed()` method

## State Management

**Tenancy:**
- Use `tenancy()->initialize($tenant)` for request context
- Always call `tenancy()->end()` in test tearDown
- Domain-based tenant resolution via middleware

**Test State:**
- Use `RefreshDatabase` trait for database isolation
- Use `DatabaseMigrations` for complex scenarios needing migration structure
- Manual DB inserts via `DB::table()` when factories aren't available

---

*Convention analysis: 2026-06-25*
