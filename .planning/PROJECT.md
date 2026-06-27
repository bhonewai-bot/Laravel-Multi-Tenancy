# TenantSmith — INFRA Hardening

## What This Is

TenantSmith is a Laravel multi-tenancy platform that lets a central admin provision tenant organizations, upload module packages, and manage domain verification via Cloudflare. Each tenant gets isolated databases with role-based access control.

This milestone hardens the Docker infrastructure, nginx configuration, and CI pipeline to make the platform production-ready.

## Core Value

**The Docker infrastructure is secure, performant, and production-ready.** Containers run as non-root, secrets stay out of image layers, nginx serves assets with proper caching and security headers, and the CI pipeline validates builds.

## Current Milestone: v1.1 INFRA Hardening

**Goal:** Production-ready Docker infrastructure with security headers, caching, OPcache, scheduler, and CI hardening.

**Target features:**
- Docker security: .dockerignore, non-root user, remove bind-mount, wire entrypoint, resource limits
- Nginx hardening: security headers, gzip compression, static asset caching
- OPcache extension for PHP-FPM throughput
- Scheduler service for Laravel scheduled tasks
- Queue worker health check
- CI pipeline: Docker build validation, Pint, composer audit
- Minor: rename Dockerfile, env var for Caddy domain

## Requirements

### Validated

- ✓ Multi-tenant architecture with Stancl Tenancy (domain-based identification, separate databases)
- ✓ Central dashboard with tenant CRUD, module management, and module request approval
- ✓ Tenant dashboard with user management, role/permission system, domain setup
- ✓ Cloudflare Custom Hostname integration for domain verification
- ✓ Module system with ZIP upload, inspection, installation, and uninstallation
- ✓ Job queue with database driver, tenant-aware job handlers with failure recovery
- ✓ Design system with Blade components, dark mode, mobile sidebar
- ✓ Auth system (Laravel Breeze) with login/logout, profile management

### Validated

- ✓ **C1**: Central admin authorization — `EnsureCentralAdmin` middleware + `access-central-admin` Gate
- ✓ **C2**: Module ZIP upload security — blocks dangerous extensions, safe file allowlist
- ✓ **C3**: Module state persistence — `module_installations` + `module_operations` tables with transactions
- ✓ **MAJOR-01**: Duplicate host verification consolidated — `HostResolver` delegates to `TenantDomainService`
- ✓ **MAJOR-02**: `EnsureModuleInstalled` identity-map fixed — uses `Str::lower()` for comparison
- ✓ **MAJOR-03**: `CreateTenantAction` wrapped in `DB::transaction()`
- ✓ **MAJOR-04**: `Tenant` model has relationships, accessors, and scopes
- ✓ **MAJOR-05**: Repair migration is idempotent (no code change needed)
- ✓ **MODERATE-1**: N+1 trap eliminated — per-request cache + eager loading
- ✓ **MODERATE-2**: `domains:recover-stuck` command for stuck domain recovery
- ✓ **MODERATE-3**: `ModuleController` catches specific exceptions, logs full error
- ✓ **MODERATE-4**: `DomainCheckController` uses `config()` instead of `env()`
- ✓ **MODERATE-5+6**: `StoreDomainAction` + `VerifyDomainAction` extracted, Action pattern consistent
- ✓ **Duplicate routes**: `dashboard` and auth routes prefixed with `tenant.` — `route:cache` works

### Out of Scope (this milestone)

- VPS public IP / custom domain deployment — ignored per user request
- Application-level changes (no new features, no model/controller changes)
- Test fixes beyond what's needed to verify INFRA changes

## Context

Brownfield project. Sole developer (Bhone Wai). The codebase audit (`docs/audit-2026-06-25.md`) identified 3 critical, 5 major, 6 moderate, and 3 minor issues. All critical, major, and moderate issues are now resolved. 96 tests passing (233 assertions). `route:cache` works in production.

Remaining: INFRA issues (Docker hardening, nginx security headers, OPcache, scheduler service) are deferred to a future milestone.

Codebase map exists at `.planning/codebase/` (1,933 lines across 7 documents).

## Constraints

- **Tech stack**: PHP 8.3, Laravel 12, Livewire 4, Alpine.js 3, Tailwind 3, MySQL 8.0 — must stay within existing stack
- **Authority**: Central admin identified by `CENTRAL_SUPERADMIN_EMAIL` / `CENTRAL_SUPERADMIN_PASSWORD` config — use existing env vars
- **Approach**: Educational — solo developer learning from each fix. Prefer clear, maintainable solutions over clever ones
- **Deployment**: Must be deployable after these 3 fixes (ignoring VPS public IP concern)

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Central auth via Gate + middleware | Simpler than a full role system at this stage; single super-admin is the current need | ✓ Implemented |
| Module ZIP: admin-only gate + content allowlist | Addresses both the auth gap and the extraction risk without rebuilding the module system | ✓ Implemented |
| Module state: dedicated `module_installations` pivot table | Database-level constraints prevent race conditions; simpler than distributed locking | ✓ Implemented |
| Fix order: C1 → C2 → C3 | C1 is foundational (all central routes depend on it), C2 depends on C1 being fixed first | ✓ Completed |
| Consolidate host verification on TenantDomainService | HostResolver delegates to TenantDomainService — eliminates divergent logic | ✓ Implemented |
| Wrap CreateTenantAction in DB::transaction | Prevents orphaned tenants if domain creation fails mid-sequence | ✓ Implemented |
| Extract domain actions (StoreDomainAction, VerifyDomainAction) | Consistent Action pattern across all write operations | ✓ Implemented |
| Prefix tenant routes with `tenant.` | Eliminates duplicate route names, enables `route:cache` in production | ✓ Implemented |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-06-27 after audit remediation complete*
