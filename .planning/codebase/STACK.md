# Technology Stack

**Analysis Date:** 2026-06-25

## Languages

**Primary:**
- PHP 8.3 - Backend application logic, controllers, services, models, middleware, Livewire components, queued jobs
- JavaScript (ES modules) - Frontend interactivity via Alpine.js and Livewire client-side bootstrapping

**Secondary:**
- Blade - Server-side templating for all views (`resources/views/`)
- CSS (Tailwind) - Styling via utility-first CSS framework (`resources/css/app.css`)

## Runtime

**Environment:**
- PHP 8.3 with FPM (production via Docker image `php:8.3-fpm`)
- Node.js 20 (CI) / npm for frontend asset compilation
- Docker Compose stack: PHP-FPM + Nginx + Caddy 2.8 + MySQL 8.0 + phpMyAdmin + queue worker

**Package Manager:**
- Composer 2 - PHP dependency management; lockfile present at `composer.lock`
- npm - JavaScript dependency management; lockfile present at `package-lock.json`

## Frameworks

**Core:**
- Laravel 12 (`laravel/framework ^12.0`) - Application framework; uses the streamlined Laravel 11+ directory structure (no `app/Http/Kernel.php`, no `app/Console/Kernel.php`)
- Livewire 4 (`livewire/livewire ^4.2`) - Reactive server-driven UI components; configured in `config/livewire.php`
- Alpine.js 3 (`alpinejs ^3.4.2`) - Client-side interactivity bundled through Livewire

**Tenancy:**
- Stancl Tenancy v3 (`stancl/tenancy ^3.9`) - Database-per-tenant multi-tenancy with domain-based identification
  - Config: `config/tenancy.php`
  - Provider: `app/Providers/TenancyServiceProvider.php`
  - Models: `app/Models/Tenant.php`, `app/Models/Domain.php`

**Modular Architecture:**
- nwidart/laravel-modules v12 (`nwidart/laravel-modules ^12.0`) - Module system for tenant-installable features
  - Module root: `Modules/` directory
  - Composer merge plugin: `Modules/*/composer.json` merged via `extra.merge-plugin`
  - Example module: `Modules/Product/`

**Testing:**
- PHPUnit 11 (`phpunit/phpunit ^11.5.3`) - Test framework
  - Config: `phpunit.xml`
  - Test suites: `tests/Unit/`, `tests/Feature/`
  - SQLite in-memory for test database (`DB_DATABASE=:memory:`)

**Build/Dev:**
- Vite 7 (`vite ^7.0.7`) - Frontend build tool
  - Config: `vite.config.js`
  - Plugin: `laravel-vite-plugin ^2.0.0`
  - Entry points: `resources/css/app.css`, `resources/js/app.js`
- Laravel Pint (`laravel/pint ^1.24`) - PHP code formatting (run with `vendor/bin/pint --dirty --format agent`)
- Laravel Sail (`laravel/sail ^1.41`) - Docker development environment
- Laravel Pail (`laravel/pail ^1.2.2`) - Real-time log viewer
- concurrently (`concurrently ^9.0.1`) - Parallel process runner for `composer dev` script

## Key Dependencies

**Critical:**
- `stancl/tenancy ^3.9` - Core multi-tenancy engine; database-per-tenant isolation, tenant bootstrappers, domain identification
- `nwidart/laravel-modules ^12.0` - Module packaging and lifecycle; tenant modules are ZIP-packaged and installed per-tenant
- `livewire/livewire ^4.2` - All interactive UI is server-rendered via Livewire components

**Infrastructure:**
- `laravel/mcp ^0.8.1` - Laravel MCP server for AI-assisted development tooling
- `laravel/boost ^2.4` (dev) - MCP tools for database queries, schema inspection, docs search
- `laravel/telescope ^5.18` (dev) - Application diagnostics and debugging; gated by `TELESCOPE_ALLOWED_EMAILS`
- `laravel/breeze ^2.3` (dev) - Authentication scaffolding (used as starter, custom auth built on top)
- `blade-ui-kit/blade-heroicons ^2.7` - Icon components for Blade views

**HTTP & Scraping:**
- `symfony/css-selector ^7.4` + `symfony/dom-crawler ^7.4` - HTML parsing for product import from Lazada/Shopee URLs

## Configuration

**Environment:**
- `.env` - Active environment configuration (gitignored)
- `.env.example` - Reference template
- `.env.production.example` - Production-specific reference
- Key env var groups:
  - **Tenancy:** `TENANCY_CENTRAL_DOMAIN`, `DB_CONNECTION`, `TENANCY_PROVISIONING_QUEUE`
  - **Cloudflare:** `CLOUDFLARE_ENABLED`, `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ZONE_ID`, `CLOUDFLARE_ZONE_DOMAIN`, `CLOUDFLARE_FALLBACK_ORIGIN`, `CLOUDFLARE_ASYNC_POLLING`
  - **Auth:** `CENTRAL_SUPERADMIN_EMAIL`, `CENTRAL_SUPERADMIN_NAME`, `CENTRAL_SUPERADMIN_PASSWORD`
  - **Services:** `SCRAPINGBEE_API_KEY`, `SCRAPINGBEE_BASE_URL`
  - **Logging:** `LOG_STACK`, `OPS_ALERT_SLACK_WEBHOOK_URL`, `LOG_SLACK_WEBHOOK_URL`
  - **Queue:** `QUEUE_CONNECTION`, `DB_QUEUE_CONNECTION`, `QUEUE_WORKER_TRIES`, `QUEUE_WORKER_TIMEOUT`

**Build:**
- `vite.config.js` - Vite build configuration (Laravel plugin, entry points)
- `tailwind.config.js` - Tailwind CSS configuration (content paths, custom brand colors, design tokens, shadows)
- `postcss.config.js` - PostCSS with Tailwind and Autoprefixer plugins
- `phpunit.xml` - PHPUnit test runner configuration
- `.editorconfig` - Editor settings (UTF-8, LF, 4-space indent)

**Config Files (PHP):**
- `config/tenancy.php` - Stancl tenancy configuration (UUID generator, database prefix, bootstrappers, cache/filesystem tenancy)
- `config/cloudflare.php` - Cloudflare Custom Hostnames API configuration
- `config/database.php` - Database connections (sqlite, mysql, mariadb, pgsql, sqlsrv) and Redis
- `config/queue.php` - Queue connections (database, redis, SQS, sync, deferred, background, failover)
- `config/cache.php` - Cache stores (database, redis, file, array, memcached, dynamodb)
- `config/mail.php` - Mail transports (SMTP, SES, Postmark, Resend, log)
- `config/logging.php` - Log channels (stack, daily, slack, ops_alert, papertrail)
- `config/session.php` - Session driver (database default)
- `config/filesystems.php` - Storage disks (local, public, s3)
- `config/auth.php` - Authentication guards, providers, and central admin configuration
- `config/services.php` - Third-party service credentials (Postmark, Resend, SES, Slack, ScrapingBee)
- `config/livewire.php` - Livewire component locations, namespaces, page layout
- `config/telescope.php` - Telescope diagnostics (disabled by default via env)

## Platform Requirements

**Development:**
- PHP 8.3+ with extensions: pdo_mysql, bcmath, intl, zip, gd (freetype + jpeg)
- Node.js 20+ with npm
- MySQL 8.0 (via Docker Compose)
- Docker and Docker Compose (recommended)
- Composer 2

**Production:**
- Docker-based deployment (PHP-FPM + Nginx + Caddy + MySQL 8.0)
- Laravel Cloud deployment supported via `cloud` CLI (`deploying-laravel-cloud` skill)
- Caddy 2.8 with on-demand TLS for dynamic tenant domain SSL
- MySQL 8.0 primary database
- Database queue driver for background jobs

## Dev Scripts

**`composer dev`** - Runs 4 parallel processes:
1. `php artisan serve` (HTTP server)
2. `php artisan queue:listen` (queue worker)
3. `php artisan pail` (log viewer)
4. `npm run dev` (Vite HMR)

**`composer setup`** - Full project setup: install deps, generate key, migrate, build assets

**`composer test`** - Clears config cache and runs PHPUnit

---

*Stack analysis: 2026-06-25*
