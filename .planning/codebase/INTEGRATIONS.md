# External Integrations

**Analysis Date:** 2026-06-25

## APIs & External Services

**Cloudflare Custom Hostnames API:**
- Purpose: Manage tenant custom domains (SSL provisioning, DNS validation)
- Service: `App\Services\CloudflareService`
- Config: `config/cloudflare.php`
- Authentication: API Token via `CLOUDFLARE_API_TOKEN` env var
- Rate Limits: Configurable timeout/retry (default 15s timeout, 2 retries)
- Endpoints Used:
  - POST `/custom_hostnames` - Create hostname for tenant domain
  - GET `/custom_hostnames/{cloudflareId}` - Check hostname status
- Error Handling: Throws `RuntimeException` on API failures with response parsing
- Retry Strategy: Exponential backoff with configurable sleep (200ms default)

**ScrapingBee (Pluggable):**
- Purpose: Web scraping service (available but not actively used in core flows)
- Config: `config/services.php`
- Authentication: API Key via `SCRAPINGBEE_API_KEY` env var
- Base URL: `https://api.scrapingbee.com/api/v1`
- Usage: Optional for external data fetching (available via config)

**AWS SES (Pluggable):**
- Purpose: Email delivery service
- Config: `config/services.php`
- Authentication: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
- Region: Configurable via `AWS_DEFAULT_REGION` (default: us-east-1)
- Status: Not actively used (mail defaults to 'log' driver)

**AWS SQS (Pluggable):**
- Purpose: Message queue service for high-scale production
- Config: `config/queue.php`
- Authentication: Same as SES
- Status: Available but database queue is default

**Postmark/Resend (Pluggable):**
- Purpose: Transactional email services
- Config: `config/services.php`
- Authentication: API keys via `POSTMARK_API_KEY` or `RESEND_API_KEY`
- Status: Not actively configured (alternatives to SES)

## Data Storage

**Databases:**
- SQLite (Primary - Development)
  - Connection: `database.sqlite` (central), `database/tenant*` (per tenant)
  - Client: Laravel Eloquent ORM
  - Tenancy Strategy: Separate SQLite file per tenant database
  - File Management: Automatic creation/deletion via Stancl tenancy bootstrappers

- MySQL/PostgreSQL (Production-Ready)
  - Connection: Via `DB_*` environment variables
  - Client: Laravel Eloquent ORM
  - Tenancy Strategy: Schema-based or separate database per tenant
  - Migration: Database-per-tenant with prefix naming (`tenant{uuid}`)

- Central Database
  - Purpose: Store tenants, modules, central users, domain mappings
  - Connection: `central` connection name
  - Tables: `tenants`, `domains`, `modules`, `module_requests`, `users` (central)

**File Storage:**
- Local filesystem: `storage/app/private` (default), `storage/app/public`
- Cloud: S3 support available but not actively used
- Tenant-aware: Filesystem tenancy bootstrapper isolates per-tenant files
- No active external file storage integration

**Caching:**
- Default: Database-driven cache
- Supports: Array, file, redis, memcached, dynamodb
- Tenant-aware: Cache tenancy bootstrapper tags cache by tenant
- Not actively using external cache service

## Authentication & Identity

**Auth Provider:**
- Service: Custom (Laravel Breeze scaffolding)
- Implementation: Session-based with database user provider
- Guards: `web` guard (session + Eloquent)
- User Model: `App\Models\User` (central and tenant-aware)
- Multi-tenant Users: Users stored per-tenant with tenant isolation

**Central Admin:**
- Purpose: Superadmin account for central dashboard
- Credentials: Via `CENTRAL_SUPERADMIN_EMAIL`, `CENTRAL_SUPERADMIN_PASSWORD`
- Bootstrap: Auto-created via `CentralAdminService::ensureConfiguredSuperAdminExists()`
- Access: Central domain routes only (protected by `PreventAccessFromCentralDomains` middleware)

**Role-Based Access Control:**
- Implementation: Custom middleware (`EnsureTenantRole`, `EnsureTenantPermission`)
- Models: `App\Models\Role`, `App\Models\Permission`
- Granularity: Per-tenant roles with permission checks
- Policies: `RolePolicy`, `UserPolicy`, `ModuleRequestPolicy`

## Monitoring & Observability

**Error Tracking:**
- Service: Laravel Telescope (debug dashboard)
- Config: `config/telescope.php`
- Access: `/telescope` endpoint on central domain
- Storage: Database-backed
- Status: Disabled by default (`TELESCOPE_ENABLED=false`)

**Logs:**
- Channels: Stack with daily rotation (default)
- Tenant Context: Custom processor `AddTenantContext` adds tenant ID to all logs
- Real-time: Laravel Pail for live log streaming during development
- Output: `storage/logs/laravel.log`

**Application Metrics:**
- Service: Laravel Telescope watchers
- Captures: Requests, exceptions, database queries, jobs, mail, cache, dumps
- Performance: Query logging, slow request detection

## CI/CD & Deployment

**Hosting:**
- Primary Target: Laravel Cloud
- Alternative: Docker container (DockerFile present)
- Multi-tenant Routing: Caddy with on-demand TLS for dynamic tenant domains

**Docker:**
- Development: Laravel Sail (Docker-based dev environment)
- Production: `docker-compose.prod.yml` available
- Services: PHP-FPM, Caddy web server, queue worker

**CI Pipeline:**
- Service: Not configured in repository
- Status: No GitHub Actions or CI config detected

**Queue System:**
- Driver: Database-backed (default)
- Worker: `php artisan queue:listen` (included in dev script)
- Jobs: Domain sync, module installation/uninstallation
- Tenant-aware: Queue tenancy bootstrapper isolates tenant jobs

## Environment Configuration

**Required env vars (Critical):**
- `APP_KEY` - Encryption key for sessions and cookies
- `DB_CONNECTION` - Database driver (sqlite, mysql, pgsql)
- `DB_DATABASE`, `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` - Database connection
- `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ZONE_ID` - Cloudflare integration
- `CLOUDFLARE_ZONE_DOMAIN` - Base domain for tenant subdomains
- `TENANCY_CENTRAL_DOMAIN` - Central application domain
- `CENTRAL_SUPERADMIN_EMAIL`, `CENTRAL_SUPERADMIN_PASSWORD` - Admin credentials

**Optional env vars (Pluggable):**
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` - AWS services
- `POSTMARK_API_KEY`, `RESEND_API_KEY` - Email services
- `SLACK_BOT_USER_OAUTH_TOKEN` - Slack notifications
- `SCRAPINGBEE_API_KEY` - Web scraping
- `QUEUE_CONNECTION` - Queue backend selection
- `TELESCOPE_ENABLED` - Debug dashboard toggle

**Secrets Location:**
- Environment variables (`.env` files)
- Laravel Cloud: Managed environment variables
- Docker: Docker secrets or mounted `.env`

## Webhooks & Callbacks

**Incoming:**
- Cloudflare Domain Validation: `/.well-known/cf-custom-hostname-challenge/{hostnameId}`
  - Endpoint: `App\Http\Controllers\CloudflareHostnameChallengeController`
  - Middleware: None (host-agnostic for Cloudflare validation)
  - Rate Limit: Not explicitly set
  - Purpose: Verify domain ownership before Cloudflare SSL provisioning

- Caddy Domain Check: `/internal/domain-check`
  - Endpoint: `App\Http\Controllers\DomainCheckController`
  - Middleware: `throttle:120,1` (120 requests per minute)
  - Purpose: On-demand TLS validation for Caddy
  - Access: Internal only

**Outgoing:**
- Cloudflare API Calls (outbound):
  - Create custom hostname: POST to Cloudflare
  - Check hostname status: GET from Cloudflare
  - Triggered by: Tenant domain creation workflow
  - Jobs: `App\Jobs\SyncPendingCloudflareDomain`

- Queue Jobs (outbound):
  - `App\Jobs\SyncPendingCloudflareDomain` - Async domain sync to Cloudflare
  - `App\Jobs\InstallTenantModule` - Async module installation
  - `App\Jobs\UninstallTenantModule` - Async module uninstallation
  - Triggered by: HTTP request → dispatched → processed by queue worker

## Integration Patterns

**Service Layer:**
- Services encapsulate external API logic: `App\Services\CloudflareService`
- Controllers delegate to services, services handle HTTP calls
- Error handling centralized in service layer with exceptions

**Job Queue:**
- Long-running operations dispatched as jobs (domain sync, module install)
- Tenant context preserved in job serialization
- Async processing prevents HTTP timeout issues

**Configuration:**
- All integrations configurable via environment variables
- Config files in `config/` directory with defaults
- Feature flags via env vars (e.g., `CLOUDFLARE_ENABLED`)

---

*Integration audit: 2026-06-25*
