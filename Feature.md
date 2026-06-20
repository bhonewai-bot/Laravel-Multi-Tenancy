# Features

## Multi-Tenancy

- **Database-per-tenant** — Each tenant gets its own MySQL database. The central database keeps shared records like tenants, domains, modules, and jobs.
- **Domain-based routing** — Tenants are identified by the domain used to visit the app (e.g., `t001.app.localhost` or a custom domain). Caddy handles HTTPS automatically.
- **Platform subdomains** — Each tenant gets a built-in subdomain like `t001.app.localhost`. These are trusted and don't need extra verification.
- **Custom domains** — Tenants can add their own domain names. The app supports two verification methods: DNS TXT records or Cloudflare Custom Hostnames.
- **Cloudflare SSL integration** — Custom domains are automatically issued SSL certificates through Cloudflare. The app creates custom hostnames via Cloudflare's API and polls until activation is complete.
- **Domain security** — Unverified or unknown domains are blocked. Central domains can't access tenant routes, and only verified tenants can serve traffic.

## User Authentication

- **Registration and login** — Users can register, log in, and log out.
- **Password management** — Users can reset forgotten passwords and update their current password.
- **Email verification** — Users must verify their email address before full access. Verification emails can be resent.
- **Central super admin** — A super admin account is auto-created from configuration when the app boots. This account manages all tenants and modules.
- **Post-login routing** — Users are sent to the right dashboard automatically: the tenant dashboard if visiting a tenant domain, or the central admin panel if visiting the central domain.

## Roles and Permissions (RBAC)

- **Roles** — Each tenant has roles like Admin and Staff, created automatically when the tenant is set up.
- **Features and permissions** — Permissions are grouped under features (e.g., User, Role, Module, Domain). Permissions use dot notation like `user.read` or `domain.create`.
- **Role-permission assignment** — Admins get all permissions. Staff get read-only access.
- **Permission middleware** — Routes can be protected by permission using middleware. Only users with the right permission can access those routes.
- **Tenant admin auto-seed** — Every new tenant gets an admin user with a default password set in the environment config.

## Module System

- **Module marketplace** — A central catalog of modules that tenants can browse and request to install.
- **Module upload** — Admins can upload new modules as ZIP files. Each module requires a `module.json` file and migrations. The app validates the structure before accepting the upload.
- **Module request workflow** — Tenants request modules they want. Central admins can approve or reject each request.
- **Async install and uninstall** — Module installs and uninstalls run in the background via queue jobs. This prevents long-running operations from blocking the user.
- **Operation tracking** — The tenant record tracks the status of each module operation (queued, running, success, or failed).
- **Module access guard** — Middleware blocks routes belonging to modules the tenant hasn't installed.

## Product Module

- **Full CRUD** — Create, read, update, and delete products with name, SKU, price, quantity, description, and image.
- **Dashboard** — Product index page shows summary stats and a searchable, sortable product table.
- **Image upload** — Each product can have an image uploaded and stored on the server.
- **Product import from URLs** — Paste a Shopee or Lazada product URL and the app scrapes the product details automatically.
- **Smart scraping** — Shopee imports use ScrapingBee to render JavaScript-heavy pages. Lazada imports read structured JSON-LD data directly.
- **Auto-detect marketplace** — The system detects whether a URL is from Lazada or Shopee and picks the right importer automatically.
- **Queued import** — Product imports run in the background so they don't block the user interface.

## Queue and Jobs

- **Database queue** — Both central and tenant databases have their own job tables for background processing.
- **Tenant-scoped jobs** — Jobs for module install/uninstall and product import run inside the correct tenant context.
- **Job retries** — Failed jobs retry automatically with increasing delays to handle temporary issues.
- **Cloudflare polling job** — When a custom domain is added, a delayed polling job checks back with Cloudflare until the SSL certificate is ready (up to 15 attempts, 2 minutes apart).

## API

- **Sanctum authentication** — API routes are protected by Laravel Sanctum tokens.
- **Product API** — Full REST API for products under `api/v1/products` with all CRUD operations.

## Monitoring and Debugging

- **Laravel Telescope** — Built-in debugging and monitoring tool for inspecting requests, queries, jobs, and more.
- **Runtime table repair** — A special migration recreates essential system tables (cache, sessions, jobs) if they ever go missing.

## Infrastructure

- **Docker Compose** — Local development with app, nginx, MySQL, phpMyAdmin, and a queue worker.
- **Production setup** — Production Docker Compose file with nginx using Cloudflare origin SSL certificates.
- **Caddy reverse proxy** — Automatic HTTPS for all domains, including on-demand TLS for custom domains. Falls back to a Cloudflare origin for production traffic.
- **phpMyAdmin** — Database management UI available at port 9000 for local development.
