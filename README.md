# Laravel Multi-Tenancy

Production-style multi-tenant SaaS skeleton built with Laravel + `stancl/tenancy`.

## Architecture at a glance

- Pattern: modular monolith (single Laravel app).
- Isolation: database-per-tenant (`tenant{tenant_id}`) + central shared DB (`central`).
- Tenant resolution: by domain/host.
- Core lifecycle supported:
  - central tenant onboarding (`tenant + primary domain + DB provision/migrate/seed`)
  - tenant module request/approve/install/uninstall
  - custom domain add + DNS TXT verify + verified-host enforcement
  - tenant RBAC (roles/features/permissions, policy-driven guards)

For diagrams and deeper architecture notes, see:
- `docs/architecture.md`
- `docs/decisions.md`

## Local setup (Docker)

### 1) Boot services

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
```

### 2) Configure `.env`

At minimum, verify:

```dotenv
APP_URL=http://app.localhost
TENANCY_CENTRAL_DOMAIN=app.localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=central
DB_USERNAME=root
DB_PASSWORD=root

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

DOMAIN_CHECK_TOKEN=replace-with-long-random-token
TENANT_DEFAULT_ADMIN_PASSWORD=ChangeMe123!
```

### 3) Install dependencies + migrate/seed central

```bash
docker compose exec app php artisan migrate --seed
docker compose exec app npm ci
docker compose exec app npm run build
```

### 4) Access

- Central app: `http://app.localhost`
- Tenant example (after onboarding): `http://t001.app.localhost`
- phpMyAdmin: `http://localhost:9000`

## Production ingress (Cloudflare)

- Keep `caddy` for local only (`docker-compose.yml`).
- Use `docker-compose.prod.yml` for production (Cloudflare -> nginx origin).
- Production nginx terminates TLS on `443` using origin cert files:
  - `docker/nginx/ssl/origin.crt`
  - `docker/nginx/ssl/origin.key`
- Create these as Cloudflare Origin Certificate files for your zone.
- If missing/invalid, Cloudflare can show `525 SSL handshake failed`.

## Tenancy workflow

1. Create tenant from central UI (`/tenants/create`).
2. System creates tenant + domain in central DB.
3. Tenancy event pipeline provisions tenant DB:
   - create database
   - run tenant migrations
   - run tenant bootstrap seed
4. Login to tenant domain using seeded admin credentials:
   - email: `admin@{tenant_id}.local`
   - password: `TENANT_DEFAULT_ADMIN_PASSWORD` from `.env`

## Queue requirement (important)

Module install/uninstall is async. Keep worker running:

```bash
docker compose logs -f queue
```

If queue is down, module status can stay at `Installing...`/`Uninstalling...`.

## Testing and CI parity

Run the same checks locally as CI:

```bash
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app php artisan test
```

## Operations / recovery

Runbook and scripts:

- `docs/operations.md`
- `scripts/ops/backup-central.sh`
- `scripts/ops/backup-tenant.sh`
- `scripts/ops/restore-tenant.sh`
- `scripts/ops/list-tenant-dbs.sh`

These support single-tenant recovery without impacting other tenants.

## Current milestone status

- Step 11-17 completed (onboarding, tenant bootstrap seed, RBAC, custom domain lifecycle, module hardening, E2E tests, operations baseline).
- Next focus: Step 18 release cut (final packaging/tag and handoff polish).
