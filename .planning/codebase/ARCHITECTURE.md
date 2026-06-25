<!-- refreshed: 2026-06-25 -->
# Architecture

**Analysis Date:** 2026-06-25

## System Overview

```text
                    Request Entry Point
                           |
                    bootstrap/app.php
                    (middleware + routing)
                           |
            +--------------+------------------+
            |              |                  |
     Central Domains   Host-Agnostic     Tenant Domains
     (web.php routes)  (CF challenge,    (tenant.php routes)
            |           domain-check)          |
            |              |                   |
            v              v                   v
  +------------------+  +-----------------+  +---------------------+
  | Central Layer    |  | Infrastructure  |  | Tenant Layer        |
  | (Admin Surface)  |  | Endpoints       |  | (Tenant Workspace)  |
  +------------------+  +-----------------+  +---------------------+
            |              |                   |
            v              v                   v
  +-----------------------------------------------------------+
  |              Domain Isolation Layer                         |
  |  HostResolver / RejectInvalidTenantHost / CloudflareSync   |
  +-----------------------------------------------------------+
            |
            v
  +-----------------------------------------------------------+
  |                  Stancl Tenancy Engine                      |
  |  TenancyServiceProvider / InitializeTenancyByDomain        |
  +-----------------------------------------------------------+
            |
            v
  +-----------------------------------------------------------+
  |              Database-Per-Tenant Storage                    |
  |  Central DB (tenants, domains, modules, module_requests)   |
  |  Tenant DB  (users, roles, permissions, features)          |
  +-----------------------------------------------------------+
```

## Component Responsibilities

| Component | Responsibility | File |
|-----------|----------------|------|
| `HostResolver` | Determines if host is central, tenant, verified, or unknown (read-only, no CF calls) | `app/Support/HostResolver.php` |
| `AppHome` | Resolves post-auth landing path (`/tenants` for central, `/dashboard` for tenant) | `app/Support/AppHome.php` |
| `RejectInvalidTenantHost` | Middleware enforcing host policy: central=404, unknown=404, unverified=403 | `app/Http/Middleware/RejectInvalidTenantHost.php` |
| `EnsureModuleInstalled` | Middleware blocking tenant routes for uninstalled modules | `app/Http/Middleware/EnsureModuleInstalled.php` |
| `EnsureTenantPermission` | Middleware enforcing RBAC permission checks on tenant routes | `app/Http/Middleware/EnsureTenantPermission.php` |
| `EnsureTenantRole` | Middleware enforcing role-based checks on tenant routes | `app/Http/Middleware/EnsureTenantRole.php` |
| `CloudflareService` | HTTP client for Cloudflare Custom Hostnames API (create, get, map statuses) | `app/Services/CloudflareService.php` |
| `DomainCloudflareSyncService` | Orchestrates CF sync and persists verification state to `domains` table | `app/Services/DomainCloudflareSyncService.php` |
| `TenantDomainService` | Domain normalization, primary subdomain detection, TXT verification | `app/Services/TenantDomainService.php` |
| `TenantModuleInstaller` | Runs module migrations/seeding inside active tenant DB context | `app/Services/TenantModuleInstaller.php` |
| `TenantModuleRegistry` | Tracks installed modules and operation state on central tenant record | `app/Services/TenantModuleRegistry.php` |
| `ModuleZipInspector` | Validates and extracts module ZIP packages into `Modules/` directory | `app/Services/ModuleZipInspector.php` |
| `CentralAdminService` | Bootstraps super-admin user on central DB during app boot | `app/Services/CentralAdminService.php` |
| `CreateTenantAction` | Creates tenant + domain + Cloudflare sync in one operation | `app/Actions/Tenants/CreateTenantAction.php` |
| `UpdateTenantAction` | Updates tenant and re-syncs Cloudflare domain state | `app/Actions/Tenants/UpdateTenantAction.php` |
| `SyncCloudflareDomainAction` | Triggers CF hostname provisioning for a tenant domain | `app/Actions/Tenants/SyncCloudflareDomainAction.php` |
| `InstallTenantModule` | Queued job: initializes tenancy, runs installer, tracks status | `app/Jobs/InstallTenantModule.php` |
| `UninstallTenantModule` | Queued job: initializes tenancy, runs uninstaller, tracks status | `app/Jobs/UninstallTenantModule.php` |
| `SyncPendingCloudflareDomain` | Queued job: polls CF activation state up to 15 times at 2-min intervals | `app/Jobs/SyncPendingCloudflareDomain.php` |
| `AddTenantContext` | Monolog processor appending tenant_id, host, context to every log line | `app/Logging/AddTenantContext.php` |
| `TenancyServiceProvider` | Registers tenancy events, routes, middleware priority | `app/Providers/TenancyServiceProvider.php` |
| `AppServiceProvider` | Registers policies, boots CentralAdminService, sets Livewire update route | `app/Providers/AppServiceProvider.php` |
| `DashboardController` | Dual dashboard: central stats or tenant stats based on `tenant()` context | `app/Http/Controllers/DashboardController.php` |
| `TenantController` | CRUD for tenants from central admin surface | `app/Http/Controllers/TenantController.php` |
| `ModuleController` | Central module catalog management (upload ZIP, toggle active) | `app/Http/Controllers/ModuleController.php` |
| `ModuleRequestController` | Central review of tenant module requests (approve/reject) | `app/Http/Controllers/ModuleRequestController.php` |
| `DomainController` (Tenant) | Tenant-side domain CRUD, CF sync, verification | `app/Http/Controllers/Tenant/DomainController.php` |
| `ModuleRequestController` (Tenant) | Tenant-side module request, install, uninstall with watch/poll UI | `app/Http/Controllers/Tenant/ModuleRequestController.php` |
| `RoleController` (Tenant) | Tenant-scoped role + permission CRUD | `app/Http/Controllers/Tenant/RoleController.php` |
| `UserController` (Tenant) | Tenant-scoped user CRUD with last-admin protection | `app/Http/Controllers/Tenant/UserController.php` |
| `DomainCheckController` | Caddy on-demand TLS validation endpoint | `app/Http/Controllers/DomainCheckController.php` |
| `CloudflareHostnameChallengeController` | Serves CF HTTP validation challenge tokens | `app/Http/Controllers/CloudflareHostnameChallengeController.php` |
| `ProductServiceProvider` | Module service provider for Product module (nwidart/laravel-modules) | `Modules/Product/app/Providers/ProductServiceProvider.php` |

## Pattern Overview

**Overall:** Database-per-tenant multi-tenancy with domain-based host resolution

**Key Characteristics:**
- Tenants are isolated by separate MySQL databases (`tenant{uuid}`)
- Domain-based identification: host header determines which tenant serves the request
- Central database holds cross-tenant metadata (tenants, domains, modules, module_requests)
- Tenant databases hold tenant-scoped data (users, roles, permissions, features)
- Cloudflare SSL for SaaS provisions custom domains asynchronously
- Module system allows per-tenant feature installation with migrations/seeding
- Action pattern for complex write operations (`CreateTenantAction`, `UpdateTenantAction`)
- Service layer encapsulates external integrations and complex business rules
- Queued jobs handle async operations (module install/uninstall, CF polling)
- RBAC without external packages: custom Role/Permission/Feature models with pivot table

## Layers

**Routing & Middleware:**
- Purpose: Request classification and tenant context initialization
- Location: `bootstrap/app.php`, `routes/web.php`, `routes/tenant.php`
- Contains: Route definitions, middleware aliases, exception handling
- Depends on: `HostResolver`, `RejectInvalidTenantHost`, stancl/tenancy middleware
- Used by: All HTTP requests

**Controllers:**
- Purpose: HTTP request handling, validation, response generation
- Location: `app/Http/Controllers/`, `app/Http/Controllers/Tenant/`
- Contains: Standard Laravel controllers, resource controllers
- Depends on: Actions, Services, Models, Policies, Form Requests
- Used by: Route definitions

**Actions:**
- Purpose: Orchestrated multi-step write operations
- Location: `app/Actions/Tenants/`
- Contains: `CreateTenantAction`, `UpdateTenantAction`, `SyncCloudflareDomainAction`
- Depends on: Models, Services
- Used by: Controllers (injected via constructor)

**Services:**
- Purpose: Business logic, external integrations, state management
- Location: `app/Services/`
- Contains: Cloudflare integration, module management, domain management, admin bootstrapping
- Depends on: Models, external APIs, config
- Used by: Actions, Controllers, Jobs, Middleware

**Jobs:**
- Purpose: Asynchronous processing for long-running or deferred operations
- Location: `app/Jobs/`
- Contains: Module install/uninstall, CF domain polling
- Depends on: Services, Models, stancl/tenancy facade
- Used by: Controllers (dispatched)

**Models:**
- Purpose: Data access, relationships, domain logic
- Location: `app/Models/`
- Contains: Tenant, Domain, Module, ModuleRequest, User, Role, Permission, Feature
- Depends on: Eloquent, stancl/tenancy concerns
- Used by: Everything above

**Policies:**
- Purpose: Authorization logic for model operations
- Location: `app/Policies/`
- Contains: ModuleRequestPolicy, RolePolicy, UserPolicy
- Depends on: User model, role/permission checks
- Used by: Controllers via `$this->authorize()`

**Middleware:**
- Purpose: Request-level guards for host validation, module checks, RBAC
- Location: `app/Http/Middleware/`
- Contains: RejectInvalidTenantHost, EnsureModuleInstalled, EnsureTenantPermission, EnsureTenantRole
- Depends on: `HostResolver`, Models
- Used by: Route definitions (registered in `bootstrap/app.php`)

**Views (Blade):**
- Purpose: Server-rendered UI with Alpine.js interactivity
- Location: `resources/views/`
- Contains: Layouts, components, page views for central and tenant contexts
- Depends on: Blade components, Alpine.js, Tailwind CSS
- Used by: Controllers (return views)

**Modules:**
- Purpose: Self-contained feature packages installable per-tenant
- Location: `Modules/Product/`
- Contains: Controllers, Livewire components, Models, Services, routes, migrations, views
- Depends on: stancl/tenancy, nwidart/laravel-modules
- Used by: Tenant routes via `module:product` middleware

## Data Flow

### Primary Request Path (Central Domain)

1. HTTP request arrives at central domain (e.g., `app.localhost`)
2. `bootstrap/app.php` routes central domains to `routes/web.php` (`bootstrap/app.php:36-39`)
3. `RejectInvalidTenantHost` middleware is NOT applied (central routes only)
4. Controller handles request against central database tables
5. Response returned

### Primary Request Path (Tenant Domain)

1. HTTP request arrives at tenant domain (e.g., `t001.app.localhost` or `custom.com`)
2. `RejectInvalidTenantHost` middleware runs first (`routes/tenant.php:29`)
3. `HostResolver::isCentralHost()` returns false for tenant host (`app/Support/HostResolver.php:12`)
4. `HostResolver::findTenantDomain()` queries central `domains` table (`app/Support/HostResolver.php:26`)
5. `HostResolver::canServeTenantHost()` checks `verified_at` or primary subdomain status (`app/Support/HostResolver.php:42`)
6. `InitializeTenancyByDomain` switches DB connection to `tenant{uuid}` (`routes/tenant.php:31`)
7. Controller runs against tenant database
8. `Tenancy::end()` reverts to central context

### Tenant Creation Flow

1. Central admin POSTs to `TenantController::store()` (`app/Http/Controllers/TenantController.php:51`)
2. Validated by `TenantStoreRequest` (`app/Http/Requests/TenantStoreRequest.php`)
3. `CreateTenantAction::execute()` orchestrates: (`app/Actions/Tenants/CreateTenantAction.php:15`)
   - Normalizes domain via `TenantDomainService`
   - Creates `Tenant` model (triggers `TenantCreated` event)
   - Stancl `CreateDatabase` job provisions `tenant{uuid}` DB
   - Stancl `MigrateDatabase` job runs `database/migrations/tenant/` migrations
   - Stancl `SeedDatabase` job runs tenant seeders
   - Creates `Domain` record linked to tenant
   - `SyncCloudflareDomainAction` calls `DomainCloudflareSyncService::sync()` if CF enabled
4. `SyncPendingCloudflareDomain` job polls CF every 2 min (up to 30 min) if async polling enabled

### Domain Verification Flow (Cloudflare SSL for SaaS)

1. Tenant creates domain via `DomainController::store()` (`app/Http/Controllers/Tenant/DomainController.php:100`)
2. Domain saved with `verified_at = null` to central `domains` table
3. If Cloudflare enabled: `DomainCloudflareSyncService::sync()` creates CF custom hostname (`app/Services/DomainCloudflareSyncService.php:30`)
4. Cloudflare provisions SSL certificate (async, 2-5 minutes)
5. `SyncPendingCloudflareDomain` polls CF status via `CloudflareService::getHostname()` (`app/Jobs/SyncPendingCloudflareDomain.php:46`)
6. When `cf_hostname_status = 'active'` AND `cf_ssl_status = 'active'`: `verified_at = now()` (`app/Services/DomainCloudflareSyncService.php:73`)
7. Domain can now serve traffic

### Module Install Flow

1. Tenant requests module via `ModuleRequestController::request()` (`app/Http/Controllers/Tenant/ModuleRequestController.php:67`)
2. Central admin approves via `ModuleRequestController::approve()` (`app/Http/Controllers/ModuleRequestController.php:32`)
3. Tenant triggers install via `ModuleRequestController::install()` (`app/Http/Controllers/Tenant/ModuleRequestController.php:113`)
4. `TenantModuleRegistry::startModuleOperation()` writes queued status to central tenant record
5. `InstallTenantModule` job dispatched (`app/Jobs/InstallTenantModule.php:24`)
6. Job initializes tenancy context: `Tenancy::initialize($tenant)` (`app/Jobs/InstallTenantModule.php:77`)
7. `TenantModuleInstaller::install()` runs module migrations + seeder on tenant DB (`app/Services/TenantModuleInstaller.php:43`)
8. `TenantModuleRegistry::markInstalled()` persists installed state to central tenant record
9. UI polls watch params to display progress/completion

**State Management:**
- Central state: Eloquent models on central MySQL (tenants, domains, modules, module_requests)
- Tenant state: Eloquent models on per-tenant MySQL (users, roles, permissions)
- Module install state: JSON columns on central tenant record (`installed_modules`, `module_operations`)
- Cloudflare state: Columns on central `domains` table (`cf_hostname_status`, `cf_ssl_status`, `verified_at`)
- Frontend state: Alpine.js `$store.sidebar` for sidebar collapse, `$store.theme` for dark mode
- Session-based flash messages for operation feedback

## Key Abstractions

**Central vs Tenant Database Split:**
- Purpose: Enforce data isolation between platform operators and tenants
- Central DB tables: `tenants`, `domains`, `modules`, `module_requests`, `users` (central admin), `cache`, `jobs`
- Tenant DB tables: `users`, `roles`, `permissions`, `features`, `role_permissions`, `cache`, `jobs`
- Models use `CentralConnection` trait for central-only tables: `Module`, `ModuleRequest`
- Tenant-scoped models (`User`, `Role`, `Permission`, `Feature`) have no connection trait -- they use the active tenant connection

**Module System:**
- Purpose: Allow tenants to install/remove features with isolated migrations and seeding
- Modules live in `Modules/{Name}/` following nwidart/laravel-modules convention
- Each module has: `config/config.php`, `database/migrations/`, `database/seeders/`, `app/` with Controllers/Models/Services, `resources/views/`, `routes/web.php`
- Module registration: ZIP upload via `ModuleZipInspector` -> `Modules/` directory -> `modules` table
- Module state tracking: `installed_modules` JSON array on tenant `data` column
- Module route protection: `module:product` middleware alias (`app/Http/Middleware/EnsureModuleInstalled.php`)
- Module auth protection: `auth` middleware + tenancy initialization in module routes (`Modules/Product/routes/web.php`)

**Host Resolution Pipeline:**
- Purpose: Classify incoming requests as central, valid tenant, or reject
- `HostResolver` is the single source of truth -- never calls Cloudflare
- Decision tree: isCentralHost? -> findTenantDomain -> canServeTenantHost (verified_at || primarySubDomain)
- Cloudflare sync must complete BEFORE a domain can serve traffic

## Entry Points

**HTTP Entry (`bootstrap/app.php`):**
- Location: `bootstrap/app.php`
- Triggers: All HTTP requests
- Responsibilities: Domain-based routing (central vs tenant), middleware registration, exception handling

**Central Routes (`routes/web.php`):**
- Location: `routes/web.php`
- Triggers: Requests to central domains (e.g., `app.localhost`)
- Responsibilities: Tenant CRUD, module catalog, module request review, dashboard, profile

**Tenant Routes (`routes/tenant.php`):**
- Location: `routes/tenant.php`
- Triggers: Requests to any non-central domain that passes `RejectInvalidTenantHost`
- Responsibilities: Tenant dashboard, users/roles management, domain management, module request/install

**Cloudflare Challenge (`CloudflareHostnameChallengeController`):**
- Location: `app/Http/Controllers/CloudflareHostnameChallengeController.php`
- Triggers: `/.well-known/cf-custom-hostname-challenge/{hostnameId}` (host-agnostic)
- Responsibilities: Serve HTTP validation tokens for CF SSL provisioning

**Domain Check (`DomainCheckController`):**
- Location: `app/Http/Controllers/DomainCheckController.php`
- Triggers: `/internal/domain-check` with shared token
- Responsibilities: Caddy on-demand TLS validation

**Livewire Update Route:**
- Location: Set in `AppServiceProvider::boot()` (`app/Providers/AppServiceProvider.php:49`)
- Triggers: Livewire AJAX requests to `/livewire/update`
- Responsibilities: Process Livewire component updates with full tenancy middleware stack

**Module Routes (`Modules/Product/routes/web.php`):**
- Location: `Modules/Product/routes/web.php`
- Triggers: Product module requests within tenant context
- Responsibilities: Product CRUD with `module:product` + `auth` + tenancy middleware

## Architectural Constraints

- **Threading:** Standard Laravel synchronous request lifecycle. Queue worker handles async jobs (`InstallTenantModule`, `UninstallTenantModule`, `SyncPendingCloudflareDomain`). Concurrency protection via `Cache::lock()` in `TenantModuleInstaller`.
- **Global state:** `TenantModuleRegistry` reads/writes JSON columns on central tenant record -- last-write-wins risk documented in `upsertModuleOperation()` (`app/Services/TenantModuleRegistry.php:200`).
- **Circular imports:** None detected. Dependencies flow downward: Routes -> Controllers -> Actions/Services -> Models.
- **Central domain routing:** Central routes are explicitly scoped to `$centralDomains` in `bootstrap/app.php:36-39`. Tenancy routes are loaded by `TenancyServiceProvider::mapRoutes()` which registers `routes/tenant.php` globally (gated by `RejectInvalidTenantHost`).
- **Module code execution:** Module ZIPs uploaded via `ModuleZipInspector` extract PHP files into `Modules/` which are autoloaded. Module code runs within the application context after extraction.
- **Shared views:** Both central and tenant contexts use views from `resources/views/`. The `dashboard.blade.php` renders differently based on `tenant()` presence. Tenant-specific sub-views are under `resources/views/tenant/`.
- **Database provisioning queue:** `TENANCY_PROVISIONING_QUEUE` env var controls whether tenant creation/deletion DB operations are queued or synchronous (`config/tenancy.php:53`).

## Anti-Patterns

### Direct Cloudflare Calls from Request Code

**What happens:** Some controller code calls `DomainCloudflareSyncService` directly during request handling.
**Why it's wrong:** Request-serving code should never call external APIs synchronously -- it blocks the response and creates timeout risk.
**Do this instead:** Cloudflare calls should happen via queued jobs (`SyncPendingCloudflareDomain`) or explicit sync actions triggered from controlled entry points. The `DomainController::store()` handles this with try/catch and error logging.

### Unprotected Module ZIP Extraction

**What happens:** `ModuleZipInspector` extracts ZIP contents into `Modules/` directory which is autoloaded.
**Why it's wrong:** A malicious ZIP could contain arbitrary PHP code that becomes part of the application codebase.
**Do this instead:** Add code review/approval step before extraction, or extract to a staging directory for manual review. The `module.json` validation is a start but insufficient.

### Dashboard Dual-Path Rendering

**What happens:** `DashboardController::index()` branches on `tenant()` to render different data sets into the same `dashboard.blade.php` view.
**Why it's wrong:** The view must conditionally render based on which context is active, making it fragile and hard to maintain.
**Do this instead:** Use separate views for central and tenant dashboards, or use a dedicated Livewire component that handles both contexts.

## Error Handling

**Strategy:** Domain-driven exceptions with try/catch at controller boundaries. External service failures are caught, logged, and surfaced via flash messages.

**Patterns:**
- Cloudflare API failures: Caught in `DomainController`, error logged with structured context, domain updated with `cf_error` field, redirect with error flash message
- Module install failures: `InstallTenantModule::failed()` writes error state to central tenant record and logs
- Tenant resolution failures: `TenantCouldNotBeIdentifiedOnDomainException` caught in `bootstrap/app.php:54` returning 404
- Missing config: `CloudflareService::ensureConfigured()` throws `RuntimeException` with missing env var names

## Cross-Cutting Concerns

**Logging:** `AddTenantContext` Monolog processor appends `tenant_id`, `host`, `context` (central/tenant), `request_id`, `job_id` to every log line (`app/Logging/AddTenantContext.php`)

**Validation:** Laravel Form Request classes for controllers (`TenantStoreRequest`, `TenantUpdateRequest`, `RoleStoreRequest`, `UserStoreRequest`, etc.). Manual `Validator::make()` in `DomainController::store()` for complex rules.

**Authorization:** Custom RBAC via `Role` -> `permissions` (pivot) -> `Feature` model chain. `User::hasPermission()` supports both simple and dot-notation permission keys (e.g., `domain.read`). Policies registered in `AppServiceProvider::boot()`. Middleware aliases: `role`, `permission`, `module`.

**Multi-tenancy Middleware Stack (highest priority first):**
1. `PreventAccessFromCentralDomains` (stancl)
2. `InitializeTenancyByDomain` (stancl)
3. `RejectInvalidTenantHost` (custom)
4. `EnsureModuleInstalled` (custom, alias: `module`)
5. `EnsureTenantPermission` (custom, alias: `permission`)
6. `EnsureTenantRole` (custom, alias: `role`)

**Design System:** TenantSmith design system with dual-theme (light/dark) using custom hex values for dark surfaces. Blade component library at `resources/views/components/`. Alpine.js for client-side interactivity. Tailwind CSS v3 with `brand-*` color aliases.

---

*Architecture analysis: 2026-06-25*
