# Requirements: TenantSmith Security Hardening

**Defined:** 2026-06-25
**Core Value:** Every tenant database and module operation is properly authorized and isolated. No unauthorized user can provision tenants or execute code.

## v1 Requirements

### Authorization

- [ ] **AUTH-01**: Only the configured super-admin (matching `CENTRAL_SUPERADMIN_EMAIL`) can access central CRUD routes (tenants, modules, module-requests)
- [ ] **AUTH-02**: Non-admin authenticated users receive a 403 Forbidden response on central routes
- [ ] **AUTH-03**: The `TenantStoreRequest::authorize()` method checks admin status instead of `(bool) $this->user()`
- [ ] **AUTH-04**: Central admin Gate is defined in `AppServiceProvider` and reusable across middleware, requests, and views

### Upload Security

- [ ] **UPLOAD-01**: Module upload route is gated behind admin authorization (inherits from AUTH-01)
- [ ] **UPLOAD-02**: `ModuleZipInspector` blocks ZIPs containing PHP files (`.php`, `.phtml`, `.php3`, `.php4`, `.php5`, `.phar`) or other executable content (`.sh`, `.exe`, `.bat`, `.cgi`, `.pl`)
- [ ] **UPLOAD-03**: Extracted module files are validated against an allowlist of safe file types before being written to disk (config, migrations, seeders, views, routes)
- [ ] **UPLOAD-04**: `ModuleController::store()` catches specific exceptions only — not `\Throwable` — and logs full errors while showing generic messages to users

### Module State

- [ ] **STATE-01**: `installed_modules` moves from tenant `data` JSON column to a dedicated `module_installations` pivot table (`tenant_id`, `module_id`, `installed_at`)
- [ ] **STATE-02**: `module_operations` moves from tenant `data` JSON column to a dedicated `module_operations` table (`tenant_id`, `module_slug`, `action`, `status`, `message`, `created_at`, `updated_at`)
- [ ] **STATE-03**: All module state reads and writes use database transactions — each mutation is an atomic INSERT/UPDATE, never a read-modify-write on the tenant record
- [ ] **STATE-04**: `TenantModuleRegistry` is updated to read/write from the new tables, and existing JSON data is migrated in a migration `up()` method

## v2 Requirements

Deferred to next milestone.

- **MAJOR-01**: Consolidate `HostResolver` and `TenantDomainService` into one host verification implementation
- **MAJOR-02**: Fix `EnsureModuleInstalled` identity-map — change `fn($item) => $item` to `strtolower`
- **MAJOR-03**: Wrap `CreateTenantAction::execute()` in `DB::transaction()`
- **MAJOR-04**: Add accessors, scopes, and relationships to `Tenant` model
- **MAJOR-05**: Resolve repair migration issue — document root cause and add CI migration validation

## Out of Scope

| Feature | Reason |
|---------|--------|
| Full role-based central admin (multiple admins) | Single super-admin is sufficient for current scale |
| Module code signing / registry | Over-engineering for a solo developer — allowlist validation is adequate |
| Docker/infrastructure hardening | Separate milestone |
| Moderate/minor audit issues | Separate milestone |
| VPS public IP / custom domain deployment | Deferred per user request |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | Phase 1 | Pending |
| AUTH-02 | Phase 1 | Pending |
| AUTH-03 | Phase 1 | Pending |
| AUTH-04 | Phase 1 | Pending |
| UPLOAD-01 | Phase 1 | Pending |
| UPLOAD-02 | Phase 1 | Pending |
| UPLOAD-03 | Phase 1 | Pending |
| UPLOAD-04 | Phase 1 | Pending |
| STATE-01 | Phase 1 | Pending |
| STATE-02 | Phase 1 | Pending |
| STATE-03 | Phase 1 | Pending |
| STATE-04 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 12 total
- Mapped to phases: 12
- Unmapped: 0 ✓

---
*Requirements defined: 2026-06-25*
*Last updated: 2026-06-25 after initial definition*
