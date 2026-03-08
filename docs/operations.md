# Operations Runbook

This runbook covers queue operations, failed job handling, tenant-aware logging, and backup/restore for central + tenant databases.

## 1) Queue Worker Operations

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
- Queue driver: `database`
- Worker command is defined in `docker-compose.yml` under `queue` service.
- Retry policy for risky module jobs:
  - `tries=3`
  - `backoff=[10,30,60]`
  - `timeout=120`
- Module install/uninstall is async and depends on this worker.

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
