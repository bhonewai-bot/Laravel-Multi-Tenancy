# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- Module platform skeleton (planned).
- Module install/request/approval flow (planned).

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
