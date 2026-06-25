<!-- refreshed: 2026-06-25 -->
# Architecture

**Analysis Date:** 2026-06-25

## System Overview

```text
┌──────────────────────────────────────────────────────────────────┐
│                      Reverse Proxy Layer                         │
│  Caddy (TLS termination + on-demand certs)                      │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  nginx (internal routing)                                   │  │
│  └────────────────────────┬───────────────────────────────────┘  │
└───────────────────────────┼──────────────────────────────────────┘
                            │
┌───────────────────────────┼──────────────────────────────────────┐
│                     Laravel Application                          │
│                                                                  │
│  ┌──────────────────────┐  ┌──────────────────────────────────┐  │
│  │   Central Context    │  │        Tenant Context             │  │
│  │   routes/web.php     │  │        routes/tenant.php          │  │
│  │                      │  │   (loaded after tenancy init)     │  │
│  │  ┌────────────────┐  │  │  ┌────────────────────────────┐  │  │
│  │  │ Tenant Mgmt    │  │  │  │ Domain Mgmt                │  │  │
│  │  │ Module Catalog │  │  │  │ Module Request/Install      │  │  │
│  │  │ Module Reviews │  │  │  │ User & Role Mgmt            │  │  │
│  │  │ Profile        │  │  │  │ Profile                     │  │  │
│  │  └────────────────┘  │  │  └────────────────────────────┘  │  │
│  └──────────────────────┘  └──────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────────┐│
│  │                   Service Layer                              ││
│  │  CloudflareService · TenantDomainService · TenantModuleInstaller ││
│  │  TenantModuleRegistry · CentralAdminService                  ││
│  │  DomainCloudflareSyncService · ModuleZipInspector            ││
│  └──────────────────────────┬───────────────────────────────────┘│
│                             │                                    │
│  ┌──────────────────────────┼───────────────────────────────────┐│
│  │                   Jobs (Queue)                                ││
│  │  InstallTenantModule · UninstallTenantModule                 ││
│  │  SyncPendingCloudflareDomain                                 ││
│  └──────────────────────────┬───────────────────────────────────┘│
└─────────────────────────────┼────────────────────────────────────┘
                              │
┌─────────────────────────────┼────────────────────────────────────┐
│  ┌──────────────────────┐   │   ┌─────────────────────────────┐  │
│  │  Central Database     │   │   │  Tenant Database (N)        │  │
│  │  - users              │   │   │  - users (tenant-scoped)    │  │
│  │  - tenants            │   │   │  - roles                    │  │
│  │  - domains            │   │   │  - permissions              │  │
│  │  - modules            │   │   │  - features                 │  │
│  │  - module_requests    │   │   │  - role_permissions         │  │
│  │  - cache/jobs         │   │   │  - cache/jobs               │  │
│  └──────────────────────┘   │   │  - module-specific tables   │  │
│                             │   └─────────────────────────────┘  │
│                    Stancl Tenancy Package                         │
└─────────────────────────────────────────────────────────────────┘
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

**Overall:** Domain-based multi-tenancy with a modular plugin architecture

**Key Characteristics:**
- Tenants are identified by domain (primary subdomain `{tenant_id}.{central_domain}` or custom verified domain)
- Complete database isolation per tenant (separate MySQL databases, `tenant_{id}` naming)
- Central database holds tenant metadata, module catalog, and domain records
- Module system allows ZIP upload, central review, and per-tenant installation via async jobs
- Cloudflare Custom Hostnames API provides automatic SSL and domain activation for custom domains
- RBAC authorization model: Feature > Permission > Role > User hierarchy

## Layers

**Central HTTP Layer:**
- Purpose: Serves the platform administration interface
- Location: `routes/web.php`
- Contains: Tenant CRUD, module catalog management, module request review, design system, profile
- Depends on: Eloquent models, Actions, Services
- Used by: Admin users via `{central_domain}`

**Tenant HTTP Layer:**
- Purpose: Serves tenant-specific management interfaces
- Location: `routes/tenant.php`
- Contains: Domain management, module request/install, user/role management, dashboard
- Depends on: Eloquent models, Services, Jobs, tenant middleware stack
- Used by: Tenant users via `{tenant_id}.{central_domain}` or verified custom domain

**Middleware Layer:**
- Purpose: Request filtering for host validation, tenancy initialization, authorization
- Location: `app/Http/Middleware/`
- Contains: `RejectInvalidTenantHost`, `EnsureModuleInstalled`, `EnsureTenantRole`, `EnsureTenantPermission`
- Depends on: `HostResolver`, `App\Models\User`, Stancl middleware
- Used by: Route definitions in `routes/tenant.php` and `routes/web.php`

**Action Layer:**
- Purpose: Orchestrates multi-step domain operations (create tenant, sync Cloudflare)
- Location: `app/Actions/Tenants/`
- Contains: `CreateTenantAction`, `UpdateTenantAction`, `SyncCloudflareDomainAction`
- Depends on: Models, Services
- Used by: Controllers

**Service Layer:**
- Purpose: Business logic and external integration encapsulation
- Location: `app/Services/`
- Contains: Cloudflare, domain, module, and admin services
- Depends on: Models, Config, HTTP client
- Used by: Actions, Controllers, Jobs

**Job Layer:**
- Purpose: Async execution of long-running tenant-scoped operations
- Location: `app/Jobs/`
- Contains: `InstallTenantModule`, `UninstallTenantModule`, `SyncPendingCloudflareDomain`
- Depends on: Services, Stancl tenancy facade
- Used by: Controllers dispatch to queue workers

**Module Layer:**
- Purpose: Self-contained feature packages installable per-tenant
- Location: `Modules/Product/`
- Contains: Models, Livewire components, controllers, routes, migrations, seeders
- Depends on: Core app services, Stancl tenancy
- Used by: Tenant context when module is installed and active

**View/Component Layer:**
- Purpose: Blade components and layouts for both central and tenant UIs
- Location: `resources/views/`
- Contains: Blade components, layouts, page views
- Depends on: TailwindCSS, Alpine.js, Livewire (for Product module)
- Used by: Controllers return views

## Data Flow

### Tenant Provisioning

1. Admin submits tenant form on `/tenants/create` (`app/Http/Controllers/TenantController.php:52`)
2. `TenantController::store()` delegates to `CreateTenantAction::execute()` (`app/Actions/Tenants/CreateTenantAction.php:16`)
3. `Tenant::create()` writes to central `tenants` table (`app/Actions/Tenants/CreateTenantAction.php:20`)
4. Domain record created via `$tenant->domains()->create()` (`app/Actions/Tenants/CreateTenantAction.php:28`)
5. `SyncCloudflareDomainAction` calls `DomainCloudflareSyncService::sync()` to register with Cloudflare (`app/Actions/Tenants/SyncCloudflareDomainAction.php:47`)
6. If async polling enabled, `SyncPendingCloudflareDomain` job dispatched (`app/Actions/Tenants/SyncCloudflareDomainAction.php:53`)
7. Stancl `TenantCreated` event fires, triggering `CreateDatabase` > `MigrateDatabase` > `SeedDatabase` pipeline (`app/Providers/TenancyServiceProvider.php:43`)

### Tenant Request Lifecycle

1. Request arrives at Caddy, passes to nginx, hits Laravel
2. `HostResolver::findTenantDomain()` checks central domains table (`app/Support/HostResolver.php:27`)
3. `RejectInvalidTenantHost` middleware blocks central hosts, unknown hosts, and unverified domains (`app/Http/Middleware/RejectInvalidTenantHost.php:22`)
4. Stancl `InitializeTenancyByDomain` middleware identifies tenant, switches DB connection (`app/Providers/TenancyServiceProvider.php:182`)
5. `PreventAccessFromCentralDomains` middleware blocks central-only routes (`app/Providers/TenancyServiceProvider.php:179`)
6. Route handlers execute in tenant context; all DB writes target tenant database
7. `Tenancy::end()` or middleware reverts to central context after response

### Module Installation Flow

1. Tenant user clicks install on `/modules` page (`app/Http/Controllers/Tenant/ModuleRequestController.php:113`)
2. `ModuleRequestController::install()` validates approval, starts operation via `TenantModuleRegistry` (`app/Http/Controllers/Tenant/ModuleRequestController.php:131`)
3. `InstallTenantModule::dispatch()` queues the job (`app/Http/Controllers/Tenant/ModuleRequestController.php:138`)
4. Job rehydrates tenant, initializes tenancy via `Tenancy::initialize()` (`app/Jobs/InstallTenantModule.php:77`)
5. `TenantModuleInstaller::install()` runs module migrations on tenant DB (`app/Services/TenantModuleInstaller.php:44`)
6. `TenantModuleRegistry::markInstalled()` persists install state to central tenant record (`app/Services/TenantModuleInstaller.php:77`)
7. Tenant UI polls operation status via `watch_module_id` query parameter until terminal state

### Domain Verification Flow (Cloudflare)

1. Tenant adds custom domain at `/domains/create` (`app/Http/Controllers/Tenant/DomainController.php:100`)
2. Domain persisted to central `domains` table with `verified_at = null`
3. `DomainCloudflareSyncService::sync()` creates Cloudflare custom hostname (`app/Services/DomainCloudflareSyncService.php:30`)
4. Background job `SyncPendingCloudflareDomain` polls Cloudflare up to 15 times at 2-minute intervals (`app/Jobs/SyncPendingCloudflareDomain.php:58`)
5. Once `cf_hostname_status = active` and `cf_ssl_status = active`, `verified_at` is set (`app/Services/DomainCloudflareSyncService.php:73`)
6. Tenant can also manually check status via `/domains/{domain}/check-status`

**State Management:**
- Central state: Tenant metadata, domain records, module catalog, and RBAC stored in central database
- Tenant state: Tenant-scoped users, roles, permissions, and module-specific tables in per-tenant databases
- Module operation state: JSON columns (`installed_modules`, `module_operations`) on central tenant record
- Session-based auth: Standard Laravel session driver, no stateless tokens

## Key Abstractions

**Tenancy Boundary:**
- Purpose: Isolates tenant data at the database, filesystem, and cache level
- Examples: `app/Providers/TenancyServiceProvider.php`, `config/tenancy.php`
- Pattern: Stancl Tenancy domain-based identification with per-tenant database provisioning

**Module System:**
- Purpose: Installable feature packages that add functionality per-tenant
- Examples: `Modules/Product/`, `app/Services/TenantModuleInstaller.php`, `app/Services/TenantModuleRegistry.php`
- Pattern: ZIP upload to central catalog, async migration/seeding into tenant DBs, state tracked on central tenant record

**Host Resolution:**
- Purpose: Routes incoming requests to central or tenant context based on domain
- Examples: `app/Support/HostResolver.php`, `app/Http/Middleware/RejectInvalidTenantHost.php`
- Pattern: Local DB lookup only (no external API calls during routing)

**RBAC Authorization:**
- Purpose: Controls access to features and routes based on user roles and permissions
- Examples: `app/Models/User.php`, `app/Models/Role.php`, `app/Models/Permission.php`, `app/Models/Feature.php`
- Pattern: Feature > Permission > Role > User with dot-notation permission keys (e.g., `domain.read`, `module.install`)

**Cloudflare State Machine:**
- Purpose: Manages async domain activation and SSL provisioning
- Examples: `app/Services/CloudflareService.php`, `app/Services/DomainCloudflareSyncService.php`
- Pattern: Sync write to local state, background polling for status updates

## Entry Points

**Central Web Routes:**
- Location: `routes/web.php`
- Triggers: HTTP requests to `{central_domain}`
- Responsibilities: Tenant management, module catalog, module request review, profile, design system

**Tenant Web Routes:**
- Location: `routes/tenant.php`
- Triggers: HTTP requests to `{tenant_id}.{central_domain}` or verified custom domain
- Responsibilities: Tenant dashboard, domain management, module request/install, user/role management, profile

**Console Routes:**
- Location: `routes/console.php`
- Triggers: Artisan CLI commands
- Responsibilities: Scheduled tasks and console commands

**Auth Routes:**
- Location: `routes/auth.php` (loaded in both central and tenant contexts)
- Triggers: Login, registration, password reset, email verification
- Responsibilities: Laravel Breeze authentication scaffolding

**Livewire Update Endpoint:**
- Location: `resources/js/app.js` (Alpine + Livewire boot), `AppServiceProvider::boot()` (route registration)
- Triggers: POST `/livewire/update`
- Responsibilities: Livewire component updates with tenant-aware middleware

**Application Bootstrap:**
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

**Strategy:** Layered exception handling with explicit failure state persistence

**Patterns:**
- Controllers use try/catch with `back()->with('error', $message)` for user-facing errors
- Services throw `RuntimeException` for business logic failures
- Jobs implement `failed()` callback to persist failure state (e.g., `TenantModuleRegistry::markModuleOperationFailed()`)
- Cloudflare integration errors are captured as metadata on domain records rather than blocking operations
- `bootstrap/app.php` catches `TenantCouldNotBeIdentifiedOnDomainException` and returns 404

## Cross-Cutting Concerns

**Logging:** Structured logging via `Log::info/warning/error` with contextual arrays (tenant_id, domain, operation status). Cloudflare sync has dedicated `logCloudflareSync()` helper in `DomainCloudflareSyncService`.
**Validation:** Laravel Form Request classes for central CRUD (`TenantStoreRequest`, `TenantUpdateRequest`, `RoleStoreRequest`, etc.) and inline `Validator::make()` in tenant controllers. `ModuleZipInspector` validates ZIP structure before extraction.
**Authentication:** Laravel Breeze with session driver. Central admin bootstrapped from env vars. Auth routes loaded in both central and tenant contexts.
**Authorization:** Gate policies registered in `AppServiceProvider::boot()` for `ModuleRequest`, `User`, and `Role`. Tenant middleware aliases: `role`, `permission`, `module`.
**Frontend:** Blade templates with TailwindCSS, Alpine.js for interactivity, Livewire for Product module components. Vite bundler. Dark mode via Alpine `$store.theme`.

---

*Architecture analysis: 2026-06-25*
