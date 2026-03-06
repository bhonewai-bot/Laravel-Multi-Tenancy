# Progress Log

## 2026-03-05

### Done
- Completed Step 16: Automated E2E Feature Tests.
  - Added `tests/Feature/Tenancy/TenancyE2EFlowTest.php` with end-to-end coverage for:
    - central tenant provisioning
    - tenant signup isolation
    - module request -> approve -> install flow
    - module guard behavior (`403` before install, `200` after install)
    - custom domain add + verify + central domain-check gate
  - Stabilized tenant E2E tests for CI/runtime:
    - switched this E2E class to migration-based DB lifecycle (`DatabaseMigrations`)
    - used absolute host URLs in tenant/central HTTP requests
    - added explicit tenancy context cleanup between assertions/tests.
- Fixed CI failure caused by missing Vite build manifest:
  - updated `.github/workflows/ci.yml` to run `npm ci` and `npm run build` before `php artisan test`.
- Verified GitHub Actions status is green after CI workflow fix.

### Commands Run
- `php artisan test tests/Feature/Tenancy/TenancyE2EFlowTest.php`
- `php artisan test`

### Result
- Step 16 Definition of Done is met:
  - automated E2E tenancy scenarios now replace manual smoke checks for the covered flows.
- Full local suite passes.
- CI workflow passes after frontend build step was added.

### Next
1. Remove temporary local tenant sqlite artifacts (`database/tenant*`) from working tree or ignore strategy.
2. Expand E2E coverage with negative-path assertions (rejected module install, unverified domain access path).
3. Move to next milestone (production-hardening or observability focus).

### Blockers
- None currently.

## 2026-03-03

### Done
- Completed Step 15: Module install/uninstall hardening.
  - Tenant module install/uninstall now uses idempotent result states:
    - `installed`
    - `already_installed`
    - `uninstalled`
    - `already_uninstalled`
  - Added operation-level lock in `TenantModuleInstaller` to prevent concurrent install/uninstall race on same tenant+module.
  - Kept state consistency rule:
    - `installed_modules` is updated only after successful migrate/seed.
    - uninstall state is updated only after successful migration rollback.
  - Removed controller-side uninstall pre-check so uninstall retries/no-op requests are handled by installer idempotency path.

### Commands Run
- `php artisan test`
- `php artisan test --filter=Tenant`

### Result
- Full test suite passed.
- Tenancy test subset passed after idempotent uninstall flow update.
- Step 15 Definition of Done is met:
  - retries/failures no longer corrupt module install state
  - repeated uninstall attempts are safe no-op responses
  - concurrent same-module operations are lock-guarded

### Next
1. Add focused feature tests specifically for module install/uninstall idempotency + lock contention.
2. Optionally move install/uninstall execution into queued jobs with uniqueness keys while preserving current idempotent service behavior.
3. Continue to Step 16 scope.

### Blockers
- None currently.

## 2026-03-02

### Done
- Delivered MVP custom-domain lifecycle hardening for tenant hosts.
  - Moved Caddy authorization endpoint registration into bootstrap route wiring (central-domain scoped and throttled).
  - Hardened `DomainCheckController` token contract:
    - uses `DOMAIN_CHECK_TOKEN`
    - returns `500` when token is not configured
    - returns `403` on token mismatch
    - only returns `200` for central domains or verified custom domains.
  - Added host normalization improvement (trailing-dot safe) in `TenantDomainService`.
  - Added tenant route throttling for custom domain create/verify actions.
- Improved tenant custom-domain UX guidance:
  - Added explicit DNS checklist in My Domains page (TXT + A/CNAME + HTTPS without `:8000`).
  - Added setup notes in Add Domain flow for post-create DNS/TLS steps.
- Added custom-domain regression tests:
  - `DomainCheckTest` for central domain, verified custom domain, unverified custom domain, invalid token.
  - `TenantDomainLifecycleTest` for unverified host blocking and verify action stamping `verified_at`.
- Aligned tenant bootstrap seeder test expectation with current seeded admin email strategy (`admin@example.com`) so test suite reflects runtime behavior.

### Commands Run
- `php artisan test tests/Feature/Tenancy/DomainCheckTest.php tests/Feature/Tenancy/TenantDomainLifecycleTest.php`
- `php artisan test`

### Result
- Domain-check gate now behaves as a strict Caddy contract and is validated by tests.
- Tenant custom domain flow now includes explicit DNS + TLS guidance in UI.
- Verified-only domain enforcement is covered by test cases.
- Full test suite passes.

### Next
1. Add centralized domain setup docs for production (A/CNAME patterns, TLS expectations, Cloudflare option).
2. Add optional audit logging for domain verify/remove actions.
3. Add action-level policies for tenant domain operations if you want policy parity with modules.

### Blockers
- None currently.

## 2026-03-01

### Done
- Completed Step 13: Tenant RBAC.
  - Added tenant RBAC schema (`roles`, `features`, `permissions`, `role_permissions`, and tenant `users.role_id`).
  - Added RBAC models/relations (`Role`, `Feature`, `Permission`) and user helpers (`hasRole`, `hasPermission`).
  - Added tenant RBAC middleware aliases (`role`, `permission`) and enforced denial for unauthorized users.
  - Switched tenant module actions to policy-driven authorization using `$this->authorize(...)`.
  - Added `ModuleRequestPolicy` abilities for `viewAny`, `request`, `install`, `uninstall`.
  - Added module feature permissions in tenant RBAC seed:
    - `module.read`, `module.request`, `module.install`, `module.uninstall`.
- Hardened bootstrap and migration safety:
  - Tenant `users` migration is now safe for existing tenants (create-if-missing, add `role_id` if missing).
  - Super admin seeder is now idempotent and writes password from env.
  - Added central super admin env keys in `.env.example`.
- Verified tests after RBAC/policy updates:
  - `php artisan test` passed.

### Commands Run
- `php artisan test`

### Result
- Step 13 Definition of Done is met:
  - tenant resources are protected with role/permission + policies
  - non-admin users are blocked from admin-level tenant module actions
  - tenant onboarding still provisions login-ready admin users automatically

### Next
1. Add focused feature tests for RBAC route/policy denials (staff vs admin).
2. Start Step 14: custom domain lifecycle (request, verify, activate/deactivate).
3. Add role/permission management UI (tenant-side) for admin users.

### Blockers
- None currently.

## 2026-02-28

### Done
- Completed Step 12: Auto Tenant Bootstrap Seed.
  - Enabled tenant seed job in provisioning pipeline:
    - `TenantCreated` -> `CreateDatabase` -> `MigrateDatabase` -> `SeedDatabase`
  - Configured tenancy seeder class to `TenantBootstrapSeeder`.
  - Added tenant-only bootstrap seeder with idempotent `User::firstOrCreate(...)`.
  - Seeded default tenant admin strategy:
    - email: `admin@{tenant_id}.local`
    - password: `TENANT_DEFAULT_ADMIN_PASSWORD` (env), hashed by model cast.
- Added tenant cache table migration for database cache driver compatibility:
  - `database/migrations/tenant/0001_01_01_000001_create_cache_table.php`
- Verified Step 12 behavior manually:
  - creating tenant from central auto-creates tenant DB
  - tenant migrations + seed run automatically
  - login works on tenant domain with seeded admin credentials
- Polished central tenant list action UX:
  - switched action to dropdown style and adjusted overflow/clipping behavior.
- Added minimal CI + test pipeline baseline:
  - GitHub Actions workflow at `.github/workflows/ci.yml`
  - CI runs composer validation, dependency install, app bootstrap, and `php artisan test`.
- Added tenancy-focused feature tests:
  - central tenant onboarding request creates tenant + domain
  - tenant bootstrap seeder is idempotent and seeds hashed password
- Updated baseline example test to match central root redirect behavior (`/` -> `/tenants`).

### Commands Run
- `php artisan tenants:seed`
- `php artisan tenants:seed --tenants=t001 --class=TenantBootstrapSeeder`
- `php artisan view:clear`
- `php artisan optimize:clear`

### Result
- Step 12 Definition of Done is met:
  - new tenants are login-ready without manual post-create steps.
- Tenant login path now works with seeded credentials on tenant domains.
- Database cache-related tenant login error is resolved by tenant cache migration.

### Next
1. Add CI baseline (lint + test gates) to protect onboarding/seeding flow from regressions.
2. Add feature test for tenant provisioning + seeded admin account (Step 12 task 8).
3. Start Step 13: tenant RBAC foundation (roles/permissions/policies).

### Blockers
- None currently.

## 2026-02-27

### Done
- Completed Step 11: Central Tenant Onboarding.
  - Central tenant CRUD routes/controller are active for onboarding.
  - `TenantStoreRequest` validates unique `tenant_id` (`tenants.id`) and `domain` (`domains.domain`).
  - `TenantController@store` creates tenant + primary domain in central DB.
  - No manual `tenants:migrate` is needed after onboarding.
- Confirmed automatic tenant provisioning pipeline is wired:
  - `TenantCreated` -> `CreateDatabase` -> `MigrateDatabase`
  - Implemented in `app/Providers/TenancyServiceProvider.php`.
- Refactored app UI from dark custom layout to Breeze-style shell:
  - Replaced old dark layout usage with `x-app-layout` across tenant/module pages.
  - Implemented integrated sidebar app shell and removed isolated/floating behavior.
  - Added sidebar logout button.
- Updated table layouts for better full-width data distribution:
  - Tenant list uses action dropdown and fixed-width table columns.
  - Modules list and module requests list use full-width fixed column layouts.
  - Module request statuses now have more visual badges (dot + color + border).

### Commands Run
- `php artisan view:clear`
- `php artisan view:cache`
- `rg -n "TenantCreated|CreateDatabase|MigrateDatabase" app/Providers config/tenancy.php`

### Result
- New tenant onboarding is central-first and automatic:
  - create tenant + domain in central
  - tenant DB created
  - tenant migrations run
  - tenant is reachable without manual migration command
- UI is now consistent with Breeze-style white theme and shared sidebar shell.
- Tenant/module/module-request tables are more readable and proportional.

### Next
1. Step 12: Auto-seeding on tenant provision (`SeedDatabase` pipeline + baseline tenant data).
2. Step 13: Tenant RBAC (roles/permissions/policies + middleware enforcement).
3. Step 14: Custom domain lifecycle (add, verify, activate/deactivate).
4. Add feature tests for central onboarding pipeline and tenant usability after create.

### Blockers
- None currently.

## 2026-02-26

### Done
- Completed Step 9 module platform skeleton (central-first):
  - Added central views:
    - `resources/views/modules/index.blade.php`
    - `resources/views/modules/create.blade.php`
    - `resources/views/module-requests/index.blade.php`
  - Added shared dark layout:
    - `resources/views/layouts/dark.blade.php`
  - Applied dark layout to tenant modules page:
    - `resources/views/tenant/modules/index.blade.php`
- Fixed central route typo:
  - `POST /module-requests/{moduleRequest}/approve` (was misspelled as `/modues-requests/...`)
- Fixed module model field mismatch:
  - `app/Models/Module.php` fillable key changed from `image_path` to `icon_path`.
- Fixed tenant module list rendering bug:
  - Replaced wrong variable `$requests` with `$requestModules` in tenant module view.
- Verified route wiring for both central and tenant module endpoints.
- Confirmed request/approval/install state transitions in UI:
  - Tenant can request module.
  - Central can approve request.
  - Tenant can install approved module.
  - Tenant can uninstall installed module.
- Completed Step 10 install flow behavior for module lifecycle:
  - Tenant install validates module is active + approved before execution.
  - Tenant module migrations run against tenant connection during install.
  - Tenant `installed_modules` state is updated on install/uninstall.
- Completed module access guard setup:
  - Middleware alias `module` is active.
  - Module routes enforce `module:customer`, `module:product`, and `module:sale`.
- Completed smoke test wrap-up (manual validation):
  - Verified module middleware visibility in route list (`route:list -v`).
  - After uninstalling `Customer`, `/customers` returned `403`.
  - After reinstalling `Customer`, `/customers` returned `200`.
  - `Product` route access remained available when installed.

### Commands Run
- `docker compose exec app php artisan route:list | rg "modules|module-requests"`
- `docker compose exec app php artisan view:clear`
- `php artisan route:list -v` (module middleware verification)

### Result
- Central module pages now render without `View [modules.index] not found`.
- Tenant module page now shows consistent dark UI and correct request status.
- Module request approve/reject routes are correctly addressable.
- Step 9 is complete.
- Step 10 is complete.
- Module route protection is confirmed by smoke test.

### Next
1. Step 11: Central tenant onboarding (tenant create flow with domain + provisioning).
2. Step 12: Automatic tenant bootstrap seeding (default admin + baseline data).
3. Step 13: Tenant RBAC (roles/permissions + policy/middleware enforcement).
4. Step 14: Custom domain lifecycle (add, verify TXT, activate/deactivate).
5. Add automated feature tests for the completed module lifecycle smoke flow.

### Blockers
- None currently.

## 2026-02-25

### Done
- Created new project: `Laravel-Multi-Tenancy`.
- Added Docker-based local stack (`app`, `nginx`, `mysql`, `phpmyadmin`) using `docker-compose.yml`.
- Configured `.env` for central domain routing:
  - `APP_URL=http://app.localhost:8000`
  - `TENANCY_CENTRAL_DOMAIN=app.localhost`
- Installed and bootstrapped core packages:
  - `stancl/tenancy`
  - `nwidart/laravel-modules`
- Ran central migrations successfully (`users`, `cache`, `jobs`, `tenants`, `domains`).
- Enabled tenancy provider and route split:
  - Central routes in `routes/web.php` (scoped by central domains)
  - Tenant routes in `routes/tenant.php`
- Fixed tenant model wiring issues:
  - Added `App\Models\Tenant` with `HasDatabase`, `HasDomains`
  - Added `App\Models\Domain`
  - Updated `config/tenancy.php` to use app models
- Verified tenant database-per-tenant behavior:
  - Tenant `t001` resolves by domain `t001.app.localhost`
  - Database created as `tenantt001`
- Created baseline repository commit:
  - `d110bd2 chore(init): bootstrap Laravel Multi-Tenancy with Docker and tenancy foundation`

### Commands Run
- `composer create-project laravel/laravel Laravel-Multi-Tenancy`
- `docker compose up -d --build`
- `docker compose exec app composer require stancl/tenancy nwidart/laravel-modules`
- `docker compose exec app php artisan tenancy:install`
- `docker compose exec app php artisan migrate`
- `docker compose exec app php artisan tenants:migrate --tenants=t001`
- `docker compose exec app php artisan optimize:clear`
- `docker compose exec app php artisan tinker ...` (tenant/domain verification and recovery)

### Result
- Milestone complete: Step 1-8 foundation is working.
- Central app resolves at `app.localhost`.
- Tenant app resolves at `t001.app.localhost`.
- Data isolation pattern confirmed: database-per-tenant.

### Next
1. Step 9: Module platform skeleton (`modules`, `module_requests`, central and tenant module pages).
2. Step 10: Module install flow (approve/request/install/uninstall lifecycle).
3. Add module middleware (`module:<Name>`) and enforce access guard on tenant routes.
4. Add end-to-end tests for request/approve/install path.

### Blockers
- None currently.
