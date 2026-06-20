# Multi-Tenancy Architecture

## Package

**stancl/tenancy v3.9** — database-per-tenant isolation with domain-based identification.

## All files involved

### Models (central DB)

| File                           | Purpose                                                    |
| ------------------------------ | ---------------------------------------------------------- |
| `app/Models/Tenant.php`        | Extends `BaseTenant`, uses `HasDatabase, HasDomains`       |
| `app/Models/Domain.php`        | Custom fillable/casts for Cloudflare + verification fields |
| `app/Models/User.php`          | Central admin users, has `role()` BelongsTo                |
| `app/Models/Module.php`        | Central module catalog, uses `CentralConnection`           |
| `app/Models/ModuleRequest.php` | Tenant module requests, uses `CentralConnection`           |

### Controllers

| File                                                             | Purpose                                              |
| ---------------------------------------------------------------- | ---------------------------------------------------- |
| `app/Http/Controllers/DomainCheckController.php`                 | `GET /internal/domain-check` — Caddy on-demand TLS   |
| `app/Http/Controllers/CloudflareHostnameChallengeController.php` | `GET /.well-known/cf-custom-hostname-challenge/{id}` |
| `app/Http/Controllers/TenantController.php`                      | Central CRUD for tenants                             |
| `app/Http/Controllers/Tenant/DomainController.php`               | Tenant domain CRUD + verify + checkStatus            |

### Middleware (tenant route stack)

| File                                              | Order      | Purpose                                 |
| ------------------------------------------------- | ---------- | --------------------------------------- |
| `app/Http/Middleware/RejectInvalidTenantHost.php` | 2nd        | Blocks central/unknown/unverified hosts |
| `app/Http/Middleware/EnsureModuleInstalled.php`   | After auth | Checks `installed_modules` attribute    |
| `app/Http/Middleware/EnsureTenantRole.php`        | After auth | Role-based access                       |
| `app/Http/Middleware/EnsureTenantPermission.php`  | After auth | Permission-based access                 |

### Services

| File                                           | Purpose                                                   |
| ---------------------------------------------- | --------------------------------------------------------- |
| `app/Services/CloudflareService.php`           | Cloudflare Custom Hostnames API wrapper                   |
| `app/Services/DomainCloudflareSyncService.php` | Syncs Cloudflare state → Domain model (SOURCE OF TRUTH)   |
| `app/Services/TenantDomainService.php`         | Normalization, central domain check, DNS TXT verification |

### Actions

| File                                                 | Purpose                                        |
| ---------------------------------------------------- | ---------------------------------------------- |
| `app/Actions/Tenants/CreateTenantAction.php`         | Creates Tenant + Domain + syncs Cloudflare     |
| `app/Actions/Tenants/UpdateTenantAction.php`         | Updates Tenant + Domain + syncs Cloudflare     |
| `app/Actions/Tenants/SyncCloudflareDomainAction.php` | Cloudflare sync wrapper with graceful fallback |

### Jobs

| File                                       | Purpose                                            |
| ------------------------------------------ | -------------------------------------------------- |
| `app/Jobs/SyncPendingCloudflareDomain.php` | Polls Cloudflare up to 15× at 2-min intervals      |
| `app/Jobs/InstallTenantModule.php`         | Initializes tenancy, runs tenant module migrations |

### Support

| File                           | Purpose                                                                                   |
| ------------------------------ | ----------------------------------------------------------------------------------------- |
| `app/Support/HostResolver.php` | `isCentralHost()`, `findTenantDomain()`, `isVerifiedTenantHost()`, `canServeTenantHost()` |
| `app/Support/AppHome.php`      | Post-login redirect: `/dashboard` if tenant, `/tenants` if central                        |

### Config

| File                    | Key contents                                                       |
| ----------------------- | ------------------------------------------------------------------ |
| `config/tenancy.php`    | Central domains, bootstrappers, DB prefix, filesystem, features    |
| `config/cloudflare.php` | API credentials, fallback origin, validation method, async polling |
| `bootstrap/app.php`     | Middleware aliases, host-agnostic routes, central domain routing   |

### Routes

| File                 | Purpose                                   |
| -------------------- | ----------------------------------------- |
| `routes/web.php`     | Central routes (only on central domains)  |
| `routes/tenant.php`  | Tenant routes (with middleware stack)     |
| `routes/auth.php`    | Shared auth routes, included by both      |
| `routes/console.php` | `domains:sync-cloudflare` Artisan command |

### Migrations

- Central: `database/migrations/` — tenants, domains, users, modules, jobs, cache
- Tenant: `database/migrations/tenant/` — roles, features, permissions, users, jobs, cache

### Tests

All in `tests/Feature/Tenancy/`:

- `TenancyE2EFlowTest.php`
- `TenantDomainLifecycleTest.php`
- `CloudflareDomainStatusSyncTest.php`
- `HostAccessPolicyTest.php`
- `DomainCheckTest.php`
- `TenantOnboardingTest.php`
- `TenantBootstrapSeederTest.php`

## Domain model (central `domains` table)

| Column               | Type              | Purpose                                 |
| -------------------- | ----------------- | --------------------------------------- |
| `id`                 | auto-increment PK |                                         |
| `domain`             | string, unique    | The hostname (e.g. `shop.acmecorp.com`) |
| `tenant_id`          | FK → tenants      |                                         |
| `cf_hostname_id`     | string, unique    | Cloudflare custom hostname ID           |
| `cf_hostname_status` | string            | `active` / `pending` / `deleted`        |
| `cf_ssl_status`      | string            | `active` / `pending` / `deleted`        |
| `cf_error`           | text              | Error from Cloudflare API               |
| `cf_payload`         | JSON              | Full Cloudflare response                |
| `cf_last_checked_at` | datetime          | Last poll timestamp                     |
| `verified_at`        | datetime          | When the app marked this domain trusted |
| `verification_code`  | string            | For legacy DNS TXT verification         |

## Middleware pipeline (full order)

### Tenant request (e.g. `acme.app.localhost/dashboard`)

```
1. Caddy reverse proxy
   → GET /internal/domain-check?domain=acme.app.localhost&token=...
   → DomainCheckController checks domains table (verified_at NOT NULL or central)
   → If pass → request reaches app

2. web group (Laravel defaults)
   - EncryptCookies
   - AddQueuedCookiesToResponse
   - StartSession
   - ShareErrorsFromSession
   - ValidateCsrfToken
   - SubstituteBindings

3. RejectInvalidTenantHost
   - isCentralHost($host) → 404
   - findTenantDomain($host) → null? → 404
   - canServeTenantHost($host) → false? → 403

4. InitializeTenancyByDomain (stancl)
   - Resolves tenant by domain from domains table
   - Creates dynamic 'tenant' DB connection
   - Runs bootstrappers: Database, Cache, Filesystem, Queue

5. PreventAccessFromCentralDomains (stancl)
   - Double-check: 404 if on central domain

6. auth (authenticated routes only)

7. Route handler → Controller
```

### Central request (e.g. `app.localhost/tenants`)

```
1. route registration in bootstrap/app.php
   → Route::domain('app.localhost') → routes/web.php

2. web group (Laravel defaults)

3. auth (authenticated routes only)

4. Route handler → Controller
```

### Host-agnostic routes (no domain constraint)

These run before tenant/central routing:

- `GET /internal/domain-check` — throttled 120:1, hits DomainCheckController
- `GET /.well-known/cf-custom-hostname-challenge/{hostnameId}` — web middleware, hits CloudflareHostnameChallengeController

## Database naming

- Central DB: whatever `DB_CONNECTION` points to (currently `mysql`, database `central`)
- Tenant DBs: `tenant{tenant_id}` (e.g. `tenantacme123`)
- Tenant connection name at runtime: `tenant` (dynamic, created by DatabaseTenancyBootstrapper)

## Bootstrappers (run when tenancy initializes)

1. `DatabaseTenancyBootstrapper` — switches to tenant DB
2. `CacheTenancyBootstrapper` — tags cache with `tenant_{id}`
3. `FilesystemTenancyBootstrapper` — suffixes storage paths with `tenant{id}/`
4. `QueueTenancyBootstrapper` — prefixes queue payloads with tenant context

## Known issues

1. **CloudflareService error handling** — `createHostname()` sends any valid domain to Cloudflare as designed (SSL for SaaS supports ANY domain the tenant owns). Cloudflare's API errors are wrapped in `RuntimeException` via try/catch on `RequestException`. Reserved domains (RFC 2606, e.g. `example.com`) will be rejected by Cloudflare with a descriptive error message.

2. **`EnsureVerifiedTenantDomain` middleware missing** — Referenced by tests but file doesn't exist. Tests that instantiate it will throw ReflectionException.

3. **`$tries=3` on SyncPendingCloudflareDomain is misleading** — The job dispatches itself recursively up to 15 times, so queue-level `$tries` doesn't control the polling cycle.

4. **`shouldRetry()` stops on transient errors** — A single network timeout sets `cf_error` and permanently stops retries. Should distinguish terminal errors (1411) from transient (timeout).

5. **Console command duplicates sync logic** — `domains:sync-cloudflare` calls `CloudflareService` directly instead of delegating to `DomainCloudflareSyncService`.
