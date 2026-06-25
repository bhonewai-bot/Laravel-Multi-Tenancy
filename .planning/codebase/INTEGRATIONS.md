# External Integrations

**Analysis Date:** 2026-06-25

## APIs & External Services

**Cloudflare Custom Hostnames (SSL for SaaS):**
- Used for provisioning SSL certificates on tenant custom domains
- API: Cloudflare Custom Hostnames API v4 (`https://api.cloudflare.com/client/v4`)
- Client: `app/Services/CloudflareService.php` - HTTP client wrapping Cloudflare API calls
- Sync orchestrator: `app/Services/DomainCloudflareSyncService.php` - Manages create/refresh/poll lifecycle
- Background polling: `app/Jobs/SyncPendingCloudflareDomain.php` - Re-polls every 2 minutes, max 15 attempts (30 min)
- Config: `config/cloudflare.php`
- Auth: `CLOUDFLARE_API_TOKEN` env var (Bearer token)
- Zone: `CLOUDFLARE_ZONE_ID` env var
- Zone domain: `CLOUDFLARE_ZONE_DOMAIN` env var
- Fallback origin: `CLOUDFLARE_FALLBACK_ORIGIN` env var
- Enable toggle: `CLOUDFLARE_ENABLED` env var (default: `false`)
- Async mode: `CLOUDFLARE_ASYNC_POLLING` env var (set `true` in production)
- Validation method: `CLOUDFLARE_VALIDATION_METHOD` env var (default: `http`)
- Timeout/retry: `CLOUDFLARE_TIMEOUT` (15s), `CLOUDFLARE_RETRY_TIMES` (2), `CLOUDFLARE_RETRY_SLEEP_MS` (200ms)
- Console command: `domains:sync-cloudflare` in `routes/console.php`
- Challenge endpoint: `/.well-known/cf-custom-hostname-challenge/{hostnameId}` in `bootstrap/app.php`

**Cloudflare DNS Verification (legacy):**
- TXT record verification for domain ownership
- Service: `app/Services/TenantDomainService.php` method `checkDnsTxtVerification()`
- Record pattern: `_tenant-verification.{domain}` with TXT record containing verification code

**ScrapingBee (Web Scraping):**
- Used for scraping product data from Shopee URLs (which require JavaScript rendering)
- Client: `Modules/Product/app/Services/Imports/ScrapingBeeClient.php`
- API base URL: `SCRAPINGBEE_BASE_URL` env var (default: `https://api.scrapingbee.com/api/v1`)
- Auth: `SCRAPINGBEE_API_KEY` env var
- Config: `config/services.php` under `scrapingbee` key
- Parameters used: `render_js=true`, `stealth_proxy=true`, `country_code=th`, `wait_browser=networkidle2`, `wait=4000`
- Timeout: 180 seconds
- NOTE: The `ScrapingBeeClient::fetchHtml()` method contains a `dd()` debug statement that must be removed before production use

**Lazada Product Scraping:**
- Direct HTTP scraping of Lazada product pages (no proxy needed)
- Importer: `Modules/Product/app/Services/Imports/Importers/LazadaProductImporter.php`
- Method: Fetches page via HTTP, parses JSON-LD structured data with Symfony DomCrawler
- Extracts: name, SKU, price, quantity, description, image
- Downloads product images to `public/products/` via local filesystem storage

**Shopee Product Scraping:**
- Proxied scraping via ScrapingBee (Shopee requires JS rendering)
- Importer: `Modules/Product/app/Services/Imports/Importers/ShopeeProductImporter.php`
- Method: Fetches rendered HTML via ScrapingBee, parses Open Graph meta tags with Symfony DomCrawler
- Extracts: name, SKU (parsed from URL pattern `i.{id}.{id}`), price (regex on Thai baht pattern), description, image

**Slack (Logging/Alerts):**
- Configured for operational log shipping and alerts
- Config: `config/logging.php` channels `slack` and `ops_alert`
- Auth: `LOG_SLACK_WEBHOOK_URL` and `OPS_ALERT_SLACK_WEBHOOK_URL` env vars
- Also configured in `config/services.php` under `slack.notifications` for notification channels
- Auth: `SLACK_BOT_USER_OAUTH_TOKEN`, `SLACK_BOT_USER_DEFAULT_CHANNEL` env vars

**Laravel Cloud (Deployment):**
- Deployment platform for production
- CLI: `laravel/cloud-cli` (`cloud` command)
- Deployment skill: `.claude/skills/deploying-laravel-cloud/SKILL.md`
- Resources: application, environment, instance, database-cluster, database, cache, bucket, domain, background-process, command, deployment
- Config: `.cloud/config.json` (repo-local), `~/.config/cloud/config.json` (global auth)

## Data Storage

**Databases:**
- MySQL 8.0 (primary)
  - Central connection: `env('DB_CONNECTION')` (default `sqlite` in dev, `mysql` in production)
  - Tenant connections: dynamically created as `tenant{tenant_id}` databases
  - Tenant DB prefix: `tenant` (configurable in `config/tenancy.php`)
  - Config: `config/database.php` defines connections for sqlite, mysql, mariadb, pgsql, sqlsrv
  - Docker: MySQL 8.0 exposed on port 3307

- SQLite (development default)
  - Default connection for local dev and testing
  - In-memory for PHPUnit tests (`DB_DATABASE=:memory:`)

**File Storage:**
- Local filesystem (`storage/app/private/`) - default disk
- Public disk (`storage/app/public/`) - served via `public/storage` symlink
  - Product module stores imported product images here under `products/` directory
- S3 - configured but not actively used (commented out in tenancy filesystem config)
- Tenant-scoped: `local` and `public` disks are suffixed with tenant ID via `FilesystemTenancyBootstrapper`

**Caching:**
- Default driver: `database` (configurable via `CACHE_STORE` env)
- Redis configured for both default and cache connections
- Tenant-scoped via `CacheTenancyBootstrapper` (tag-based isolation)
- Module install operations use cache locks: `tenant:module-operation:{tenantId}:{slug}`

**Queue:**
- Default driver: `database` (configurable via `QUEUE_CONNECTION` env)
- Redis queue also configured
- Tenant-aware via `QueueTenancyBootstrapper`
- Failed jobs: `database-uuids` driver
- Docker queue worker: runs `php artisan queue:work database` with configurable tries, backoff, timeout, max-time

**Session:**
- Default driver: `database` (configurable via `SESSION_DRIVER` env)
- Session table: standard Laravel `sessions` table

## Authentication & Identity

**Auth Provider:**
- Custom session-based authentication via Laravel Breeze scaffolding
- Guard: `web` (session driver, Eloquent user provider)
- User model: `App\Models\User` (in tenant databases)
- Central admin: `App\Services\CentralAdminService` auto-creates superadmin on boot
  - Config: `config/auth.php` under `central_admin` key
  - Env: `CENTRAL_SUPERADMIN_EMAIL`, `CENTRAL_SUPERADMIN_NAME`, `CENTRAL_SUPERADMIN_PASSWORD`

**Authorization:**
- Role-based access control (RBAC) within tenants
  - Models: `app/Models/Role.php`, `app/Models/Permission.php`
  - Tenant seeder: `TenantRbacSeeder`
- Policies: `app/Policies/` - `UserPolicy`, `RolePolicy`, `ModuleRequestPolicy`
- Middleware: `EnsureTenantRole` (alias: `role`), `EnsureTenantPermission` (alias: `permission`)
- Route-level permission strings: `user.read`, `domain.read`, `domain.create`, `domain.verify`, `domain.delete`

**Authentication Scaffolding:**
- Built on Laravel Breeze (`laravel/breeze ^2.3`)
- Auth views: `resources/views/auth/` (login, confirm-password, verify-email)
- Controllers: `app/Http/Controllers/Auth/`
- Profile management: `app/Http/Controllers/ProfileController.php`

## Monitoring & Observability

**Error Tracking:**
- Laravel Telescope (development diagnostics)
  - Provider: `app/Providers/TelescopeServiceProvider.php`
  - Config: `config/telescope.php` (disabled by default via `TELESCOPE_ENABLED=false`)
  - Access gating: `TELESCOPE_ALLOWED_EMAILS` env var (comma-separated emails)
  - Filters: Only logs reportable exceptions, failed requests, failed jobs, scheduled tasks, and monitored tags in non-local environments
  - Sensitive data: CSRF tokens and cookies hidden from request data in non-local environments

**Logs:**
- Stack channel (default) with daily rotation (14 days retention)
- Custom tenant context processor: `app/Logging/AddTenantContext.php` (adds tenant_id to all log entries)
- Operational alert channel: `ops_alert` - Slack webhook for error-level alerts
- Slack channel: `slack` - Critical-level log shipping to Slack
- Papertrail channel: configured for syslog-style remote logging
- Pail: `laravel/pail ^1.2.2` for real-time log streaming during development

## CI/CD & Deployment

**Hosting:**
- Docker Compose stack: PHP-FPM + Nginx + Caddy 2.8 + MySQL 8.0
- Caddy: Reverse proxy with on-demand TLS for dynamic tenant domains
- Nginx: Internal application server behind Caddy
- Laravel Cloud: Production deployment target

**CI Pipeline:**
- GitHub Actions: `.github/workflows/ci.yml`
- Triggers: push to `main`, PRs targeting `main`
- Steps: PHP 8.3 setup, Node.js 20 setup, composer validate, install deps, npm ci, npm build, key:generate, run tests
- Test runner: `php artisan test` (PHPUnit)
- Database: SQLite in-memory (no MySQL needed in CI)

**Container:**
- `Dockerfile`: Multi-stage build (base -> builder -> production)
- Base: `php:8.3-fpm` with extensions (pdo_mysql, bcmath, intl, zip, gd)
- Builder: Installs composer + npm dependencies, builds frontend
- Production: Copies built artifacts, removes dev files (node_modules, .git, .github, tests)

**Artisan Console Commands:**
- `domains:sync-cloudflare {domain}` - Manual Cloudflare sync for a domain (defined in `routes/console.php`)
- Standard Laravel commands: `tenants:migrate`, `tenants:seed` (configured in `config/tenancy.php`)

## Environment Configuration

**Required env vars (critical):**
- `APP_KEY` - Laravel encryption key
- `DB_CONNECTION` - Database driver (sqlite/mysql)
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` - Database credentials
- `TENANCY_CENTRAL_DOMAIN` - Central application domain
- `CENTRAL_SUPERADMIN_EMAIL` / `CENTRAL_SUPERADMIN_PASSWORD` - Superadmin credentials
- `CLOUDFLARE_API_TOKEN` / `CLOUDFLARE_ZONE_ID` - Required when `CLOUDFLARE_ENABLED=true`
- `DOMAIN_CHECK_TOKEN` - Token for Caddy on-demand TLS domain verification

**Optional env vars:**
- `CLOUDFLARE_ENABLED` - Toggle Cloudflare integration (default: `false`)
- `CLOUDFLARE_ASYNC_POLLING` - Async domain verification polling (default: `false`)
- `SCRAPINGBEE_API_KEY` - For Shopee product scraping
- `TELESCOPE_ENABLED` - Toggle Telescope (default: `false`)
- `TENANCY_PROVISIONING_QUEUE` - Queue tenant provisioning jobs (default: sync)
- `LOG_STACK` / `OPS_ALERT_SLACK_WEBHOOK_URL` / `LOG_SLACK_WEBHOOK_URL` - Logging configuration
- `TELESCOPE_ALLOWED_EMAILS` - Comma-separated emails for Telescope access

**Secrets location:**
- `.env` file (local/production, gitignored)
- Laravel Cloud environment variables (production)

## Webhooks & Callbacks

**Incoming:**
- `/.well-known/cf-custom-hostname-challenge/{hostnameId}` - Cloudflare custom hostname ownership validation
- `/internal/domain-check` - Caddy on-demand TLS domain verification (throttled: 120/min)

**Outgoing:**
- Cloudflare Custom Hostnames API - Creates and polls hostname/SSL status
- ScrapingBee API - Proxied web scraping requests
- Slack webhooks - Log alerts and operational notifications
- Lazada/Shopee direct HTTP - Product page scraping

## Product Import Pipeline

**Architecture:**
- Strategy pattern: `IProductImporter` interface at `Modules/Product/app/Services/Imports/Interfaces/IProductImporter.php`
- Resolver: `Modules\Product/app/Services/Imports/ProductImporterResolver.php` - Routes URLs to correct importer
- Service: `Modules/Product/app/Services/Imports/ProductImportService.php` - Orchestrates import and product creation
- DTO: `Modules/Product/app/Services/Imports/DTOs/ProductDto.php` - Transfer object between importers and service

**Supported Sources:**
| Source | Importer | Scraping Method | File |
|--------|----------|-----------------|------|
| Lazada | `LazadaProductImporter` | Direct HTTP + JSON-LD parsing | `Modules/Product/app/Services/Imports/Importers/LazadaProductImporter.php` |
| Shopee | `ShopeeProductImporter` | ScrapingBee proxy + Open Graph parsing | `Modules/Product/app/Services/Imports/Importers/ShopeeProductImporter.php` |

**Job Dispatch:**
- `Modules/Product/app/Jobs/ImportProductFromUrl.php` - Queued job for background product import

---

*Integration audit: 2026-06-25*
