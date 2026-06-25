# TenantSmith — Security Hardening

## What This Is

TenantSmith is a Laravel multi-tenancy platform that lets a central admin provision tenant organizations, upload module packages, and manage domain verification via Cloudflare. Each tenant gets isolated databases with role-based access control.

This milestone hardens critical security gaps discovered during a codebase audit — blocking issues that prevent production deployment.

## Core Value

**Every tenant database and module operation is properly authorized and isolated.** No unauthorized user can provision tenants or execute code.

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

### Active

- [ ] **C1**: Central admin authorization — only super-admin can access tenant/module CRUD routes
- [ ] **C2**: Module ZIP upload security — restrict to admin, prevent arbitrary PHP execution
- [ ] **C3**: Module state persistence — move `installed_modules` and `module_operations` from JSON blob on tenant record to dedicated database table

### Out of Scope (this milestone)

- Major issues from audit (duplicate host verification, EnsureModuleInstalled fix, CreateTenantAction transaction, Tenant model, repair migration) — next milestone
- Moderate/minor issues (N+1 traps, god controller refactor, nginx hardening, Docker fixes) — future milestones
- VPS public IP / custom domain deployment — ignored per user request

## Context

Brownfield project. Sole developer (Bhone Wai), 63 commits. The codebase audit (`docs/audit-2026-06-25.md`) identified 3 critical security issues that block any public deployment:

1. **No central admin authorization** — `routes/web.php:14` wraps all central routes in `auth` middleware only. `TenantStoreRequest::authorize()` returns `(bool) $this->user()`. Any authenticated user can create tenants or upload modules.
2. **ZIP module upload is RCE** — `ModuleZipInspector` detects dangerous files but does not block them. ZIPs are extracted directly into `base_path('Modules/')` within the web root.
3. **Module state race condition** — `TenantModuleRegistry` stores install status as JSON in the tenant `data` column using read-modify-write cycles. Docblock admits: "This is last-write-wins state."

The user wants to fix these, deploy, then tackle major issues in a follow-up phase as a learning exercise.

Codebase map exists at `.planning/codebase/` (1,933 lines across 7 documents).

## Constraints

- **Tech stack**: PHP 8.3, Laravel 12, Livewire 4, Alpine.js 3, Tailwind 3, MySQL 8.0 — must stay within existing stack
- **Authority**: Central admin identified by `CENTRAL_SUPERADMIN_EMAIL` / `CENTRAL_SUPERADMIN_PASSWORD` config — use existing env vars
- **Approach**: Educational — solo developer learning from each fix. Prefer clear, maintainable solutions over clever ones
- **Deployment**: Must be deployable after these 3 fixes (ignoring VPS public IP concern)

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Central auth via Gate + middleware | Simpler than a full role system at this stage; single super-admin is the current need | — Pending |
| Module ZIP: admin-only gate + content allowlist | Addresses both the auth gap and the extraction risk without rebuilding the module system | — Pending |
| Module state: dedicated `module_installations` pivot table | Database-level constraints prevent race conditions; simpler than distributed locking | — Pending |
| Fix order: C1 → C2 → C3 | C1 is foundational (all central routes depend on it), C2 depends on C1 being fixed first | — Pending |

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
*Last updated: 2026-06-25 after initialization*
