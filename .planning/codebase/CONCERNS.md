# Codebase Concerns

**Analysis Date:** 2026-06-25

## Tech Debt

**Asynchronous Queue Configuration:**
- Issue: Queue processing (`TENANCY_PROVISIONING_QUEUE`) defaults to synchronous execution in development. Production requires explicit env var configuration to enable async job processing, but there is no validation or warning if queue setup is incomplete.
- Files: `app/Providers/TenancyServiceProvider.php:53`, `app/Providers/TenancyServiceProvider.php:65`
- Impact: Tenant provisioning, module installation, and Cloudflare domain sync run synchronously by default, blocking HTTP requests during long operations and causing potential timeouts.
- Fix approach: Add env var validation on application boot and document required queue worker setup in deployment guides. Consider health check endpoint that validates queue worker status.

**Tenant State Management - Last-Write-Wins Race Condition:**
- Issue: `TenantModuleRegistry` uses `last-write-wins` semantics when updating `installed_modules` and `module_operations` JSON columns. While `refresh()` mitigates stale reads, concurrent module installs/uninstalls can still lose state.
- Files: `app/Services/TenantModuleRegistry.php:52`, `app/Services/TenantModuleRegistry.php:195`
- Impact: Module installation status can become inconsistent if multiple queue workers or HTTP requests modify the same tenant's module list simultaneously.
- Fix approach: Implement atomic JSON array operations using database-level JSON functions (PostgreSQL `jsonb_set`, MySQL `JSON_ARRAY_APPEND`) or use a dedicated pivot table for module state. Add distributed locking at the tenant level for module operations.

**Hardcoded Fallback Credentials:**
- Issue: `CENTRAL_SUPERADMIN_PASSWORD` has a hardcoded default value of `'password'` in auth configuration.
- Files: `config/auth.php:24`
- Impact: If env var is not set, the super admin password defaults to an insecure value. This is especially risky in development environments that might accidentally be exposed.
- Fix approach: Remove the default value and throw an exception if `CENTRAL_SUPERADMIN_PASSWORD` is not set in production environment.

**Module Migration Path Resolution:**
- Issue: Module migration file resolution uses a candidate-path approach with 4 different path patterns checked sequentially. This creates fragility and makes debugging failed module installs difficult.
- Files: `app/Services/TenantModuleInstaller.php:133-147`
- Impact: If module file structure doesn't match expected conventions, installation fails with a generic "Module files not found" error rather than helpful guidance on expected structure.
- Fix approach: Define a strict module manifest schema that declares migration paths. Validate module ZIP structure before installation to fail fast with actionable error messages.

**Sync Resource Conflict Detection:**
- Issue: `SyncedResourceChangedInForeignDatabase` event exists but has no listeners, meaning cross-database sync conflicts are detected but not resolved.
- Files: `app/Providers/TenancyServiceProvider.php:107`
- Impact: If a synced resource (tenant model) is modified in both central and tenant databases, last-write-wins silently overwrites changes without notification or conflict resolution.
- Fix approach: Implement conflict resolution listener that either queues manual review or applies configurable merge strategy based on resource type.

## Known Bugs

**Module Install Middleware Edge Case:**
- Symptoms: Module guard middleware does not account for modules in "installing" or "queued" state. Tenants with operations in progress might be blocked from accessing routes if the module hasn't been marked as installed yet.
- Files: `app/Http/Middleware/EnsureModuleInstalled.php:31`, `app/Http/Middleware/EnsureModuleInstalled.php:45`
- Trigger: User triggers module install and immediately navigates to module-protected route while install is still processing in background.
- Workaround: Manually add module to `installed_modules` array via tinker, or wait for install to complete and refresh.

**Missing Rate Limiting on Sensitive Operations:**
- Symptoms: Domain creation, module request, and module installation endpoints have no explicit rate limiting beyond default web middleware throttling.
- Files: `routes/tenant.php` (module install/request routes), `app/Http/Controllers/Tenant/DomainController.php:100`
- Trigger: Repeated form submissions or API abuse could create multiple pending operations or exhaust queue workers.
- Workaround: Implement rate limiting middleware on sensitive endpoints.

## Security Considerations

**Central Domain Route Scope Bypass Risk:**
- Risk: Tenant routes are registered via `TenantServiceProvider::mapRoutes()` only when tenant context is initialized, but the host resolution logic in `App\HostResolver` could potentially resolve central domains to tenant contexts if misconfigured.
- Files: `app/Providers/TenancyServiceProvider.php:159-165`, `app/Support/HostResolver.php`
- Current mitigation: Central domains are explicitly listed in `tenancy.central_domains` config. Middleware ordering ensures tenancy initialization happens first.
- Recommendations: Add explicit checks in route service provider to prevent central domain registration when tenant context is active. Implement domain ownership validation as a defense-in-depth layer.

**Cloudflare API Token Exposure:**
- Risk: Cloudflare API token is stored as an env var but could be logged if HTTP client debug mode is enabled or if exception handlers log request/response bodies.
- Files: `app/Services/CloudflareService.php:33`, `config/cloudflare.php:11`
- Current mitigation: Token is passed via `withToken()` which masks it in HTTP client debug output.
- Recommendations: Implement explicit log redaction for Cloudflare API calls. Add monitoring for unusual Cloudflare API activity patterns. Consider using Cloudflare's scoped API tokens with minimal required permissions.

**Tenant Database Migration Safety:**
- Risk: Tenant migrations run with `--force` flag by default, bypassing confirmation prompts. This is necessary for automated provisioning but risky if migrations contain destructive operations.
- Files: `config/tenancy.php:188`, `app/Services/TenantModuleInstaller.php:67`
- Current mitigation: Migrations are version-controlled and tested before deployment.
- Recommendations: Implement migration preflight checks that validate destructive operations (DROP, TRUNCATE) before execution. Add dry-run mode for module migrations to preview changes.

**Module ZIP Inspection Gaps:**
- Risk: Module ZIP files are inspected before installation but the inspection logic may not detect all malicious payloads (e.g., PHP code execution in migration files, SQL injection in seeders).
- Files: `app/Services/ModuleZipInspector.php` (inferred from references)
- Current mitigation: ZIP inspection blocks obviously malformed modules.
- Recommendations: Implement sandboxed module execution environment for testing module code before installation. Add static analysis rules for module migration and seeder files. Maintain allowlist of permitted file patterns in module ZIPs.

**Incomplete Audit Logging:**
- Risk: Module operations, domain changes, and user management actions are not systematically logged to an immutable audit trail.
- Files: `app/Jobs/InstallTenantModule.php:93`, `app/Jobs/UninstallTenantModule.php:93`
- Current mitigation: Basic logger calls exist for operation completion.
- Recommendations: Implement structured audit logging to a separate audit table or external service. Capture user ID, timestamp, tenant ID, action type, and affected resource for all state-changing operations.

## Performance Bottlenecks

**Tenant Metadata JSON Column Queries:**
- Problem: Querying `installed_modules` and `module_operations` from the tenants table requires parsing JSON columns on every request where module state is checked.
- Files: `app/Services/TenantModuleRegistry.php:30-38`, `app/Http/Middleware/EnsureModuleInstalled.php:31`
- Cause: Module state is stored as JSON in the central `tenants` table. Complex queries or frequent reads trigger repeated JSON parsing.
- Improvement path: Consider extracting module state to a dedicated `tenant_modules` pivot table for complex queries. Cache module state in Redis for high-frequency access patterns.

**N+1 Query Risk in Module Listing:**
- Problem: Module listing endpoints fetch all modules and then check operation state for each one in a loop, potentially causing N+1 queries.
- Files: `app/Http/Controllers/Tenant/ModuleRequestController.php:33-43`
- Cause: Module metadata, request status, installed status, and operation status are fetched separately and joined in PHP.
- Improvement path: Use eager loading for module relationships. Consider denormalizing module status into a single query that joins all relevant tables.

**Dashboard Statistics Queries:**
- Problem: Dashboard performs multiple separate COUNT queries for tenants, modules, requests, and users without caching.
- Files: `app/Http/Controllers/DashboardController.php:24-27`
- Cause: Statistics are computed fresh on every page load without caching.
- Improvement path: Implement dashboard statistics caching with TTL of 5-10 minutes. Use Redis or database query cache for frequently accessed aggregate counts.

## Fragile Areas

**Tenant Context Initialization Chain:**
- Files: `app/Providers/TenancyServiceProvider.php:175-191`
- Why fragile: Middleware priority ordering is critical. If new middleware is added without proper priority, tenant context may not initialize before downstream route logic, causing cross-tenant data leaks.
- Safe modification: Always test middleware changes with tenant isolation tests. Use `makeTenancyMiddlewareHighestPriority()` to prepend new tenancy middleware. Document any new middleware that must run after tenancy initialization.
- Test coverage: `tests/Feature/Tenancy/HostAccessPolicyTest.php`, `tests/Feature/Tenancy/TenancyE2EFlowTest.php`

**Module ZIP Handling and Installation:**
- Files: `app/Services/TenantModuleInstaller.php:45-81`, `app/Services/ModuleZipInspector.php` (inferred)
- Why fragile: Module installation executes arbitrary migration and seeder code. Malformed modules can corrupt tenant databases.
- Safe modification: Never skip ZIP inspection. Always run module installation in a transaction with rollback capability. Test module installation with intentionally malformed ZIPs to validate error handling.
- Test coverage: Limited to happy path scenarios. Edge cases (corrupted ZIPs, missing files, invalid migrations) need explicit test coverage.

**Cloudflare Domain Provisioning State Machine:**
- Files: `app/Services/DomainCloudflareSyncService.php`, `app/Jobs/SyncPendingCloudflareDomain.php`
- Why fragile: Domain provisioning involves multiple external API calls and state transitions. Network failures or Cloudflare API changes can leave domains in inconsistent states.
- Safe modification: Implement idempotent sync operations. Add retry logic with exponential backoff. Monitor for stuck domain states that haven't progressed in >30 minutes.
- Test coverage: `tests/Feature/Tenancy/CloudflareDomainStatusSyncTest.php`, `tests/Feature/Tenancy/TenantDomainLifecycleTest.php`

**Central/Tenant Database Connection Switching:**
- Files: `app/Http/Controllers/Tenant/DomainController.php:104`, `app/Jobs/InstallTenantModule.php:77`
- Why fragile: Switching between central and tenant database connections within the same request requires careful state management. Errors during connection switching can leave the application targeting the wrong database.
- Safe modification: Always use `try/finally` blocks with `Tenancy::end()` to ensure context is properly restored. Validate connection state before critical writes. Test error scenarios where job fails mid-tenancy-context.
- Test coverage: `tests/Feature/Tenancy/TenancyE2EFlowTest.php`

## Scaling Limits

**Tenant Database File Storage:**
- Current capacity: SQLite databases limited to ~281 TB theoretical maximum, but practical limits ~100 GB before performance degrades.
- Limit: Each tenant gets a separate SQLite database file. File system I/O becomes a bottleneck with >1000 concurrent tenants.
- Scaling path: Migrate to MySQL/PostgreSQL for tenant databases. Implement connection pooling. Consider database-per-tenant vs schema-per-tenant strategy based on tenant count and query volume.

**Module Queue Worker Throughput:**
- Current capacity: Single queue worker processes one job at a time. Module installation takes 10-60 seconds per job.
- Limit: With single worker, queue backs up if >10 module installations are triggered simultaneously.
- Scaling path: Scale queue workers horizontally. Implement job prioritization (critical operations first). Add queue depth monitoring and auto-scaling triggers.

**Central Database JSON Column Updates:**
- Current capacity: JSON column updates on `tenants` table require full row locks. Concurrent updates to same tenant create contention.
- Limit: >50 concurrent module operations on same tenant causes lock contention and timeouts.
- Scaling path: Use atomic database operations for JSON array modifications. Implement optimistic locking with version columns. Consider event-sourcing pattern for module state changes.

## Dependencies at Risk

**nwidart/laravel-modules:**
- Risk: This package provides modular architecture but hasn't been updated for Laravel 12 compatibility. It may have security vulnerabilities or breaking changes in future updates.
- Impact: Module installation and management functionality depends on this package. Migration away would require rewriting module resolution logic.
- Migration plan: Evaluate `spatie/laravel-medialibrary` or custom module registry. Implement module interface contract to reduce coupling.

**stancl/tenancy:**
- Risk: Core tenancy package with deep integration into database, cache, filesystem, and queue layers. Breaking changes or deprecation would require significant refactoring.
- Impact: Complete application architecture depends on this package. All tenant isolation, database provisioning, and context switching rely on it.
- Migration plan: Maintain upgrade path documentation. Test with new versions in staging environment before production. Consider abstraction layer for tenancy operations to reduce direct package dependency.

**laravel/telescope (Development Dependency):**
- Risk: Telescope provides debugging interface but can expose sensitive data in production if not properly secured. May have performance overhead on high-traffic applications.
- Impact: Application performance and security posture.
- Migration plan: Ensure Telescope is disabled or password-protected in production. Implement environment-based configuration to disable Telescope in production. Consider removing from production deployment entirely.

## Missing Critical Features

**Comprehensive Audit Trail:**
- Problem: No systematic logging of who did what, when, and to which tenant/resource. Critical for compliance and security incident response.
- Blocks: Regulatory compliance (SOC2, GDPR), security forensics, user accountability.

**Rollback Capability for Module Operations:**
- Problem: Module installation cannot be rolled back if it fails partway through. Manual intervention is required to clean up partial installations.
- Blocks: Reliable module marketplace, zero-downtime module updates, automated module testing.

**Module Health Checks and Status Monitoring:**
- Problem: No health check endpoint to verify module installation integrity or detect corruption.
- Blocks: Automated monitoring, proactive issue detection, module marketplace quality assurance.

**Multi-Factor Authentication for Central Admin:**
- Problem: Central admin users (super admin) can manage all tenants and modules without MFA requirement.
- Blocks: Security best practices, compliance requirements for privileged access.

**Tenant Rate Limiting and Quotas:**
- Problem: No limits on tenant resource consumption (module installs, domain additions, user creation).
- Blocks: Cost control, abuse prevention, fair resource allocation across tenants.

## Test Coverage Gaps

**Module Installation Failure Scenarios:**
- What's not tested: Corrupted ZIP files, missing migration files, failed seeders, partial installation rollback, concurrent installation attempts on same tenant.
- Files: `tests/Feature/Tenancy/TenancyE2EFlowTest.php` (limited to happy path)
- Risk: Silent data corruption or inconsistent state in production when modules fail to install correctly.
- Priority: High

**Concurrent Operation Handling:**
- What's not tested: Multiple queue workers processing module operations for same tenant simultaneously, race conditions in module state updates, distributed lock failures.
- Files: `app/Services/TenantModuleRegistry.php`, `app/Services/TenantModuleInstaller.php`
- Risk: Module installation status becomes inconsistent, leading to tenants being blocked from accessing installed modules or modules being installed twice.
- Priority: High

**Cross-Tenant Data Isolation Validation:**
- What's not tested: Verification that tenant A cannot access tenant B's data even with malformed requests, unauthorized API calls, or SQL injection attempts.
- Files: `app/Http/Middleware/EnsureTenantPermission.php`, `app/Http/Middleware/EnsureTenantRole.php`
- Risk: Security breach if tenant isolation can be circumvented through edge cases not covered by existing tests.
- Priority: Critical

**Cloudflare API Failure Handling:**
- What's not tested: Cloudflare API timeouts, rate limiting, authentication failures, partial failures where hostname is created but SSL validation fails.
- Files: `app/Services/CloudflareService.php`, `app/Services/DomainCloudflareSyncService.php`
- Risk: Domains stuck in provisioning state, user confusion about domain status, orphaned Cloudflare resources.
- Priority: Medium

**Queue Worker Failure Recovery:**
- What's not tested: Queue worker crashes during module installation, memory exhaustion, database connection loss during long-running jobs.
- Files: `app/Jobs/InstallTenantModule.php`, `app/Jobs/UninstallTenantModule.php`
- Risk: Module operations left in "running" state indefinitely, requiring manual intervention to reset.
- Priority: High

---

*Concerns audit: 2026-06-25*
