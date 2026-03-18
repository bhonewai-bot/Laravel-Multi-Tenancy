# Operations Runbook

This runbook covers queue operations, failed job handling, tenant-aware logging, and backup/restore for central + tenant databases.

## 1) Queue Worker Operations

Current production note:
- The app is presently stabilized with simpler runtime choices on EC2:
  - `CACHE_STORE=file`
  - `SESSION_DRIVER=file`
  - background queue-driven Cloudflare polling should not be treated as the main operator path
- For tenant custom domains, the supported operator flow is:
  1. create/add domain
  2. add Cloudflare DNS/custom-hostname record
  3. use the tenant domain `Check Status` action
  4. visit the custom domain once it shows active

### Start services
```bash
docker compose up -d
```

### Worker status
```bash
docker compose ps
docker compose logs --tail=100 queue
```

### Restart worker after deploy/config change
```bash
docker compose exec app php artisan queue:restart
docker compose restart queue
```

### Runtime policy
- Queue driver is environment-dependent right now.
- Local/full async expectation: `database`
- Current stabilized EC2 production fallback may use: `sync`
- Worker command is defined in `docker-compose.yml` under `queue` service.
- Retry policy for risky module jobs:
  - `tries=3`
  - `backoff=[10,30,60]`
  - `timeout=120`
- Module install/uninstall is async and depends on this worker.

Production caveat:
- If the production environment is intentionally running with `QUEUE_CONNECTION=sync` during stabilization, the async/domain polling notes below do not apply until queue-backed runtime is restored.

## 2) Failed Job Operations

### Inspect failed jobs
```bash
docker compose exec app php artisan queue:failed
```

### Retry one failed job
```bash
docker compose exec app php artisan queue:retry <failed-job-uuid>
```

### Retry all failed jobs
```bash
docker compose exec app php artisan queue:retry all
```

### Remove failed job records after resolution
```bash
docker compose exec app php artisan queue:flush
```

## 3) Tenant-Aware Logging

Application logs include extra context:
- `tenant_id`
- `host`
- `context` (`tenant` or `central`)
- `request_id` (from `x-request-id` / `x-correlation-id` when present)
- `job_id` (for queue jobs)

### Check logs
```bash
docker compose exec app tail -n 200 storage/logs/laravel.log
```

### Suggested verification
1. Hit a central route (`app.localhost`).
2. Hit a tenant route (`t001.app.localhost`).
3. Trigger one queued module install/uninstall.
4. Confirm central and tenant log lines show different `context`/`tenant_id`.

## 3.1) Module Operation Watch-State (UI)

Module UI status behavior:
- `queued/running` -> UI shows `Installing...` or `Uninstalling...`
- `success/failed` -> UI shows terminal alert on watched refresh
- terminal operation is cleared after alert is rendered

If module status is stuck in processing:

```bash
docker compose logs --tail=200 queue
docker compose exec app php artisan queue:failed
docker compose exec app php artisan queue:retry all
```

If queue service had startup race with MySQL:

```bash
docker compose restart queue
```

## 3.2) Tenant Custom Domain Status Refresh

Primary production operator path:

1. Open tenant app
2. Go to `Custom Domains`
3. Click `Setup` for the pending domain
4. Click `Check Status`

Expected outcomes:
- if Cloudflare still reports pending state, the page keeps showing waiting status
- if Cloudflare reports hostname + SSL active, the domain becomes verified and starts serving traffic

Useful verification:
- visit `https://<tenant-domain>/login`
- confirm the domain detail page shows:
  - `Hostname Routing: Active`
  - `SSL Certificate: Active`
  - `Verification: Verified`

Emergency/manual recovery for a stuck domain:

```bash
docker compose exec app php artisan tinker --execute="\$d=\App\Models\Domain::where('domain','example.your-zone.com')->first(); app(\App\Services\DomainCloudflareSyncService::class)->sync(\$d); dump(\$d->fresh()->only(['domain','cf_hostname_status','cf_ssl_status','verified_at','cf_last_checked_at','cf_error']));"
```

Use this only when the UI path is insufficient or when diagnosing production state drift.

## 4) Database Backup

Backup scripts are in `scripts/ops`.

### Central DB backup
```bash
bash scripts/ops/backup-central.sh
```

### One tenant DB backup
```bash
bash scripts/ops/backup-tenant.sh t001
```

### List tenant DB names from central registry
```bash
bash scripts/ops/list-tenant-dbs.sh
```

## 5) Tenant Restore (Single-Tenant Recovery)

### Restore one tenant DB from dump
```bash
bash scripts/ops/restore-tenant.sh t001 storage/app/backups/tenantt001_YYYY-MM-DD_HHMMSS.sql
```

This script drops and recreates only `tenant{tenant_id}`.
It does not touch `central` or other tenant DBs.

## 6) Recovery Drill (Definition of Done)

Perform this once per release:

1. Choose tenant A (`t001`) and tenant B (`t002`).
2. Backup tenant A:
   ```bash
   bash scripts/ops/backup-tenant.sh t001
   ```
3. Simulate tenant A loss:
   ```bash
   docker compose exec -T mysql mysql -uroot -p"${DB_PASSWORD:-root}" -e "DROP DATABASE tenantt001;"
   ```
4. Restore tenant A:
   ```bash
   bash scripts/ops/restore-tenant.sh t001 <dump_file>
   ```
5. Validate:
   - tenant A domain can login and access data.
   - tenant B domain remains unaffected.
   - central DB (`tenants`, `domains`) is unchanged.

## 7) Optional Telescope (Local/Internal)

- Enable in `.env`: `TELESCOPE_ENABLED=true`
- Access: `https://app.localhost/telescope`
- Keep disabled by default outside local/dev.
