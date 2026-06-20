---
name: multi-tenancy
description: "Apply when working on multi-tenancy: tenant creation, domain management, host resolution, middleware pipeline, Cloudflare SSL for SaaS, or database-per-tenant architecture. Covers stancl/tenancy v3 configuration, domain verification flows, and the RejectInvalidTenantHost middleware. For diagnosing broken custom domains in production, prefer diagnose-custom-domain."
metadata:
    author: team
---

# Multi-Tenancy

This application uses **stancl/tenancy v3** with database-per-tenant isolation. Tenants are identified by domain, and custom domains are verified through Cloudflare SSL for SaaS before serving traffic.

For the full architecture map, load `references/architecture.md`. For the Cloudflare flow specifically, load `references/cloudflare.md`.

## Before touching code

1. Check whether the change affects central DB, tenant DBs, or both:
   - Domain records, tenant records, modules → **central** database
   - Users, roles, permissions, tenant data → **tenant** databases
2. If adding columns to a table, confirm which database the table lives in.
3. Verify with `search-docs` for stancl/tenancy v3 API before calling package methods.

## Common tasks

### Add a new tenancy feature (column, config, service)

1. If it's a domain column → central migration on `domains` table
2. If it's a tenant column → central migration on `tenants` table (use `data` JSON column or add a real column)
3. If it's tenant-internal → tenant migration in `database/migrations/tenant/`
4. Update `App\Models\Domain` or `App\Models\Tenant` as needed
5. Never call Cloudflare from request-routing code — `HostResolver` is read-only, local DB only

### Understand why a host can or can't access tenant routes

The decision tree (see `references/architecture.md` for the full pipeline):

```
Host hits app
  → isCentralHost?  → YES → 404 (tenant routes don't serve central)
  → NO
  → findTenantDomain(host) → null? → 404 (unknown domain)
  → found
  → canServeTenantHost(host) → false? → 403 (unverified)
  → true → serve tenant routes
```

`canServeTenantHost` returns true when:
- `domain.verified_at !== null` (Cloudflare sync completed), OR
- The domain is a platform subdomain (ends with a configured central domain, e.g. `t001.app.localhost`)

### Create a tenant

Tenant creation flow: `CreateTenantAction` → creates Tenant model + Domain record + syncs Cloudflare if enabled.

Key config:
- Tenant DB name: `tenant{tenant_id}` (prefix `tenant`, suffix empty)
- Central connection: `env('DB_CONNECTION')` (currently `mysql`)
- Tenant model: `App\Models\Tenant` (extends `Stancl\Tenancy\Database\Models\Tenant`)

### Add a custom domain for a tenant

1. Tenant POSTs domain to `DomainController::store()`
2. Validated: unique on central DB, not a central domain, not a primary subdomain
3. Domain record saved with `verified_at = null`
4. If `config('cloudflare.enabled')` is true → `DomainCloudflareSyncService::sync()` creates the Cloudflare custom hostname
5. Cloudflare provisions SSL (2-5 minutes normally)
6. `SyncPendingCloudflareDomain` job polls every 2 minutes (up to 15 times, 30 min total)
7. When `cf_hostname_status = 'active'` AND `cf_ssl_status = 'active'` → `verified_at = now()`
8. Domain can now serve traffic

### Debug local dev issues

If `CLOUDFLARE_ENABLED=true` locally:
- Cloudflare can't reach `localhost` → all custom hostname API calls fail
- Solution: set `CLOUDFLARE_ENABLED=false` in `.env` for local dev
- Platform subdomains (`.app.localhost`) still work without Cloudflare

## Key invariants (do not break these)

1. **`HostResolver` never calls Cloudflare.** It only queries the local `domains` table. Cloudflare sync must have already happened.
2. **`DomainCloudflareSyncService` is the single source of truth for sync rules.** Controllers and commands must not call `CloudflareService` directly.
3. **Central domains and tenant domains are separate.** Central routes (`routes/web.php`) only run on central domains. Tenant routes (`routes/tenant.php`) run on everything else but are gated by `RejectInvalidTenantHost`.
4. **`verified_at` is set ONLY by `DomainCloudflareSyncService::sync()`.** Never set it manually in controllers or commands.
5. **Database-per-tenant means migrations must be run per-tenant** via `php artisan tenants:migrate`.

## Configuration quick reference

| Config | Key | Default | Notes |
|--------|-----|---------|-------|
| Central domains | `tenancy.central_domains` | `[env('TENANCY_CENTRAL_DOMAIN')]` | Currently `['app.localhost']` |
| Tenant DB prefix | `tenancy.database.prefix` | `'tenant'` | DB names: `tenant{id}` |
| Cloudflare enabled | `cloudflare.enabled` | `false` | Turn off locally |
| Cloudflare zone | `cloudflare.api.zone_id` | from env | Zone for `bhonewai.cc.cd` |
| Cloudflare async | `cloudflare.async_polling` | `false` | Set true in production |
| Cloudflare zone domain | `cloudflare.api.zone_domain` | from env | Currently `bhonewai.cc.cd` |
| Fallback origin | `cloudflare.fallback_origin` | `proxy-fallback.bhonewai.cc.cd` | CNAME target for tenants |

## Knowledge maintenance

When you discover something that contradicts or extends `references/architecture.md` or `references/cloudflare.md`:

1. Propose the update and ask permission
2. After approval, edit the reference file
3. Append an entry to `references/log.md`

See `.claude/WRITE_BACK.md` for the full convention.

## Related skills

- `diagnose-custom-domain` — when a tenant reports their domain isn't working (403, 404, SSL errors)
- `deploying-laravel-cloud` — deployment procedures
