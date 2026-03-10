# Cloudflare Custom Domain Handoff Backup

Last updated: 2026-03-10 (Asia/Yangon)

This file is a thread-restart backup for continuing Cloudflare custom hostname work in another chat.

## 1) Project Context

- Working repo: `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy`
- Current branch: `main`
- Reference repo: `/Users/appleclub/Documents/Professional Product Lab/cloudflare/lara-ums`
- Reference branch used for behavior study: `origin/dev/cloudflare-custom-hostnames`
  - branch tip observed: `4156471c90d72cbe6839aaef1c2fdc5e74ed1dbc`

Important: the target is behavior parity (Cloudflare domain lifecycle), not copy-paste UI/code.

## 2) Agreed Architecture

1. Caddy is for local development only.
2. Production flow is Cloudflare -> origin (no Caddy in prod compose).
3. `verified_at` remains the tenant-domain gate in middleware.
4. Cloudflare status sync (`check-status`) is responsible for setting/unsetting `verified_at`.

## 3) Step Status (Plan Tracking)

### Step 1 Freeze architecture
- Status: done.

### Step 2 Production compose profile
- Status: done (compose config validates).
- Added/updated:
  - `docker-compose.prod.yml`
  - `docker/nginx/conf.d/prod/app.conf`
  - `docker/nginx/ssl/README.md`

### Step 3 Cloudflare config
- Status: done.
- Added/updated:
  - `config/cloudflare.php`
  - `.env.example` Cloudflare keys
- Verified via tinker:
  - `config('cloudflare.enabled') === true`
  - `config('cloudflare.api.zone_id')` set

### Step 4 domains table + model fields
- Status: done.
- Added/updated:
  - `database/migrations/2026_03_09_063344_add_cloudflare_fields_to_domains_table.php`
  - `app/Models/Domain.php`
- Note: migration rollback index drops were fixed.

### Step 5 Cloudflare service
- Status: done.
- Added/updated:
  - `app/Services/CloudflareService.php`
- Key methods now available:
  - `createHostname()`
  - `getHostname()`
  - `mapStatuses()` (+ `mapStatus()` compatibility alias)

### Step 6 Wire domain creation
- Status: done.
- Updated:
  - `app/Http/Controllers/Tenant/DomainController.php` (`store()`)
- Behavior:
  - create domain row
  - call Cloudflare create
  - persist `cf_*`
  - set `verified_at` only on active/active

### Step 7 Check status endpoint
- Status: done.
- Updated:
  - `routes/tenant.php` (`POST /domains/{domain}/check-status`)
  - `DomainController::checkStatus()`

### Step 8 UI updates
- Status: done (own design, not copied).
- Updated:
  - `resources/views/tenant/domains/index.blade.php`
  - `resources/views/tenant/domains/create.blade.php`
  - `resources/views/tenant/domains/show.blade.php` (new)
  - `routes/tenant.php` + `DomainController::show()`

### Step 9 Middleware gate
- Status: done.
- Gate stays in:
  - `app/Http/Middleware/EnsureVerifiedTenantDomain.php`
- Gate is `verified_at`-based (via `TenantDomainService::canUseAsTenantDomain()` path).

### Step 10 tests
- Status: done.
- Added:
  - `tests/Feature/Tenancy/CloudflareDomainStatusSyncTest.php`
- Updated:
  - `tests/Feature/Tenancy/TenancyE2EFlowTest.php` custom-domain flow now mocks Cloudflare lifecycle.
- Checkpoint:
  - `php artisan test tests/Feature/Tenancy` -> passing (18 tests).

### Step 11 Cloudflare runbook
- Status: partially done (behavior understood, docs can be expanded).

## 4) Current Known Issue (Non-blocking for Step 9/10)

Observed in browser for real public host: `525 SSL handshake failed`.

Interpretation:
- Cloudflare custom hostname status can be active, but origin TLS handshake can still fail.
- This is origin ingress/TLS runtime setup, not domain lifecycle logic.

Current position:
- Since this thread scope is app workflow and testable domain lifecycle, Step 9/10 were completed first.
- Origin TLS hardening in real infra should be handled as a separate deployment task.

## 5) Security Note

A Cloudflare API token was exposed during chat/screenshots. Rotate/revoke old token and update `.env` with the new token.

## 6) Local Workspace State (when this backup was written)

Tracked modified files:
- `.env.example`
- `README.md`
- `app/Http/Controllers/Tenant/DomainController.php`
- `app/Services/CloudflareService.php`
- `docker-compose.prod.yml`
- `tests/Feature/Tenancy/TenancyE2EFlowTest.php`

New files/directories:
- `docker/nginx/conf.d/prod/app.conf`
- `docker/nginx/ssl/README.md`
- `tests/Feature/Tenancy/CloudflareDomainStatusSyncTest.php`
- `docs/cloudflare-handoff-backup.md`

Untracked tenant sqlite artifacts (safe to clean/ignore):
- `database/tenant*`

## 7) Suggested Next Thread Prompt

Use this in the next chat:

```text
Continue from docs/cloudflare-handoff-backup.md in Laravel-Multi-Tenancy.
Focus now on production/origin TLS 525 fix and final Cloudflare runbook docs.
Do not change verified_at middleware gating logic.
Keep Caddy local-only and retain Cloudflare status-sync flow/tests.
```

