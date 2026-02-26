# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
### Added
- Central module management views:
  - `resources/views/modules/index.blade.php`
  - `resources/views/modules/create.blade.php`
  - `resources/views/module-requests/index.blade.php`
- Reusable dark Blade layout:
  - `resources/views/layouts/dark.blade.php`
- Tenant module page migrated to shared dark layout:
  - `resources/views/tenant/modules/index.blade.php`

### Changed
- Central approve route corrected to:
  - `POST /module-requests/{moduleRequest}/approve`
- Module model fillable key aligned to migration/controller payload:
  - `icon_path` (replacing `image_path`)
- Tenant module actions now include install/uninstall transitions after approval.

### Fixed
- Central UI crash:
  - `View [modules.index] not found`
- Tenant modules status rendering bug:
  - incorrect variable reference `$requests` corrected to `$requestModules`
- Tenant module state now reflects installed/uninstalled transitions in UI.

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
