# Codebase Structure

**Analysis Date:** 2026-06-25

## Directory Layout

```
Laravel-Multi-Tenancy/
├── app/                          # Core application code (central + tenant context)
│   ├── Actions/Tenants/          # Orchestration actions for tenant lifecycle
│   ├── Http/
│   │   ├── Controllers/          # Central admin controllers
│   │   │   ├── Auth/             # Authentication controllers (Breeze)
│   │   │   └── Tenant/           # Tenant-context controllers (domain, users, roles, modules)
│   │   ├── Middleware/            # Custom middleware (host validation, RBAC, module checks)
│   │   └── Requests/             # Form request validation classes
│   │       └── Auth/             # Auth-specific form requests
│   ├── Jobs/                     # Async queue jobs (module install/uninstall, Cloudflare sync)
│   ├── Livewire/                 # Empty - no Livewire components in main app
│   ├── Logging/                  # Custom logging configuration
│   ├── Models/                   # Eloquent models (Tenant, User, Domain, Module, RBAC)
│   ├── Policies/                 # Authorization policies
│   ├── Providers/                # Service providers (App + Tenancy)
│   ├── Services/                 # Business logic and external integrations
│   ├── Support/                  # Helper classes (HostResolver, AppHome)
│   └── View/Components/          # View component classes (AppLayout, GuestLayout)
├── bootstrap/                    # Application bootstrap configuration
│   └── app.php                   # Middleware, routing, exception handling
├── config/                       # Configuration files
├── database/
│   ├── factories/                # Model factories for testing
│   ├── migrations/               # Central database migrations
│   │   └── tenant/               # Tenant-scoped database migrations
│   └── seeders/                  # Database seeders
├── docker/                       # Docker infrastructure configs
│   ├── nginx/                    # Nginx reverse proxy configuration
│   └── prod/                     # Production Docker overrides
├── docs/                         # Project documentation
├── mcp-server/                   # MCP server configuration
├── Modules/                      # Installable module packages
│   └── Product/                  # Product module (self-contained)
│       ├── app/
│       │   ├── Http/Controllers/ # Module controllers
│       │   ├── Jobs/             # Module async jobs
│       │   ├── Livewire/         # Livewire components (ProductTable, forms)
│       │   ├── Models/           # Module models (Product)
│       │   ├── Providers/        # Module service providers
│       │   └── Services/         # Module business logic (import services)
│       ├── config/               # Module-specific config
│       ├── database/             # Module migrations and seeders
│       ├── resources/            # Module views, assets, JS, CSS
│       ├── routes/               # Module route definitions
│       └── tests/                # Module-specific tests
├── public/                       # Web root (index.php, assets)
├── resources/
│   ├── css/                      # TailwindCSS source
│   ├── js/                       # Alpine.js + Livewire bootstrap
│   └── views/                    # Blade templates
│       ├── auth/                 # Login, register, password views
│       ├── components/           # Reusable Blade components (30+ components)
│       ├── dashboard/            # Dashboard views
│       ├── design-system/        # Design system reference page
│       ├── errors/               # Error pages
│       ├── layouts/              # App layout, guest layout, navigation
│       ├── livewire/             # Livewire component views (empty in main app)
│       ├── module-requests/      # Central module request review
│       ├── modules/              # Central module catalog views
│       ├── profile/              # User profile views
│       └── tenant/               # Tenant management views
│           ├── domains/          # Domain CRUD and status views
│           ├── modules/          # Tenant module request/install views
│           ├── roles/            # Tenant role CRUD views
│           └── users/            # Tenant user CRUD views
├── routes/
│   ├── auth.php                  # Authentication routes (Breeze)
│   ├── console.php               # Artisan console routes
│   ├── tenant.php                # Tenant-scoped routes (loaded after tenancy init)
│   └── web.php                   # Central web routes
├── slides/                       # Presentation materials
├── storage/                      # Laravel storage (logs, cache, sessions)
├── tests/
│   ├── Feature/                  # Feature tests
│   │   ├── Auth/                 # Authentication tests
│   │   └── Tenancy/              # Tenancy lifecycle and domain tests
│   └── Unit/                     # Unit tests
├── .claude/skills/               # Claude Code skills
│   ├── deploying-laravel-cloud/
│   ├── diagnose-custom-domain/
│   ├── laravel-best-practices/
│   ├── livewire-development/
│   ├── mcp-development/
│   ├── multi-tenancy/
│   ├── tailwindcss-development/
│   └── tenantsmith-design/
├── .github/workflows/            # GitHub Actions CI/CD
├── .planning/                    # Planning and codebase analysis docs
├── docker-compose.yml            # Local development Docker stack
├── docker-compose.prod.yml       # Production Docker stack
├── DockerFile                    # Application Docker image
├── composer.json                 # PHP dependencies
├── package.json                  # Node.js dependencies
├── tailwind.config.js            # TailwindCSS configuration
├── vite.config.js                # Vite bundler configuration
├── phpunit.xml                   # PHPUnit configuration
├── modules_statuses.json         # Module activation states
├── CLAUDE.md                     # Claude Code project instructions
└── AGENTS.md                     # Agent configuration
```

## Directory Purposes

**`app/Http/Controllers/` (Central):**
- Purpose: Controllers for the platform administration surface (central context)
- Contains: `TenantController`, `ModuleController`, `ModuleRequestController`, `DashboardController`, `ProfileController`, and host-check endpoints
- Key files: `app/Http/Controllers/TenantController.php`, `app/Http/Controllers/ModuleController.php`

**`app/Http/Controllers/Tenant/`:**
- Purpose: Controllers that run inside tenant request context
- Contains: Domain management, module request/install, user/role CRUD
- Key files: `app/Http/Controllers/Tenant/DomainController.php`, `app/Http/Controllers/Tenant/ModuleRequestController.php`

**`app/Http/Middleware/`:**
- Purpose: Request processing middleware for host validation, module gating, and RBAC enforcement
- Contains: `RejectInvalidTenantHost`, `EnsureModuleInstalled`, `EnsureTenantRole`, `EnsureTenantPermission`
- Key files: `app/Http/Middleware/RejectInvalidTenantHost.php`, `app/Http/Middleware/EnsureModuleInstalled.php`

**`app/Http/Requests/`:**
- Purpose: Form request validation classes for input validation
- Contains: `TenantStoreRequest`, `TenantUpdateRequest`, `RoleStoreRequest`, `RoleUpdateRequest`, `UserStoreRequest`, `UserUpdateRequest`, `ProfileUpdateRequest`, `TenantUserUpdateRequest`
- Key files: `app/Http/Requests/TenantStoreRequest.php`

**`app/Actions/Tenants/`:**
- Purpose: Orchestration actions for multi-step tenant lifecycle operations
- Contains: `CreateTenantAction`, `UpdateTenantAction`, `SyncCloudflareDomainAction`
- Key files: `app/Actions/Tenants/CreateTenantAction.php`

**`app/Services/`:**
- Purpose: Business logic services and external integration clients
- Contains: Cloudflare integration, domain management, module installation/registry, admin bootstrapping, ZIP inspection
- Key files: `app/Services/TenantModuleInstaller.php`, `app/Services/DomainCloudflareSyncService.php`, `app/Services/CloudflareService.php`

**`app/Support/`:**
- Purpose: Lightweight helper classes for routing and context resolution
- Contains: `HostResolver` (domain lookup), `AppHome` (post-auth redirect)
- Key files: `app/Support/HostResolver.php`

**`app/Jobs/`:**
- Purpose: Async queue jobs for long-running or tenant-scoped operations
- Contains: `InstallTenantModule`, `UninstallTenantModule`, `SyncPendingCloudflareDomain`
- Key files: `app/Jobs/InstallTenantModule.php`, `app/Jobs/SyncPendingCloudflareDomain.php`

**`app/Models/`:**
- Purpose: Eloquent models for both central and tenant-scoped entities
- Contains: `Tenant`, `User`, `Domain`, `Module`, `ModuleRequest`, `Role`, `Permission`, `Feature`
- Key files: `app/Models/Tenant.php`, `app/Models/User.php`, `app/Models/Domain.php`

**`app/Policies/`:**
- Purpose: Authorization policies for Gate-based access control
- Contains: `ModuleRequestPolicy`, `UserPolicy`, `RolePolicy`
- Key files: `app/Policies/ModuleRequestPolicy.php`

**`app/Providers/`:**
- Purpose: Service providers that bootstrap application services
- Contains: `AppServiceProvider` (policies, admin boot, Livewire route), `TenancyServiceProvider` (Stancl events, routes, middleware priority)
- Key files: `app/Providers/TenancyServiceProvider.php`, `app/Providers/AppServiceProvider.php`

**`Modules/Product/`:**
- Purpose: Self-contained Product module - a complete installable module package
- Contains: Controllers, Livewire components, models, services (import from Shopee/Lazada), routes, migrations, tests
- Key files: `Modules/Product/module.json`, `Modules/Product/app/Livewire/ProductTable.php`, `Modules/Product/app/Services/Imports/ProductImportService.php`

**`resources/views/components/`:**
- Purpose: Reusable Blade UI components following the design system
- Contains: 30+ components including `sidebar`, `modal`, `dropdown`, `badge`, `data-table`, `stat-card`, `empty-state`, `alert`, `page-header`, `theme-toggle`, `user-menu`
- Key files: `resources/views/components/sidebar.blade.php`, `resources/views/components/modal.blade.php`, `resources/views/components/data-table.blade.php`

**`resources/views/layouts/`:**
- Purpose: Main layout templates for authenticated and guest views
- Contains: `app.blade.php` (sidebar + header + content), `guest.blade.php` (minimal auth layout), `navigation.blade.php`
- Key files: `resources/views/layouts/app.blade.php`

**`config/tenancy.php`:**
- Purpose: Stancl Tenancy package configuration
- Contains: Tenant model, domain model, database isolation settings, bootstrapper registration, migration parameters
- Key files: `config/tenancy.php`

**`config/cloudflare.php`:**
- Purpose: Cloudflare Custom Hostnames API integration settings
- Contains: API token, zone ID, timeout, retry settings, validation method, async polling toggle
- Key files: `config/cloudflare.php`

**`database/migrations/tenant/`:**
- Purpose: Migrations that run inside each tenant database
- Contains: Users, roles, permissions, features, role_permissions tables, plus cache/jobs tables
- Key files: `database/migrations/tenant/2026_03_01_064405_create_roles_table.php`

## Key File Locations

**Entry Points:**
- `bootstrap/app.php`: Application boot, middleware registration, routing configuration
- `bootstrap/providers.php`: Service provider registration
- `routes/web.php`: Central web routes
- `routes/tenant.php`: Tenant-scoped web routes (loaded after tenancy initialization)
- `routes/auth.php`: Authentication routes (Breeze)

**Configuration:**
- `config/tenancy.php`: Stancl Tenancy database isolation, bootstrappers, migration params
- `config/cloudflare.php`: Cloudflare API credentials and behavior
- `config/database.php`: Database connections (central SQLite default, MySQL for production)
- `config/auth.php`: Authentication guards, central admin credentials
- `modules_statuses.json`: Module activation flags (Customer, Product, Sale)

**Core Logic:**
- `app/Services/TenantModuleInstaller.php`: Module migration and seeding engine
- `app/Services/DomainCloudflareSyncService.php`: Cloudflare state synchronization
- `app/Support/HostResolver.php`: Central/tenant host resolution
- `app/Jobs/InstallTenantModule.php`: Async module installation with tenancy context
- `app/Http/Middleware/RejectInvalidTenantHost.php`: Host validation gate

**Testing:**
- `tests/Feature/Tenancy/`: Tenancy lifecycle, domain management, Cloudflare sync tests
- `tests/Feature/Auth/`: Authentication and central admin bootstrap tests
- `tests/TestCase.php`: Base test case

## Naming Conventions

**Files:**
- Models: Singular PascalCase (`Tenant.php`, `ModuleRequest.php`, `Domain.php`)
- Controllers: PascalCase, context-prefixed (`TenantController.php` for central, `Tenant/DomainController.php` for tenant)
- Services: PascalCase, descriptive suffix (`TenantModuleInstaller.php`, `CloudflareService.php`)
- Actions: PascalCase with Action suffix (`CreateTenantAction.php`, `SyncCloudflareDomainAction.php`)
- Middleware: PascalCase descriptive (`RejectInvalidTenantHost.php`, `EnsureModuleInstalled.php`)
- Jobs: PascalCase, verb-first (`InstallTenantModule.php`, `SyncPendingCloudflareDomain.php`)
- Policies: PascalCase with Policy suffix (`ModuleRequestPolicy.php`)
- Requests: PascalCase with Store/Update suffix (`TenantStoreRequest.php`)
- Blade components: kebab-case (`delete-modal.blade.php`, `page-header.blade.php`)
- Blade views: kebab-case for components, dot-path for page views (`tenant.domains.index`)

**Directories:**
- Standard Laravel: lowercase (`app/`, `config/`, `database/`, `resources/`, `routes/`)
- Module packages: PascalCase (`Modules/Product/`)
- Tenant-scoped migrations: `database/migrations/tenant/`
- Claude skills: kebab-case (`multi-tenancy/`, `laravel-best-practices/`)

## Where to Add New Code

**New Central Admin Feature:**
- Controller: `app/Http/Controllers/{FeatureName}Controller.php`
- Form Requests: `app/Http/Requests/{Entity}StoreRequest.php`, `app/Http/Requests/{Entity}UpdateRequest.php`
- Routes: Add to `routes/web.php` inside the `auth` middleware group
- Views: `resources/views/{feature-name}/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
- Tests: `tests/Feature/{FeatureName}Test.php`

**New Tenant Feature:**
- Controller: `app/Http/Controllers/Tenant/{FeatureName}Controller.php`
- Routes: Add to `routes/tenant.php` inside the `auth` middleware group
- Views: `resources/views/tenant/{feature-name}/`
- Authorization: Use `permission:resource.action` or `role:admin` middleware
- Tests: `tests/Feature/Tenancy/{FeatureName}Test.php`

**New Service/Integration:**
- Service class: `app/Services/{ServiceName}.php`
- Inject via constructor where needed
- If external API: encapsulate HTTP calls behind service interface

**New Async Operation:**
- Job class: `app/Jobs/{JobName}.php` implementing `ShouldQueue`
- Always implement `failed()` callback to persist failure state
- Use `TenantModuleRegistry` pattern for operation status tracking

**New Blade Component:**
- Component class: `app/View/Components/{ComponentName}.php` (if logic needed)
- View: `resources/views/components/{kebab-name}.blade.php`
- Reference: `resources/views/components/` for existing component patterns

**New Module:**
- Directory: `Modules/{ModuleName}/` with standard Laravel structure
- Manifest: `Modules/{ModuleName}/module.json` (name, alias, providers)
- Must include: `database/migrations/` for tenant-scoped migrations
- Register: Upload ZIP via central admin or manually add to `Modules/`
- Reference module: `Modules/Product/` for complete structure example

**New Tenant Migration:**
- Location: `database/migrations/tenant/`
- Naming: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`
- Run via: `php artisan tenants:migrate` or auto-runs on tenant creation

**New Authorization Policy:**
- Policy class: `app/Policies/{Model}Policy.php`
- Register in `app/Providers/AppServiceProvider::boot()` via `Gate::policy()`
- Reference: `app/Policies/ModuleRequestPolicy.php`

**New Form Request:**
- Location: `app/Http/Requests/{Context}/{Entity}{Action}Request.php`
- Use constructor property promotion for dependencies
- Reference: `app/Http/Requests/TenantStoreRequest.php`

## Special Directories

**`Modules/`:**
- Purpose: Installable module packages that extend tenant functionality
- Generated: Via ZIP upload through `ModuleController::store()` or manual placement
- Committed: Yes (module code is version-controlled alongside the main app)

**`storage/`:**
- Purpose: Laravel runtime storage (logs, cache, sessions, file uploads)
- Generated: Yes, at runtime
- Committed: No (except `storage/app/` structure)

**`bootstrap/cache/`:**
- Purpose: Framework cached configuration and routes
- Generated: Yes, via `php artisan config:cache`, `route:cache`
- Committed: No

**`docs/`:**
- Purpose: Project documentation including architecture decisions and operations guides
- Generated: No
- Committed: Yes
- Key files: `docs/architecture.md`, `docs/decisions.md`, `docs/operations.md`

**`.planning/`:**
- Purpose: GSD planning and codebase analysis documents
- Generated: Yes, by codebase mapping agents
- Committed: Yes

**`mcp-server/`:**
- Purpose: MCP server configuration for Laravel Boost integration
- Generated: No
- Committed: Yes

---

*Structure analysis: 2026-06-25*
