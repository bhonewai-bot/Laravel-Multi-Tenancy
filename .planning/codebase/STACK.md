# Technology Stack

**Analysis Date:** 2026-06-25

## Languages

**Primary:**
- PHP 8.3 - Backend logic, controllers, models, services, jobs
- JavaScript (ES Modules) - Frontend interactions, Vite build

**Secondary:**
- Blade Templates - View rendering and component composition
- CSS (Tailwind) - Styling and responsive design

## Runtime

**Environment:**
- Laravel Framework v12.0 - Core PHP framework
- PHP 8.3+ with modern features (enums, typed properties, etc.)

**Package Manager:**
- Composer (PHP) - `composer.json` present, `composer.lock` committed
- npm (JavaScript) - `package.json` present, `package-lock.json` committed

## Frameworks

**Core:**
- Laravel v12.0 - PHP web framework (full-stack MVC)
- Livewire v4.2 - Reactive PHP frontend components without JavaScript framework
- Alpine.js v3.4.2 - Lightweight client-side interactivity

**Testing:**
- PHPUnit v11.5.3 - PHP unit and feature testing
- Faker v1.23 - Test data generation

**Build/Dev:**
- Vite v7.0.7 - Frontend asset bundler and hot module replacement
- Laravel Breeze v2.3 - Authentication scaffolding
- Laravel Pint v1.24 - Code formatting (PSR-12 based)

**Development Tools:**
- Laravel Telescope v5.18 - Application debugging and metrics dashboard
- Laravel Pail v1.2.2 - Real-time log viewer
- Laravel Sail v1.41 - Docker development environment

## Key Dependencies

**Critical:**
- stancl/tenancy v3.9 - Multi-tenancy architecture with database isolation per tenant
- nwidart/laravel-modules v12.0 - Modular application structure (plugins/modules system)
- laravel/mcp v0.8.1 - Model Context Protocol server integration

**Infrastructure:**
- blade-ui-kit/blade-heroicons v2.7 - Icon library for Blade components
- symfony/css-selector v7.4 - CSS selector parsing for testing
- symfony/dom-crawler v7.4 - DOM traversal for HTML manipulation

**Optional/Pluggable:**
- laravel/boost v2.4 (dev) - Enhanced development experience tools
- mockery/mockery v1.6 (dev) - Mock objects for testing

## Configuration

**Environment:**
- Multiple `.env` files: `.env` (current), `.env.example`, `.env.production.example`
- Key variables: Database connections, Cloudflare API, authentication secrets
- Central admin credentials: `CENTRAL_SUPERADMIN_EMAIL`, `CENTRAL_SUPERADMIN_PASSWORD`

**Build:**
- `vite.config.js` - Frontend build configuration with Laravel plugin
- `tailwind.config.js` - Custom brand colors, dark mode, and typography
- `postcss.config.js` - CSS processing pipeline
- `phpunit.xml` - Testing configuration

**Tenancy:**
- `config/tenancy.php` - Stancl tenancy bootstrappers and database isolation
- Separate tenant databases: `database/tenant*` files (SQLite per tenant)
- Multi-database manager strategy (SQLite, MySQL, PostgreSQL supported)

**Queue:**
- Default queue: Database-driven (`QUEUE_CONNECTION=database`)
- Supports: sync, database, redis, sqs, beanstalkd
- Job batching and tenant-aware queue processing

## Platform Requirements

**Development:**
- PHP 8.3+ with extensions: pdo_sqlite, pdo_mysql, pdo_pgsql
- Node.js for npm/frontend builds
- Composer for PHP dependencies
- Docker (optional, via Laravel Sail)

**Production:**
- Deployment target: Laravel Cloud or Docker container
- Database: SQLite (current), MySQL, or PostgreSQL
- Web server: Caddy (for multi-tenant domain routing) or nginx
- Queue worker: Required for background jobs (domain sync, module install)
- PHP extensions: Same as development

**External Services (Production):**
- Cloudflare: Custom hostnames API for tenant domain management
- Database hosting: Managed MySQL/PostgreSQL or SQLite file storage

---

*Stack analysis: 2026-06-25*
