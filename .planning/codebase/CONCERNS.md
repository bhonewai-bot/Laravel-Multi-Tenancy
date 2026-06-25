# Codebase Concerns

**Analysis Date:** 2026-06-25

## Tech Debt

**Uncommitted Working Tree Changes:**
- Issue: 14 files are modified or untracked on the `dev` branch, including core blade templates (`resources/views/tenant/index.blade.php`, `resources/views/tenant/domains/index.blade.php`, etc.), the Alpine entrypoint (`resources/js/app.js`), and a new component (`resources/views/components/delete-modal.blade.php`). None are staged or committed.
- Files: `resources/js/app.js`, `resources/views/components/dropdown.blade.php`, `resources/views/components/modal.blade.php`, `resources/views/components/delete-modal.blade.php`, `resources/views/module-requests/index.blade.php`, `resources/views/modules/create.blade.php`, `resources/views/modules/index.blade.php`, `resources/views/tenant/create.blade.php`, `resources/views/tenant/domains/index.blade.php`, `resources/views/tenant/edit.blade.php`, `resources/views/tenant/index.blade.php`, `resources/views/tenant/modules/index.blade.php`, `resources/views/tenant/roles/index.blade.php`, `resources/views/tenant/users/index.blade.php`
- Impact: Uncommitted UI changes risk being lost or creating merge conflicts. Other contributors (or CI) cannot see or validate the current state of the views.
- Fix approach: Review and commit or stash these changes before starting new work. Establish a habit of committing before context-switching.

**env() Called Directly in Application Code:**
- Issue: `env()` is called directly in application service providers and controllers instead of using cached `config()` values. After `php artisan config:cache`, `env()` returns `null` for all keys, causing runtime failures.
- Files: `app/Providers/TenancyServiceProvider.php` (lines 53, 65 — `env('TENANCY_PROVISIONING_QUEUE')`), `app/Providers/TelescopeServiceProvider.php` (line 63 — `env('TELESCOPE_ALLOWED_EMAILS')`), `app/Http/Controllers/DomainCheckController.php` (line 25 — `env('DOMAIN_CHECK_TOKEN')`)
- Impact: Application will break in any environment where config is cached (production deployments that run `config:cache`).
- Fix approach: Move all `env()` reads into `config/*.php` files and reference them via `config()` in application code. Add `config/*.php` keys for `tenancy.provisioning_queue`, `telescope.allowed_emails`, and `domain_check.token`.

**Central Superadmin Password in Config with Hardcoded Default:**
- Issue: `config/auth.php` contains a hardcoded default password `'password'` for the central superadmin, referenced via `env('CENTRAL_SUPERADMIN_PASSWORD')`.
- Files: `config/auth.php` (line 24)
- Impact: If the env var is unset, the superadmin account is created with the password `password`. This is a severe production risk.
- Fix approach: Remove the default value or set it to an empty string. Require the env var to be explicitly set in `.env`. Add a startup assertion or deployment check.

**TenantModuleRegistry Last-Write-Wins Race Condition:**
- Issue: The `upsertModuleOperation()` method in `TenantModuleRegistry` uses refresh-then-save on the tenant JSON column. Concurrent queue workers processing different modules for the same tenant can overwrite each other's operation status.
- Files: `app/Services/TenantModuleRegistry.php` (lines 201-215)
- Impact: Module operation status displayed in the UI may be stale or incorrect after concurrent installs/uninstalls.
- Fix approach: Use a database-level atomic update (e.g., `DB::raw()` JSON set on the specific key) or introduce a dedicated `module_operations` table instead of a JSON column on tenants.

**EnsureModuleInstalled Middleware No-Op Mapping:**
- Issue: `EnsureModuleInstalled` applies `array_map(fn ($item) => $item, $installedModules)` which maps each item to itself — this is a no-op and suggests incomplete normalization logic.
- Files: `app/Http/Middleware/EnsureModuleInstalled.php` (lines 38-41)
- Impact: If module slugs in the `installed_modules` array are stored with inconsistent casing, the comparison on line 45 could fail to match.
- Fix approach: Either apply `Str::lower()` in the map callback, or remove the no-op `array_map` entirely.

**Empty Constructor in TenantDomainService:**
- Issue: `TenantDomainService` has an empty `__construct()` with a comment placeholder. This is dead code that adds noise.
- Files: `app/Services/TenantDomainService.php` (lines 20-23)
- Impact: Minor code cleanliness issue.
- Fix approach: Remove the empty constructor.

**Module Code Extracted to Production Filesystem Without Versioning:**
- Issue: `ModuleZipInspector::extract()` moves uploaded module ZIPs into `Modules/{name}` on the local filesystem. There is no versioning, update, or rollback mechanism. A second upload of the same module name is rejected.
- Files: `app/Services/ModuleZipInspector.php` (lines 129-179)
- Impact: Module updates require manual filesystem intervention. No way to roll back a broken module.
- Fix approach: Design a module versioning strategy before this feature is used in production. Consider a `module.json` version field and an `Modules/{name}/{version}/` directory structure.

## Known Bugs

**Password Not Hashed on User Creation:**
- Issue: `UserController::store()` passes `$validated['password']` directly to `User::create()` without hashing. If the `User` model does not have a `Hash::make()` cast on the `password` attribute, passwords are stored in plaintext.
- Files: `app/Http/Controllers/Tenant/UserController.php` (line 57)
- Trigger: Creating any user via the tenant user management UI.
- Workaround: Verify the `User` model has a mutator or cast that hashes the password. If not, this is a critical security bug.

**Password Not Hashed on User Update:**
- Issue: `UserController::update()` passes the raw password from the request into `$payload['password']` without hashing.
- Files: `app/Http/Controllers/Tenant/UserController.php` (line 124)
- Trigger: Updating a user with a new password via the tenant user management UI.
- Workaround: Same as above — ensure the model casts or mutates the password field.

## Security Considerations

**Module ZIP Upload Path Traversal Mitigation is Incomplete:**
- Risk: `ModuleZipInspector` checks for `../` and `/` prefixes in ZIP entry names, but the blocklist is not exhaustive. ZIP entries with encoded sequences (e.g., URL-encoded `..%2F`) or Windows-style backslashes (`..\`) may not be caught.
- Files: `app/Services/ModuleZipInspector.php` (lines 30-39)
- Current mitigation: Basic prefix/contains checks are present. Extracted files are constrained to the module root subtree.
- Recommendations: Use `ZipArchive::renameName()` to flatten paths, or validate that every extracted file resolves to a path under `Modules/{moduleName}/` after extraction. Add a post-extraction path audit.

**Module ZIP Allows PHP Execution from User Uploads:**
- Risk: Uploaded module ZIPs contain PHP files that are extracted into `Modules/` and become part of the application codebase. A malicious module could execute arbitrary PHP on the server.
- Files: `app/Services/ModuleZipInspector.php` (line 43), `app/Http/Controllers/ModuleController.php` (lines 41-73)
- Current mitigation: None — PHP files in ZIPs are explicitly allowed.
- Recommendations: Implement a module signing/verification system. At minimum, restrict module uploads to trusted administrators and log all module installations. Consider a `modules/` allowlist or marketplace model with server-side validation.

**Telescope Access Controlled by env() at Runtime:**
- Risk: The Telescope gate reads `TELESCOPE_ALLOWED_EMAILS` via `env()` on every request. If the env var is empty or unset, the gate closure may behave unexpectedly depending on PHP string casting.
- Files: `app/Providers/TelescopeServiceProvider.php` (lines 62-66)
- Current mitigation: Telescope filtering limits what data is captured in non-local environments.
- Recommendations: Move to `config()` and validate the config value during boot. Consider IP-based restrictions as a secondary layer.

**Domain Check Token Passed in Query String:**
- Risk: `DomainCheckController` accepts the shared authentication token as a query parameter (`?token=...`), which is logged in server access logs, browser history, and potentially proxy logs.
- Files: `app/Http/Controllers/DomainCheckController.php` (line 26)
- Current mitigation: The token is also accepted via the `X-Domain-Check-Token` header.
- Recommendations: Deprecate query-string token usage. Document that the header method is preferred. Ensure the token value is rotated periodically.

**Route Model Binding Resolves Centrally Without Policy:**
- Risk: Tenant resource routes using `Route::resource()` with model binding (e.g., `users`, `roles`) rely on the Stancl tenancy middleware to scope queries to the tenant database. If tenancy initialization fails silently, route model binding could resolve a model from a different tenant's database.
- Files: `routes/tenant.php` (lines 55-56), `app/Http/Controllers/Tenant/UserController.php`
- Current mitigation: `RejectInvalidTenantHost` middleware and `InitializeTenancyByDomain` middleware provide the first line of defense.
- Recommendations: Add explicit tenant ownership checks in controllers (as `DomainController` does) or use scoped route model binding where available.

## Performance Bottlenecks

**Cloudflare API Calls During Synchronous Domain Store:**
- Problem: `DomainController::store()` calls `$this->domainSyncService->sync()` synchronously before returning the redirect response. Cloudflare API calls add 1-3 seconds of latency to every domain creation.
- Files: `app/Http/Controllers/Tenant/DomainController.php` (lines 142-147)
- Cause: The `sync()` method performs an HTTP request to the Cloudflare Custom Hostnames API inline with the HTTP request lifecycle.
- Improvement path: Dispatch the Cloudflare sync to a queued job for all domain creation flows. Show a "pending" status in the UI immediately.

**Module Operation State Stored as JSON on Tenant Record:**
- Problem: Every module operation status update reads the full `module_operations` JSON column, modifies it in PHP, and writes the entire value back. As the number of installed modules grows, this read-modify-write cycle becomes increasingly expensive.
- Files: `app/Services/TenantModuleRegistry.php` (lines 201-215)
- Cause: JSON column updates require a full column rewrite on every operation.
- Improvement path: Normalize into a dedicated `module_operations` table with indexed columns for `tenant_id`, `slug`, and `status`.

**DNS TXT Verification Uses Synchronous DNS Lookup:**
- Problem: `TenantDomainService::checkDnsTxtVerification()` calls `dns_get_record()` which is a blocking network call. DNS timeouts can cause the verify action to hang for up to the system DNS timeout (often 5-10 seconds).
- Files: `app/Services/TenantDomainService.php` (lines 126-139)
- Cause: PHP's `dns_get_record()` is synchronous and does not respect application-level timeouts.
- Improvement path: Wrap in a timeout mechanism or move DNS verification to a queued job with its own timeout.

## Fragile Areas

**Tenant Module Installation Pipeline:**
- Files: `app/Jobs/InstallTenantModule.php`, `app/Services/TenantModuleInstaller.php`, `app/Services/TenantModuleRegistry.php`
- Why fragile: The pipeline spans a queued job, cache-based locking, Artisan command execution, and JSON column state management. Failures at any stage can leave the system in an inconsistent state (module partially installed, operation status stuck in "running").
- Safe modification: Always test module install/uninstall flows with queue workers running. Verify that `Tenant::end()` is called in the `finally` block (it is, in `InstallTenantModule`). Monitor for stuck "running" operations.
- Test coverage: `InstallTenantModule` job has no dedicated test. `TenantModuleInstaller` has no unit tests.

**Cloudflare Domain Sync State Machine:**
- Files: `app/Services/CloudflareService.php`, `app/Services/DomainCloudflareSyncService.php`, `app/Jobs/SyncPendingCloudflareDomain.php`
- Why fragile: Domain verification depends on a multi-step state machine (create -> pending_validation -> active) with external API dependencies. Cloudflare API failures can leave domains in intermediate states.
- Safe modification: Always handle `Throwable` in sync operations (the codebase does). Check `cf_error` field before displaying status to users.
- Test coverage: `CloudflareDomainStatusSyncTest.php` has 399 lines but relies on Mockery mocks of `CloudflareService`, not real API behavior.

**Cross-Tenant Data Isolation:**
- Files: `app/Http/Controllers/Tenant/DomainController.php`, `app/Http/Controllers/Tenant/UserController.php`, `app/Http/Controllers/Tenant/RoleController.php`
- Why fragile: Tenant isolation depends on correctly scoping all queries with `tenant_id` or relying on Stancl tenancy middleware. `DomainController` manually checks `$domain->tenant_id !== $tenant->id`, but `UserController` and `RoleController` rely solely on the tenancy middleware to scope the connection.
- Safe modification: When adding new tenant-scoped resources, always verify the tenant ownership check pattern used. Prefer explicit ownership checks over implicit connection scoping.
- Test coverage: `TenancyE2EFlowTest.php` covers the happy path but does not test cross-tenant access attempts.

## Scaling Limits

**SQLite Tenant Databases:**
- Current capacity: The application uses SQLite for tenant databases (evidenced by `tenant*` files in `database/` and `SQLiteDatabaseManager` in config). SQLite supports single-writer concurrency.
- Limit: Concurrent write requests to the same tenant will serialize and may cause `SQLITE_BUSY` errors under high load.
- Scaling path: Migrate production tenant databases to MySQL or PostgreSQL. The `tenancy.php` config already defines managers for all three drivers.

**Single Queue Worker in Docker Compose:**
- Current capacity: `docker-compose.yml` defines one `queue` service running `queue:work database`. This processes one job at a time.
- Limit: Module installs, domain syncs, and other queued work will queue up during high-traffic periods.
- Scaling path: Increase `--workers` or add multiple queue containers. Consider dedicated queues for module operations vs. domain syncs.

## Dependencies at Risk

**Stancl Tenancy (stancl/laravel-tenancy):**
- Risk: This is the core multi-tenancy framework. Version compatibility with Laravel 12 must be verified. The package uses event-driven database provisioning which can conflict with other service providers.
- Impact: A breaking change or incompatibility would affect all tenant operations.
- Migration plan: Pin to a tested version. Monitor the package's Laravel compatibility matrix. Consider `tenancyforlaravel.com` commercial support for production.

## Missing Critical Features

**No Module Update/Rollback Mechanism:**
- Problem: Modules can be installed and uninstalled, but there is no upgrade path. A module update requires manual filesystem manipulation and re-running migrations.
- Blocks: Production module lifecycle management, security patching for installed modules.

**No Cross-Tenant Access Attempt Logging:**
- Problem: When `abort(404)` is triggered by tenant ownership checks (e.g., in `DomainController::show()`), there is no security audit log recording the attempted cross-tenant access.
- Blocks: Security incident detection and forensics.

**No Rate Limiting on Auth Routes:**
- Problem: The `routes/tenant.php` auth routes (login, password reset) do not have explicit rate limiting middleware. Domain creation and status checks have `throttle` middleware, but authentication endpoints rely solely on Laravel's default rate limiter.
- Blocks: Brute-force protection for tenant user accounts.

## Test Coverage Gaps

**Module System Has No Tests:**
- What's not tested: `ModuleController`, `ModuleZipInspector`, `TenantModuleInstaller`, `InstallTenantModule` job, `UninstallTenantModule` job, `EnsureModuleInstalled` middleware.
- Files: `app/Http/Controllers/ModuleController.php`, `app/Services/ModuleZipInspector.php`, `app/Services/TenantModuleInstaller.php`, `app/Jobs/InstallTenantModule.php`, `app/Jobs/UninstallTenantModule.php`, `app/Http/Middleware/EnsureModuleInstalled.php`
- Risk: Module uploads could contain malicious code, module installs could corrupt tenant databases, and the install/uninstall state machine could leave tenants in broken states — all undetected.
- Priority: High

**Product Module Has Zero Tests:**
- What's not tested: The entire `Modules/Product` module — controllers, Livewire components, import services, and scraping integrations.
- Files: `Modules/Product/app/Http/Controllers/ProductController.php`, `Modules/Product/app/Livewire/ProductTable.php`, `Modules/Product/app/Services/Imports/`
- Risk: Product CRUD, import from external sources (Shopee, Lazada), and scraping logic are completely untested.
- Priority: Medium

**No Policy Unit Tests:**
- What's not tested: `RolePolicy`, `UserPolicy`, `ModuleRequestPolicy` are not tested in isolation.
- Files: `app/Policies/RolePolicy.php`, `app/Policies/UserPolicy.php`, `app/Policies/ModuleRequestPolicy.php`
- Risk: Authorization rules may silently deny legitimate access or allow unauthorized operations.
- Priority: High

**No Tenant Onboarding Integration Test:**
- What's not tested: The full `CreateTenantAction` flow including domain creation, Cloudflare sync, and database provisioning end-to-end.
- Files: `app/Actions/Tenants/CreateTenantAction.php`, `app/Actions/Tenants/SyncCloudflareDomainAction.php`
- Risk: New tenant provisioning could fail in production with no test catching the regression.
- Priority: High

---

*Concerns audit: 2026-06-25*
