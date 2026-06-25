# Roadmap: TenantSmith Security Hardening

## Overview

Three critical security fixes that block production deployment. Each phase delivers an independently valuable security improvement: first, lock down central admin access so only the super-admin can manage tenants and modules; second, harden the module upload pipeline so malicious ZIPs cannot execute arbitrary code; third, replace race-prone JSON blob state with proper database tables. The phases follow a strict dependency chain (C1 -> C2 -> C3) where each builds on the authorization foundation established by the previous.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Central Admin Authorization** - Gate all central routes behind a super-admin check so only the configured admin can manage tenants, modules, and module requests
- [ ] **Phase 2: Module Upload Security** - Restrict module upload to admins and block ZIPs containing executable file types before extraction to disk
- [ ] **Phase 3: Module State Persistence** - Replace JSON blob read-modify-write with dedicated database tables and atomic transactions for module install/operation tracking

## Phase Details

### Phase 1: Central Admin Authorization
**Goal**: Only the configured super-admin can access central CRUD routes -- any other authenticated user is blocked
**Depends on**: Nothing (first phase)
**Requirements**: AUTH-01, AUTH-02, AUTH-03, AUTH-04
**Success Criteria** (what must be TRUE):
  1. A user whose email matches `CENTRAL_SUPERADMIN_EMAIL` can access all central routes (tenant CRUD, module management, module requests)
  2. An authenticated user whose email does NOT match `CENTRAL_SUPERADMIN_EMAIL` receives a 403 Forbidden response when attempting to access any central route
  3. The `TenantStoreRequest::authorize()` method rejects non-admin users instead of accepting any authenticated user
  4. The admin Gate is defined once in `AppServiceProvider` and used consistently by middleware, form requests, and Blade views
**Plans**: TBD

Plans:
- [ ] 01-01: TBD
- [ ] 01-02: TBD

### Phase 2: Module Upload Security
**Goal**: Module ZIP uploads are restricted to admins and sanitized so that no executable file types can be extracted to the filesystem
**Depends on**: Phase 1
**Requirements**: UPLOAD-01, UPLOAD-02, UPLOAD-03, UPLOAD-04
**Success Criteria** (what must be TRUE):
  1. An authenticated non-admin user cannot access the module upload route (receives 403)
  2. Attempting to upload a ZIP containing `.php`, `.phtml`, `.phar`, `.sh`, `.exe`, `.bat`, `.cgi`, `.pl`, or similar executable files is rejected with a clear error message before extraction
  3. Only files matching the safe allowlist (config, migrations, seeders, views, routes) are written to disk during module extraction
  4. When a module upload fails, the user sees a generic error message while the full error details are written to the Laravel log
**Plans**: TBD

Plans:
- [ ] 02-01: TBD
- [ ] 02-02: TBD

### Phase 3: Module State Persistence
**Goal**: Module installation and operation records are stored in dedicated database tables with atomic transactions, eliminating race conditions from JSON blob read-modify-write
**Depends on**: Phase 2
**Requirements**: STATE-01, STATE-02, STATE-03, STATE-04
**Success Criteria** (what must be TRUE):
  1. Installed module records are stored in a `module_installations` table with `tenant_id`, `module_id`, and `installed_at` columns -- not in the tenant `data` JSON column
  2. Module operation records (install, uninstall) are stored in a `module_operations` table with `tenant_id`, `module_slug`, `action`, `status`, `message`, and timestamps
  3. All module state reads and writes happen inside database transactions -- no read-modify-write on the tenant record
  4. Existing JSON blob data is migrated into the new tables during the migration, and `TenantModuleRegistry` reads/writes from the new tables
**Plans**: TBD

Plans:
- [ ] 03-01: TBD
- [ ] 03-02: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 -> 2 -> 3

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Central Admin Authorization | 0/2 | Not started | - |
| 2. Module Upload Security | 0/2 | Not started | - |
| 3. Module State Persistence | 0/2 | Not started | - |
