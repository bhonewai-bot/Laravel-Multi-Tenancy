# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
### Added
- Tenant RBAC foundation (Step 13):
  - Tenant RBAC schema: `roles`, `features`, `permissions`, `role_permissions`, and tenant `users.role_id`.
  - Tenant RBAC models: `Role`, `Feature`, `Permission`.
  - Tenant RBAC seed data: `admin`, `staff`, plus feature-action permissions (including module permissions).
  - Middleware aliases for tenant authorization:
    - `role:<name>`
    - `permission:<feature.action>`
- Policy-driven tenant module authorization:
  - `$this->authorize(...)` enforced in tenant module request/install/uninstall controller actions.
  - `ModuleRequestPolicy` abilities for `viewAny`, `request`, `install`, `uninstall`.
- Central tenant onboarding flow:
  - `POST /tenants` creates tenant + primary domain in central DB.
  - Automatic provisioning pipeline on `TenantCreated`:
    - `CreateDatabase`
    - `MigrateDatabase`
- Auto tenant bootstrap seeding (Step 12):
  - `TenantCreated` pipeline now includes `SeedDatabase`.
  - Tenant seeder configured to `TenantBootstrapSeeder`.
  - Default tenant admin is seeded with deterministic email pattern `admin@{tenant_id}.local`.
- Tenant cache migration support:
  - `database/migrations/tenant/0001_01_01_000001_create_cache_table.php`
- Minimal CI workflow:
  - `.github/workflows/ci.yml` runs composer validation + bootstrap + `php artisan test`.
- Breeze-style integrated sidebar app shell with grouped navigation.
- Sidebar footer logout action.
- Central module management views:
  - `resources/views/modules/index.blade.php`
  - `resources/views/modules/create.blade.php`
  - `resources/views/module-requests/index.blade.php`
- Reusable dark Blade layout:
  - `resources/views/layouts/dark.blade.php`
- Tenant module page migrated to shared dark layout:
  - `resources/views/tenant/modules/index.blade.php`
- Module access guard coverage for tenant modules:
  - `module:customer`
  - `module:product`
  - `module:sale`

### Changed
- Tenant user role checks are now case-insensitive (`hasRole`).
- Tenant users migration now supports both fresh tenants and existing tenants:
  - creates `users` if missing
  - adds nullable `role_id` if `users` exists without it
- Super admin seeder now uses env-driven credentials and `updateOrCreate`.
- Tenant list action UI changed to single `Action` dropdown (`View`, `Edit`, `Delete`).
- Tenant list, module list, and module requests tables now use full-width fixed column layouts.
- Module request status UI upgraded with visual badges (dot + border + semantic colors).
- Main authenticated shell switched to sidebar-oriented Breeze white theme.
- Tenant bootstrap credentials strategy uses env-driven default password:
  - `TENANT_DEFAULT_ADMIN_PASSWORD`
- Baseline root feature test now reflects redirect contract (`/` -> `/tenants`).
- Central approve route corrected to:
  - `POST /module-requests/{moduleRequest}/approve`
- Module model fillable key aligned to migration/controller payload:
  - `icon_path` (replacing `image_path`)
- Tenant module actions now include install/uninstall transitions after approval.
- Module lifecycle milestone status:
  - Step 9 and Step 10 are now complete.

### Fixed
- Unauthorized access in tenant permission middleware now returns `403` consistently.
- Super admin central seed failure due to missing password.
- Existing-tenant migration failure when `users` table already existed.
- Tenant permission route key mismatch (`users` -> `user.read`).
- Eliminated isolated/floating sidebar feel by integrating sidebar into the app shell layout flow.
- Central UI crash:
  - `View [modules.index] not found`
- Tenant modules status rendering bug:
  - incorrect variable reference `$requests` corrected to `$requestModules`
- Tenant module state now reflects installed/uninstalled transitions in UI.
- Tenant login failure caused by missing tenant `cache` table when using `CACHE_STORE=database`.
- Tenant list action dropdown clipping/overflow issues in table layout.
- Manual smoke-test validation completed:
  - uninstalling `Customer` blocks `/customers` with `403`
  - reinstalling `Customer` restores `/customers` access (`200`)

### Tests
- Added `tests/Feature/Tenancy/TenantOnboardingTest.php`.
- Added `tests/Feature/Tenancy/TenantBootstrapSeederTest.php`.

## [0.1.0] - 2026-02-25
### Added
- New Laravel project for multi-tenancy rebuild.
- Docker-based development environment using `DockerFile` and `docker-compose.yml`.
- Tenancy foundation with `stancl/tenancy`.
- Module system foundation package with `nwidart/laravel-modules`.
- Tenancy scaffolding (`tenants`, `domains`, tenant route file, tenancy provider).
- Tracking docs: `PROGRESS.md`, `docs/decisions.md`, `CHANGELOG.md`.

### Changed
- Central root route now scoped to configured central domains.
- Tenancy config model bindings switched from package models to app models.

### Fixed
- `Tenant could not be identified with tenant_id` during tenant migrate (tenant provisioning order).
- `Call to undefined method ... Tenant::domains()` caused by incorrect model binding.
- `TenantDatabaseDoesNotExistException` for `tenantt001` resolved via proper tenant recreation and DB provisioning.
