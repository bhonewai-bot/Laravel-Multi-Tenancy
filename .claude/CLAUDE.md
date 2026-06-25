<!-- GSD:project-start source:PROJECT.md -->

## Project

**TenantSmith — Security Hardening**

TenantSmith is a Laravel multi-tenancy platform that lets a central admin provision tenant organizations, upload module packages, and manage domain verification via Cloudflare. Each tenant gets isolated databases with role-based access control.

This milestone hardens critical security gaps discovered during a codebase audit — blocking issues that prevent production deployment.

**Core Value:** **Every tenant database and module operation is properly authorized and isolated.** No unauthorized user can provision tenants or execute code.

### Constraints

- **Tech stack**: PHP 8.3, Laravel 12, Livewire 4, Alpine.js 3, Tailwind 3, MySQL 8.0 — must stay within existing stack
- **Authority**: Central admin identified by `CENTRAL_SUPERADMIN_EMAIL` / `CENTRAL_SUPERADMIN_PASSWORD` config — use existing env vars
- **Approach**: Educational — solo developer learning from each fix. Prefer clear, maintainable solutions over clever ones
- **Deployment**: Must be deployable after these 3 fixes (ignoring VPS public IP concern)

<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->

## Technology Stack

## Languages

- PHP 8.3 - Backend application logic, controllers, services, models, middleware, Livewire components, queued jobs
- JavaScript (ES modules) - Frontend interactivity via Alpine.js and Livewire client-side bootstrapping
- Blade - Server-side templating for all views (`resources/views/`)
- CSS (Tailwind) - Styling via utility-first CSS framework (`resources/css/app.css`)

## Runtime

- PHP 8.3 with FPM (production via Docker image `php:8.3-fpm`)
- Node.js 20 (CI) / npm for frontend asset compilation
- Docker Compose stack: PHP-FPM + Nginx + Caddy 2.8 + MySQL 8.0 + phpMyAdmin + queue worker
- Composer 2 - PHP dependency management; lockfile present at `composer.lock`
- npm - JavaScript dependency management; lockfile present at `package-lock.json`

## Frameworks

- Laravel 12 (`laravel/framework ^12.0`) - Application framework; uses the streamlined Laravel 11+ directory structure (no `app/Http/Kernel.php`, no `app/Console/Kernel.php`)
- Livewire 4 (`livewire/livewire ^4.2`) - Reactive server-driven UI components; configured in `config/livewire.php`
- Alpine.js 3 (`alpinejs ^3.4.2`) - Client-side interactivity bundled through Livewire
- Stancl Tenancy v3 (`stancl/tenancy ^3.9`) - Database-per-tenant multi-tenancy with domain-based identification
- nwidart/laravel-modules v12 (`nwidart/laravel-modules ^12.0`) - Module system for tenant-installable features
- PHPUnit 11 (`phpunit/phpunit ^11.5.3`) - Test framework
- Vite 7 (`vite ^7.0.7`) - Frontend build tool
- Laravel Pint (`laravel/pint ^1.24`) - PHP code formatting (run with `vendor/bin/pint --dirty --format agent`)
- Laravel Sail (`laravel/sail ^1.41`) - Docker development environment
- Laravel Pail (`laravel/pail ^1.2.2`) - Real-time log viewer
- concurrently (`concurrently ^9.0.1`) - Parallel process runner for `composer dev` script

## Key Dependencies

- `stancl/tenancy ^3.9` - Core multi-tenancy engine; database-per-tenant isolation, tenant bootstrappers, domain identification
- `nwidart/laravel-modules ^12.0` - Module packaging and lifecycle; tenant modules are ZIP-packaged and installed per-tenant
- `livewire/livewire ^4.2` - All interactive UI is server-rendered via Livewire components
- `laravel/mcp ^0.8.1` - Laravel MCP server for AI-assisted development tooling
- `laravel/boost ^2.4` (dev) - MCP tools for database queries, schema inspection, docs search
- `laravel/telescope ^5.18` (dev) - Application diagnostics and debugging; gated by `TELESCOPE_ALLOWED_EMAILS`
- `laravel/breeze ^2.3` (dev) - Authentication scaffolding (used as starter, custom auth built on top)
- `blade-ui-kit/blade-heroicons ^2.7` - Icon components for Blade views
- `symfony/css-selector ^7.4` + `symfony/dom-crawler ^7.4` - HTML parsing for product import from Lazada/Shopee URLs

## Configuration

- `.env` - Active environment configuration (gitignored)
- `.env.example` - Reference template
- `.env.production.example` - Production-specific reference
- Key env var groups:
- `vite.config.js` - Vite build configuration (Laravel plugin, entry points)
- `tailwind.config.js` - Tailwind CSS configuration (content paths, custom brand colors, design tokens, shadows)
- `postcss.config.js` - PostCSS with Tailwind and Autoprefixer plugins
- `phpunit.xml` - PHPUnit test runner configuration
- `.editorconfig` - Editor settings (UTF-8, LF, 4-space indent)
- `config/tenancy.php` - Stancl tenancy configuration (UUID generator, database prefix, bootstrappers, cache/filesystem tenancy)
- `config/cloudflare.php` - Cloudflare Custom Hostnames API configuration
- `config/database.php` - Database connections (sqlite, mysql, mariadb, pgsql, sqlsrv) and Redis
- `config/queue.php` - Queue connections (database, redis, SQS, sync, deferred, background, failover)
- `config/cache.php` - Cache stores (database, redis, file, array, memcached, dynamodb)
- `config/mail.php` - Mail transports (SMTP, SES, Postmark, Resend, log)
- `config/logging.php` - Log channels (stack, daily, slack, ops_alert, papertrail)
- `config/session.php` - Session driver (database default)
- `config/filesystems.php` - Storage disks (local, public, s3)
- `config/auth.php` - Authentication guards, providers, and central admin configuration
- `config/services.php` - Third-party service credentials (Postmark, Resend, SES, Slack, ScrapingBee)
- `config/livewire.php` - Livewire component locations, namespaces, page layout
- `config/telescope.php` - Telescope diagnostics (disabled by default via env)

## Platform Requirements

- PHP 8.3+ with extensions: pdo_mysql, bcmath, intl, zip, gd (freetype + jpeg)
- Node.js 20+ with npm
- MySQL 8.0 (via Docker Compose)
- Docker and Docker Compose (recommended)
- Composer 2
- Docker-based deployment (PHP-FPM + Nginx + Caddy + MySQL 8.0)
- Laravel Cloud deployment supported via `cloud` CLI (`deploying-laravel-cloud` skill)
- Caddy 2.8 with on-demand TLS for dynamic tenant domain SSL
- MySQL 8.0 primary database
- Database queue driver for background jobs

## Dev Scripts

<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->

## Conventions

## Naming Patterns

- Classes: PascalCase — `TenantController`, `CreateTenantAction`, `InstallTenantModule`
- Config files: snake_case — `tenancy.php`, `cloudflare.php`
- Blade views: dot notation with lowercase — `tenant.index`, `module-requests.index`, `components.page-header`
- Actions: `execute()` method in Action classes — `CreateTenantAction::execute()`
- Controller methods: verb-noun — `toggleStatus()`, `store()`, `approve()`, `reject()`
- Service methods: descriptive verbs — `markModuleOperationRunning()`, `ensureConfiguredSuperAdminExists()`
- Private helpers: verb-adjective — `ensureConfigured()`, `extractError()`, `normalize()`
- PHP: camelCase — `$tenantDomain`, `$moduleInfo`, `$normalizedDomain`
- Blade views: snake_case — `@php $tenant_id = ...`, `$module_requests`
- Constants: SCREAMING_SNAKE_CASE — `ACTION_INSTALL`, `OP_STATUS_RUNNING`
- Enum keys: PascalCase (as per CLAUDE.md) — not heavily used in this codebase
- Typed properties with constructor promotion — `private CreateTenantAction $createTenant`
- No nullable type used for required dependencies — `?string` only for optional parameters

## Code Style

- Tool: Laravel Pint (vendor/bin/pint)
- Run with: `vendor/bin/pint --dirty --format agent` after PHP file changes
- Indentation: 4 spaces (PSR-12 standard)
- Line length: No strict limit, but reasonable line breaks for readability
- Curly braces on control structures even for single-line bodies (per CLAUDE.md)
- PHP 8 constructor property promotion — `public function __construct(private TenantDomainService $domainService) {}`
- Explicit return types on all methods — `public function store(): RedirectResponse`
- Typed parameters — `function execute(array $data): Tenant`
- PHPDoc blocks over inline comments
- Array shapes in PHPDoc when complexity warrants
- Laravel Pint is the enforced formatter
- No ESLint for PHP; JavaScript files follow basic Vite/npm conventions

## Import Organization

- No aliases; standard Laravel autoloading via composer.json `autoload`
- Classes resolved via PSR-4: `App\` → `app/`, `Tests\` → `tests/`

## Error Handling

- Controllers: Redirect with flash message — `return back()->with('error', $e->getMessage())`
- Actions: Let exceptions propagate to controller try/catch
- Services: Throw `RuntimeException` with descriptive messages — `CloudflareService::ensureConfigured()`
- Jobs: `failed()` method captures exceptions and logs them
- Form Requests: Custom validation messages via `messages()` method
- PHPDoc blocks on methods with side effects include `Side effects:` comment
- Examples: `TenantController::store()`, `InstallTenantModule::handle()`, `CloudflareService::createHostname()`

## Logging

- `logger()->info('message.', ['context' => $value])` — structured context arrays
- `logger()->warning('message.', [...])` — non-fatal issues
- `logger()->error('message.', [...])` — failed operations
- Used in Jobs (install/uninstall failures) and Services (Cloudflare issues)

## Comments

- PHPDoc blocks on all public methods (required)
- `Side effects:` annotations on methods with external writes
- Inline comments for complex logic only (rarely used)
- Inline comments explaining obvious code
- TODO/FIXME markers (none found in codebase)
- Commented-out code blocks

## Function Design

- Controller methods: 10-30 lines typical
- Action classes: Single responsibility, `execute()` method 20-40 lines
- Service methods: 15-40 lines; private helpers are 5-15 lines
- Type-hinted always — `string $tenantId`, `?string $module = null`
- Arrays typed as `array` (not `array<string, mixed>` in signatures, but documented in PHPDoc)
- Nullable for optional params: `?string $path = null`
- Always explicit — `Tenant`, `void`, `RedirectResponse`, `View`, `array`
- Actions return the affected model: `CreateTenantAction::execute() → Tenant`
- Services return arrays for complex data — `CloudflareService::mapStatuses() → array`

## Module Design

- No barrel files; standard Laravel PSR-4 autoloading
- Classes resolved by namespace from `app/` or `tests/`
- Extend base `Controller` class
- Use dependency injection via constructor promotion
- Delegate to Action classes for write operations
- Use Form Requests for validation
- Return `View` or `RedirectResponse` types
- Single `execute()` method
- Injected dependencies via constructor
- No static methods
- Return the affected model
- Public methods for business logic
- Private helpers for internal operations
- Configuration validation in `ensureConfigured()` or similar
- No state stored between calls (stateless services)
- Implements `ShouldQueue`
- Uses `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels` traits
- Has `$tries` and `$timeout` properties
- `handle()` method receives dependencies via injection
- `failed()` method for terminal failures
- `backoff()` for retry schedule

## Middleware Design

- Always check tenant context exists before accessing tenant data
- Use `abort()` for guard failures (403 for authorization, 404 for missing context)
- Parameterized middleware (e.g., `module:customer`)

## Policy Design

- Check role OR permission (admin has all permissions)
- Named abilities: `viewAny`, `request`, `install`, `uninstall`
- No complex logic; simple boolean checks

## Blade View Conventions

- Master layout: `layouts/app.blade.php`
- Components: `resources/views/components/` directory
- Namespaced view paths: `tenant.index`, `modules.create`, `module-requests.index`
- Reusable components: `x-page-header`, `x-primary-button`, `x-data-table`
- Livewire for dynamic components (Livewire v4)

<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->

## Architecture

## System Overview

```text

```

## Component Responsibilities

| Component | Responsibility | File |
|-----------|----------------|------|
| Bootstrap | App configuration, middleware, routing, exception handling | `bootstrap/app.php` |
| TenancyServiceProvider | Wires Stancl tenancy events, route loading, middleware priority | `app/Providers/TenancyServiceProvider.php` |
| AppServiceProvider | Registers policies, boots central admin, configures Livewire update route | `app/Providers/AppServiceProvider.php` |
| HostResolver | Determines central vs tenant host, checks domain verification state | `app/Support/HostResolver.php` |
| AppHome | Resolves post-auth landing path based on tenant context | `app/Support/AppHome.php` |
| RejectInvalidTenantHost | Blocks central hosts from tenant routes, rejects unverified tenants | `app/Http/Middleware/RejectInvalidTenantHost.php` |
| EnsureModuleInstalled | Gates tenant routes behind module installation check | `app/Http/Middleware/EnsureModuleInstalled.php` |
| EnsureTenantPermission | Enforces permission-based authorization on tenant routes | `app/Http/Middleware/EnsureTenantPermission.php` |
| EnsureTenantRole | Enforces role-based authorization on tenant routes | `app/Http/Middleware/EnsureTenantRole.php` |
| CloudflareService | HTTP client for Cloudflare Custom Hostnames API | `app/Services/CloudflareService.php` |
| DomainCloudflareSyncService | Orchestrates Cloudflare hostname creation/refresh and local state persistence | `app/Services/DomainCloudflareSyncService.php` |
| TenantDomainService | Domain normalization, verification logic, central domain protection | `app/Services/TenantDomainService.php` |
| TenantModuleInstaller | Runs tenant-scoped migrations and seeders for module install/uninstall | `app/Services/TenantModuleInstaller.php` |
| TenantModuleRegistry | Tracks module install state and operation status on central tenant record | `app/Services/TenantModuleRegistry.php` |
| CentralAdminService | Ensures super-admin exists at boot time (central context only) | `app/Services/CentralAdminService.php` |
| ModuleZipInspector | Validates and extracts uploaded module ZIP packages safely | `app/Services/ModuleZipInspector.php` |
| CreateTenantAction | Creates tenant + domain records and syncs with Cloudflare | `app/Actions/Tenants/CreateTenantAction.php` |
| UpdateTenantAction | Updates tenant records and syncs domain changes with Cloudflare | `app/Actions/Tenants/UpdateTenantAction.php` |
| SyncCloudflareDomainAction | Delegates Cloudflare hostname sync and async polling dispatch | `app/Actions/Tenants/SyncCloudflareDomainAction.php` |
| TenantController | CRUD for tenants from central admin surface | `app/Http/Controllers/TenantController.php` |
| ModuleController | Central module catalog management (upload, list, toggle) | `app/Http/Controllers/ModuleController.php` |
| ModuleRequestController (central) | Central review/approval of tenant module requests | `app/Http/Controllers/ModuleRequestController.php` |
| DomainController (tenant) | Tenant custom domain CRUD, Cloudflare status checks, DNS verification | `app/Http/Controllers/Tenant/DomainController.php` |
| ModuleRequestController (tenant) | Tenant-side module request, install/uninstall with polling watch | `app/Http/Controllers/Tenant/ModuleRequestController.php` |
| RoleController (tenant) | Tenant-scoped role management | `app/Http/Controllers/Tenant/RoleController.php` |
| UserController (tenant) | Tenant-scoped user management | `app/Http/Controllers/Tenant/UserController.php` |
| DomainCheckController | Caddy on-demand TLS domain validation endpoint | `app/Http/Controllers/DomainCheckController.php` |
| CloudflareHostnameChallengeController | Cloudflare custom hostname challenge verification | `app/Http/Controllers/CloudflareHostnameChallengeController.php` |

## Pattern Overview

- Tenants are identified by domain (primary subdomain `{tenant_id}.{central_domain}` or custom verified domain)
- Complete database isolation per tenant (separate MySQL databases, `tenant_{id}` naming)
- Central database holds tenant metadata, module catalog, and domain records
- Module system allows ZIP upload, central review, and per-tenant installation via async jobs
- Cloudflare Custom Hostnames API provides automatic SSL and domain activation for custom domains
- RBAC authorization model: Feature > Permission > Role > User hierarchy

## Layers

- Purpose: Serves the platform administration interface
- Location: `routes/web.php`
- Contains: Tenant CRUD, module catalog management, module request review, design system, profile
- Depends on: Eloquent models, Actions, Services
- Used by: Admin users via `{central_domain}`
- Purpose: Serves tenant-specific management interfaces
- Location: `routes/tenant.php`
- Contains: Domain management, module request/install, user/role management, dashboard
- Depends on: Eloquent models, Services, Jobs, tenant middleware stack
- Used by: Tenant users via `{tenant_id}.{central_domain}` or verified custom domain
- Purpose: Request filtering for host validation, tenancy initialization, authorization
- Location: `app/Http/Middleware/`
- Contains: `RejectInvalidTenantHost`, `EnsureModuleInstalled`, `EnsureTenantRole`, `EnsureTenantPermission`
- Depends on: `HostResolver`, `App\Models\User`, Stancl middleware
- Used by: Route definitions in `routes/tenant.php` and `routes/web.php`
- Purpose: Orchestrates multi-step domain operations (create tenant, sync Cloudflare)
- Location: `app/Actions/Tenants/`
- Contains: `CreateTenantAction`, `UpdateTenantAction`, `SyncCloudflareDomainAction`
- Depends on: Models, Services
- Used by: Controllers
- Purpose: Business logic and external integration encapsulation
- Location: `app/Services/`
- Contains: Cloudflare, domain, module, and admin services
- Depends on: Models, Config, HTTP client
- Used by: Actions, Controllers, Jobs
- Purpose: Async execution of long-running tenant-scoped operations
- Location: `app/Jobs/`
- Contains: `InstallTenantModule`, `UninstallTenantModule`, `SyncPendingCloudflareDomain`
- Depends on: Services, Stancl tenancy facade
- Used by: Controllers dispatch to queue workers
- Purpose: Self-contained feature packages installable per-tenant
- Location: `Modules/Product/`
- Contains: Models, Livewire components, controllers, routes, migrations, seeders
- Depends on: Core app services, Stancl tenancy
- Used by: Tenant context when module is installed and active
- Purpose: Blade components and layouts for both central and tenant UIs
- Location: `resources/views/`
- Contains: Blade components, layouts, page views
- Depends on: TailwindCSS, Alpine.js, Livewire (for Product module)
- Used by: Controllers return views

## Data Flow

### Tenant Provisioning

### Tenant Request Lifecycle

### Module Installation Flow

### Domain Verification Flow (Cloudflare)

- Central state: Tenant metadata, domain records, module catalog, and RBAC stored in central database
- Tenant state: Tenant-scoped users, roles, permissions, and module-specific tables in per-tenant databases
- Module operation state: JSON columns (`installed_modules`, `module_operations`) on central tenant record
- Session-based auth: Standard Laravel session driver, no stateless tokens

## Key Abstractions

- Purpose: Isolates tenant data at the database, filesystem, and cache level
- Examples: `app/Providers/TenancyServiceProvider.php`, `config/tenancy.php`
- Pattern: Stancl Tenancy domain-based identification with per-tenant database provisioning
- Purpose: Installable feature packages that add functionality per-tenant
- Examples: `Modules/Product/`, `app/Services/TenantModuleInstaller.php`, `app/Services/TenantModuleRegistry.php`
- Pattern: ZIP upload to central catalog, async migration/seeding into tenant DBs, state tracked on central tenant record
- Purpose: Routes incoming requests to central or tenant context based on domain
- Examples: `app/Support/HostResolver.php`, `app/Http/Middleware/RejectInvalidTenantHost.php`
- Pattern: Local DB lookup only (no external API calls during routing)
- Purpose: Controls access to features and routes based on user roles and permissions
- Examples: `app/Models/User.php`, `app/Models/Role.php`, `app/Models/Permission.php`, `app/Models/Feature.php`
- Pattern: Feature > Permission > Role > User with dot-notation permission keys (e.g., `domain.read`, `module.install`)
- Purpose: Manages async domain activation and SSL provisioning
- Examples: `app/Services/CloudflareService.php`, `app/Services/DomainCloudflareSyncService.php`
- Pattern: Sync write to local state, background polling for status updates

## Entry Points

- Location: `routes/web.php`
- Triggers: HTTP requests to `{central_domain}`
- Responsibilities: Tenant management, module catalog, module request review, profile, design system
- Location: `routes/tenant.php`
- Triggers: HTTP requests to `{tenant_id}.{central_domain}` or verified custom domain
- Responsibilities: Tenant dashboard, domain management, module request/install, user/role management, profile
- Location: `routes/console.php`
- Triggers: Artisan CLI commands
- Responsibilities: Scheduled tasks and console commands
- Location: `routes/auth.php` (loaded in both central and tenant contexts)
- Triggers: Login, registration, password reset, email verification
- Responsibilities: Laravel Breeze authentication scaffolding
- Location: `resources/js/app.js` (Alpine + Livewire boot), `AppServiceProvider::boot()` (route registration)
- Triggers: POST `/livewire/update`
- Responsibilities: Livewire component updates with tenant-aware middleware
- Location: `bootstrap/app.php`
- Triggers: Every HTTP request
- Responsibilities: Middleware registration, routing configuration, exception handling

## Architectural Constraints

- **Threading:** Standard Laravel synchronous request-response model. Queue workers process async jobs (module install/uninstall, Cloudflare sync polling). Single-process per request.
- **Global state:** `CentralAdminService::ensureConfiguredSuperAdminExists()` runs on every boot in central context, writing to the users table. This is intentional idempotent bootstrapping.
- **Circular imports:** No circular dependency chains detected. The module system (`Modules/Product/`) depends on core app services but not vice versa.
- **Tenancy isolation boundary:** All code in tenant routes must assume tenant context is active. Services like `TenantModuleInstaller` have explicit `WARNING` comments about calling them outside the correct context.
- **Domain-based identification:** Only domain-based and subdomain-based tenancy identification is active. Path-based and request-data identification middleware exist in the codebase but are not registered.
- **Central-tenant data split:** RBAC tables (roles, permissions, features) are tenant-scoped. User table is also tenant-scoped. Module catalog and domain records are central-only.

## Error Handling

- Controllers use try/catch with `back()->with('error', $message)` for user-facing errors
- Services throw `RuntimeException` for business logic failures
- Jobs implement `failed()` callback to persist failure state (e.g., `TenantModuleRegistry::markModuleOperationFailed()`)
- Cloudflare integration errors are captured as metadata on domain records rather than blocking operations
- `bootstrap/app.php` catches `TenantCouldNotBeIdentifiedOnDomainException` and returns 404

## Cross-Cutting Concerns

<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->

## Project Skills

| Skill | Description | Path |
|-------|-------------|------|
| diagnose-custom-domain | "Use when a tenant reports their custom domain is not working — returning 403, 404, or SSL errors. Covers diagnosing Cloudflare hostname status, domain verification state, DNS misconfiguration, and stuck background sync jobs. Do not use for general tenant setup or Cloudflare account-wide issues." | `.claude/skills/diagnose-custom-domain/SKILL.md` |
| laravel-best-practices | "Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns." | `.claude/skills/laravel-best-practices/SKILL.md` |
| livewire-development | "Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire." | `.claude/skills/livewire-development/SKILL.md` |
| mcp-development | "Use this skill for Laravel MCP development. Trigger when creating or editing MCP tools, resources, prompts, servers, or UI apps in Laravel projects. Covers: artisan make:mcp-* generators, routes/ai.php, Tool/Resource/Prompt/AppResource classes, schema validation, shouldRegister(), OAuth setup, URI templates, read-only attributes, MCP debugging, MCP UI apps, the x-mcp::app Blade component, createMcpApp(), default AppResource handle() auto-infers view from class name, Response::view(), AppMeta/Csp/Permissions/appMeta() configuration, #[RendersApp] attribute, Library enum for CDN libraries (Tailwind, Alpine), and host theming via CSS variables. Use this whenever the user mentions MCP apps, MCP UI, interactive MCP resources, styling MCP apps with Tailwind or Alpine, or building visual interfaces for AI agents." | `.claude/skills/mcp-development/SKILL.md` |
| multi-tenancy | "Apply when working on multi-tenancy: tenant creation, domain management, host resolution, middleware pipeline, Cloudflare SSL for SaaS, or database-per-tenant architecture. Covers stancl/tenancy v3 configuration, domain verification flows, and the RejectInvalidTenantHost middleware. For diagnosing broken custom domains in production, prefer diagnose-custom-domain." | `.claude/skills/multi-tenancy/SKILL.md` |
| tailwindcss-development | "Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS." | `.claude/skills/tailwindcss-development/SKILL.md` |
| tenantsmith-design | "Use when building or modifying UI components, layouts, or pages for TenantSmith. Covers the brand color palette, dark/light theme conventions, component patterns, typography, spacing rules, and the login page design system. Do not use for backend logic, database migrations, or API endpoints." | `.claude/skills/tenantsmith-design/SKILL.md` |
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->

## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:

- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->

## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
