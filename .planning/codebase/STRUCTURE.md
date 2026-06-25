# Codebase Structure

**Analysis Date:** 2026-06-25

## Directory Layout

```
Laravel-Multi-Tenancy/
├── app/                          # Core Laravel application code
│   ├── Actions/Tenants/          # Orchestrated write operations for tenant lifecycle
│   ├── Http/Controllers/         # Central-facing controllers
│   │   ├── Auth/                 # Breeze auth controllers (login, password, verification)
│   │   └── Tenant/               # Tenant-scoped controllers (domain, user, role, module-request)
│   ├── Http/Middleware/          # Custom middleware (host validation, RBAC, module checks)
│   ├── Http/Requests/            # Form Request validation classes
│   ├── Jobs/                     # Queued jobs (module install/uninstall, CF polling)
│   ├── Livewire/                 # (Empty) No class-based Livewire components in core app
│   ├── Logging/                  # Monolog processors (tenant context)
│   ├── Models/                   # Eloquent models (Tenant, Domain, Module, User, Role, etc.)
│   ├── Policies/                 # Authorization policies (ModuleRequest, Role, User)
│   ├── Providers/                # Service providers (App, Telescope, Tenancy)
│   ├── Services/                 # Business logic services (Cloudflare, module management, domain)
│   ├── Support/                  # Support classes (HostResolver, AppHome)
│   └── View/Components/          # Layout component classes (AppLayout, GuestLayout)
├── Modules/                      # Installable feature modules (nwidart/laravel-modules)
│   └── Product/                  # Product module
│       ├── app/                  # Module-specific code
│       │   ├── Http/Controllers/ # ProductController
│       │   ├── Jobs/             # ImportProductFromUrl
│       │   ├── Livewire/         # ProductTable, ProductCreateForm, ProductEditForm
│       │   ├── Models/           # Product model
│       │   ├── Providers/        # ProductServiceProvider, RouteServiceProvider, EventServiceProvider
│       │   └── Services/         # Product import services (ScrapingBee, importers)
│       ├── config/               # Module config (config.php)
│       ├── database/
│       │   ├── factories/        # Product factory
│       │   ├── migrations/       # Module-scoped tenant migrations
│       │   └── seeders/          # ProductDatabaseSeeder
│       ├── resources/
│       │   ├── assets/           # Module assets (JS, SASS)
│       │   └── views/            # Module Blade views (Livewire + standard)
│       ├── routes/               # Module routes (web.php, api.php)
│       └── tests/                # Module tests (Feature, Unit)
├── bootstrap/
│   └── app.php                   # App bootstrap: routing, middleware, exceptions
├── config/
│   ├── tenancy.php               # Stancl tenancy configuration
│   ├── cloudflare.php            # Cloudflare SSL for SaaS configuration
│   ├── telescope.php             # Laravel Telescope config
│   └── ...                       # Standard Laravel config files
├── database/
│   ├── factories/                # Central model factories (UserFactory)
│   ├── migrations/               # Central database migrations
│   │   └── tenant/               # Tenant database migrations (run per-tenant)
│   └── seeders/                  # Database seeders
├── docker/                       # Docker deployment config (nginx, prod)
├── docs/                         # Project documentation
├── mcp-server/                   # Python MCP server (separate from Laravel)
├── resources/
│   ├── css/                      # Tailwind CSS + custom animations (app.css)
│   ├── js/                       # Alpine.js + theme/sidebar stores (app.js)
│   └── views/
│       ├── auth/                 # Auth views (login, verify-email, confirm-password)
│       ├── components/           # Blade component library (30+ components)
│       ├── design-system/        # Design system reference page
│       ├── errors/               # Error pages (403, 404, 419, 500)
│       ├── layouts/              # Layout templates (app, guest, navigation)
│       ├── livewire/             # (Empty in core app)
│       ├── module-requests/      # Central module request views
│       ├── modules/              # Central module catalog views
│       ├── profile/              # Profile edit views
│       └── tenant/               # Tenant management views
│           ├── domains/          # Tenant domain management (index, create, show)
│           ├── modules/          # Tenant module request/install views
│           ├── roles/            # Tenant role management (CRUD)
│           └── users/            # Tenant user management (CRUD)
├── routes/
│   ├── auth.php                  # Auth routes (Breeze)
│   ├── console.php               # Artisan console routes
│   ├── tenant.php                # Tenant-scoped routes
│   └── web.php                   # Central web routes
├── tests/
│   ├── Feature/                  # Feature tests
│   │   ├── Auth/                 # Authentication tests
│   │   └── Tenancy/              # Multi-tenancy integration tests
│   └── Unit/                     # Unit tests
├── .claude/skills/               # Project-specific AI skills
│   ├── multi-tenancy/            # Multi-tenancy domain skill
│   ├── tenantsmith-design/       # Design system skill
│   ├── livewire-development/     # Livewire patterns skill
│   └── ...                       # Other skills
├── .planning/                    # GSD planning documents
│   └── codebase/                 # Codebase analysis documents
├── CLAUDE.md                     # Project instructions for AI
├── composer.json                 # PHP dependencies
├── package.json                  # JS dependencies (Vite, Tailwind, Alpine.js)
└── vite.config.js                # Vite build configuration
```

## Directory Purposes

**`app/Actions/Tenants/`:**
- Purpose: Action classes that orchestrate multi-step write operations for tenant lifecycle
- Contains: `CreateTenantAction`, `UpdateTenantAction`, `SyncCloudflareDomainAction`
- Pattern: Constructor-injected services, single `execute()` method, returns model
- Key files: `app/Actions/Tenants/CreateTenantAction.php`

**`app/Http/Controllers/`:**
- Purpose: Central-facing HTTP request handlers
- Contains: DashboardController, TenantController, ModuleController, ModuleRequestController, ProfileController, CloudflareHostnameChallengeController, DomainCheckController
- Pattern: Constructor-injected actions/services, return View or RedirectResponse
- Key files: `app/Http/Controllers/TenantController.php`, `app/Http/Controllers/DashboardController.php`

**`app/Http/Controllers/Tenant/`:**
- Purpose: Tenant-scoped controllers that run after tenancy initialization
- Contains: DomainController, ModuleRequestController, RoleController, UserController
- Pattern: Authorization via `$this->authorize()`, ownership checks via `tenant()->id` comparison
- Key files: `app/Http/Controllers/Tenant/DomainController.php`

**`app/Http/Middleware/`:**
- Purpose: Request-level guards enforcing host policy, RBAC, and module installation checks
- Contains: RejectInvalidTenantHost, EnsureModuleInstalled, EnsureTenantPermission, EnsureTenantRole
- Pattern: Constructor-injected dependencies, `handle()` method with Closure `$next`
- Key files: `app/Http/Middleware/RejectInvalidTenantHost.php`

**`app/Http/Requests/`:**
- Purpose: Form Request validation classes
- Contains: LoginRequest, ProfileUpdateRequest, TenantStoreRequest, TenantUpdateRequest, RoleStoreRequest, RoleUpdateRequest, UserStoreRequest, UserUpdateRequest, TenantUserUpdateRequest
- Pattern: Standard Laravel FormRequest with `rules()` method
- Key files: `app/Http/Requests/TenantStoreRequest.php`

**`app/Jobs/`:**
- Purpose: Queued jobs for async operations
- Contains: InstallTenantModule, UninstallTenantModule, SyncPendingCloudflareDomain
- Pattern: `ShouldQueue` interface, constructor-injected data, `handle()` method, `failed()` method for error tracking
- Key files: `app/Jobs/InstallTenantModule.php`, `app/Jobs/SyncPendingCloudflareDomain.php`

**`app/Models/`:**
- Purpose: Eloquent models representing database entities
- Contains: Tenant, Domain, Module, ModuleRequest, User, Role, Permission, Feature
- Pattern: Stancl concerns for Tenant/Domain, `CentralConnection` trait for central-only models
- Key files: `app/Models/Tenant.php`, `app/Models/Domain.php`, `app/Models/User.php`

**`app/Services/`:**
- Purpose: Business logic, external integrations, and complex operations
- Contains: CloudflareService, DomainCloudflareSyncService, TenantDomainService, TenantModuleInstaller, TenantModuleRegistry, ModuleZipInspector, CentralAdminService
- Pattern: Constructor-injected dependencies, descriptive method names, PHPDoc with side effects documented
- Key files: `app/Services/DomainCloudflareSyncService.php`, `app/Services/TenantModuleInstaller.php`

**`app/Support/`:**
- Purpose: Lightweight utility classes
- Contains: HostResolver (host classification), AppHome (post-auth redirect)
- Pattern: Stateless or minimal state, pure logic
- Key files: `app/Support/HostResolver.php`

**`app/Providers/`:**
- Purpose: Service providers bootstrapping application services
- Contains: AppServiceProvider (policies, admin bootstrap, Livewire route), TenancyServiceProvider (stancl events, routes, middleware priority), TelescopeServiceProvider
- Key files: `app/Providers/TenancyServiceProvider.php`, `app/Providers/AppServiceProvider.php`

**`Modules/`:**
- Purpose: Self-contained feature modules installed per-tenant via the module system
- Contains: `Product/` (the only current module)
- Pattern: nwidart/laravel-modules structure with Controllers, Livewire, Models, Services, views, routes, migrations
- Key files: `Modules/Product/app/Providers/ProductServiceProvider.php`, `Modules/Product/routes/web.php`

**`resources/views/components/`:**
- Purpose: Reusable Blade component library (TenantSmith design system)
- Contains: 30+ components including buttons, cards, alerts, modals, data tables, sidebar, inputs
- Pattern: `<x-component-name>` Blade syntax, all support dark mode via custom hex values
- Key files: `resources/views/components/primary-button.blade.php`, `resources/views/components/sidebar.blade.php`, `resources/views/components/alert.blade.php`

**`database/migrations/tenant/`:**
- Purpose: Migrations run against each tenant database during provisioning
- Contains: cache, jobs, roles, features, permissions, role_permissions, users tables
- Pattern: Standard Laravel migrations, run via `php artisan tenants:migrate`
- Key files: `database/migrations/tenant/2026_03_01_064333_create_users_table.php`

**`tests/`:**
- Purpose: PHPUnit test suite
- Contains: Feature tests (Auth, Tenancy), Unit tests
- Pattern: PHPUnit classes extending `Tests\TestCase`, feature tests for integration, central domain set in setUp
- Key files: `tests/TestCase.php`, `tests/Feature/Tenancy/TenantOnboardingTest.php`

## Key File Locations

**Entry Points:**
- `bootstrap/app.php`: Application bootstrap -- routing, middleware, exceptions
- `routes/web.php`: Central domain routes
- `routes/tenant.php`: Tenant-scoped routes (loaded by TenancyServiceProvider)
- `Modules/Product/routes/web.php`: Product module routes

**Configuration:**
- `config/tenancy.php`: Stancl tenancy config (central domains, DB prefix, bootstrappers)
- `config/cloudflare.php`: Cloudflare SSL for SaaS config (enabled flag, API creds, fallback origin)
- `config/auth.php`: Auth config including central admin credentials

**Core Logic:**
- `app/Support/HostResolver.php`: Host classification engine
- `app/Services/DomainCloudflareSyncService.php`: CF sync orchestration
- `app/Services/TenantModuleInstaller.php`: Module migration/seeding engine
- `app/Actions/Tenants/CreateTenantAction.php`: Tenant provisioning orchestration

**Testing:**
- `tests/TestCase.php`: Base test class (sets central domain, disables Vite)
- `tests/Feature/Tenancy/`: Multi-tenancy integration tests (8 test files)
- `tests/Feature/Auth/`: Authentication tests (5 test files)

## Naming Conventions

**Files:**
- Controllers: `PascalCaseController.php` (e.g., `TenantController.php`, `DomainController.php`)
- Models: `PascalCase.php` (e.g., `Tenant.php`, `Domain.php`, `ModuleRequest.php`)
- Services: `PascalCaseService.php` (e.g., `CloudflareService.php`, `TenantModuleRegistry.php`)
- Actions: `PascalCaseAction.php` (e.g., `CreateTenantAction.php`)
- Jobs: `PascalCase.php` (e.g., `InstallTenantModule.php`, `SyncPendingCloudflareDomain.php`)
- Middleware: `PascalCase.php` (e.g., `RejectInvalidTenantHost.php`)
- Form Requests: `PascalCaseRequest.php` (e.g., `TenantStoreRequest.php`)
- Blade views: `kebab-case.blade.php` (e.g., `create-tenant.blade.php`)
- Blade components: `kebab-case.blade.php` in `resources/views/components/` (e.g., `primary-button.blade.php`)
- Tests: `PascalCaseTest.php` (e.g., `TenantOnboardingTest.php`)

**Directories:**
- `PascalCase/` for PHP namespaces (e.g., `Actions/`, `Controllers/`, `Middleware/`)
- `kebab-case/` or `lowercase` for view directories (e.g., `module-requests/`, `tenant/`)
- Tenant-specific controllers grouped under `Tenant/` subdirectory

**PHP Classes:**
- Namespace: `App\{Directory}\ClassName` (e.g., `App\Services\CloudflareService`)
- Module namespace: `Modules\{ModuleName}\{Directory}\ClassName`
- Class names: PascalCase, descriptive (e.g., `DomainCloudflareSyncService`, `TenantModuleRegistry`)

**Methods:**
- camelCase (e.g., `canServeTenantHost()`, `markModuleOperationRunning()`)
- Boolean methods: `is*` or `can*` prefix (e.g., `isCentralHost()`, `shouldRetry()`)
- Action methods: descriptive verb phrases (e.g., `ensureConfiguredSuperAdminExists()`)

## Where to Add New Code

**New Central Controller:**
- Implementation: `app/Http/Controllers/{Name}Controller.php`
- Routes: Add to `routes/web.php` inside the `auth` middleware group
- Tests: `tests/Feature/{Name}Test.php`

**New Tenant Controller:**
- Implementation: `app/Http/Controllers/Tenant/{Name}Controller.php`
- Routes: Add to `routes/tenant.php` inside the `auth` middleware group
- Authorization: Use `$this->authorize()` with existing or new policies
- Tests: `tests/Feature/Tenancy/{Name}Test.php`

**New Service:**
- Implementation: `app/Services/{Name}Service.php`
- Pattern: Constructor-injected dependencies, PHPDoc with side effects
- Register: Via constructor injection (auto-resolved by container)

**New Action:**
- Implementation: `app/Actions/Tenants/{Name}Action.php`
- Pattern: Constructor-injected services, single `execute()` method

**New Job:**
- Implementation: `app/Jobs/{Name}.php`
- Pattern: `ShouldQueue`, constructor data, `handle()` + `failed()` methods

**New Middleware:**
- Implementation: `app/Http/Middleware/{Name}.php`
- Register: Add alias in `bootstrap/app.php` under `$middleware->alias()`

**New Model:**
- Implementation: `app/Models/{Name}.php`
- Migration: `database/migrations/{timestamp}_create_{name}_table.php` (central) or `database/migrations/tenant/` (tenant)
- Factory: `database/factories/{Name}Factory.php`

**New Blade Component:**
- Implementation: `resources/views/components/{name}.blade.php`
- Usage: `<x-{name} />` in views
- Follow: TenantSmith design system patterns (see `.claude/skills/tenantsmith-design/SKILL.md`)

**New Blade Page:**
- Implementation: `resources/views/{section}/{name}.blade.php`
- Layout: `<x-app-layout>` with `x-slot:header` for page header
- Follow: Page patterns from `.claude/skills/tenantsmith-design/references/page-patterns.md`

**New Policy:**
- Implementation: `app/Policies/{Model}Policy.php`
- Register: `Gate::policy(Model::class, ModelPolicy::class)` in `AppServiceProvider::boot()`

**New Module:**
- Directory: `Modules/{ModuleName}/`
- Follow: Product module structure (`Modules/Product/`)
- Service Provider: `Modules/{ModuleName}/app/Providers/{ModuleName}ServiceProvider.php`
- Routes: `Modules/{ModuleName}/routes/web.php` with tenancy middleware + `module:{slug}` middleware

**New Tenant Migration:**
- Location: `database/migrations/tenant/{timestamp}_{description}.php`
- Run: `php artisan tenants:migrate`

**New Test:**
- Feature: `tests/Feature/{Area}/{TestName}.php` (extends `Tests\TestCase`)
- Unit: `tests/Unit/{TestName}.php`
- Run: `php artisan test --compact --filter={TestName}`

## Special Directories

**`Modules/`:**
- Purpose: Installable feature modules with self-contained code, views, routes, migrations
- Generated: Yes (via ZIP upload or manual creation)
- Committed: Yes (checked into git)
- Note: Module ZIPs are uploaded via `ModuleZipInspector` and extracted here

**`.claude/skills/`:**
- Purpose: AI skill definitions for domain-specific guidance
- Generated: No (manually created)
- Committed: Yes
- Note: Contains multi-tenancy, tenantsmith-design, livewire-development, and other skills

**`.planning/`:**
- Purpose: GSD planning documents and codebase analysis
- Generated: Yes (by GSD tools)
- Committed: Yes

**`docker/`:**
- Purpose: Docker deployment configuration (nginx, production)
- Generated: No (manually configured)
- Committed: Yes

**`mcp-server/`:**
- Purpose: Python MCP server (separate from Laravel app)
- Generated: No
- Committed: Yes
- Note: Contains `.venv/` virtual environment

---

*Structure analysis: 2026-06-25*
