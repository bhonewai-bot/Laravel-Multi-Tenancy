# Stack Research — INFRA Hardening

**Domain:** Docker infrastructure hardening for Laravel multi-tenancy platform
**Researched:** 2026-06-27
**Confidence:** HIGH

## Recommended Stack

### Docker Security

| Technology / Config | Version / Scope | Purpose | Why Recommended |
|---------------------|-----------------|---------|-----------------|
| `.dockerignore` | New file | Reduce build context, exclude secrets | Prevents `.env`, `vendor/`, `node_modules/`, `.git/` from entering image layers; cuts context size by 80%+ |
| Non-root USER | Dockerfile `AS production` | Run PHP-FPM as `www-data` | Containers running as root can escalate to host root; `www-data` is PHP-FPM's default worker uid |
| `docker/prod/entrypoint.sh` | Already exists | Wire into Dockerfile | Already handles storage permissions, SQLite writes, and `gosu` drop to non-root; currently NOT wired into `CMD` |
| `cap_drop: ALL` | docker-compose | Remove Linux capabilities | Containers don't need `SYS_ADMIN`, `NET_RAW`, etc. Add back only `NET_BIND_SERVICE` if binding <1024 |
| `security_opt: no-new-privileges` | docker-compose | Block privilege escalation | Prevents setuid binaries and capability escalation inside container |
| `read_only: true` + tmpfs | docker-compose | Immutable filesystem | App code should not change at runtime; writable dirs mounted as tmpfs |
| `deploy.resources.limits` | docker-compose | Memory and CPU caps | Prevents a runaway process from OOM-killing the host; critical for queue workers |
| Rename `DockerFile` -> `Dockerfile` | compose files | Fix case mismatch | `dockerfile: DockerFile` references are wrong (actual file is `Dockerfile`). Works on macOS (case-insensitive) but FAILS on Linux CI runners |

**Specific resource limits (recommended starting points):**

| Service | Memory Limit | CPU Limit | Rationale |
|---------|-------------|-----------|-----------|
| `app` (PHP-FPM) | 512M | 1.0 | Livewire requests can spike; leaves headroom |
| `nginx` | 128M | 0.5 | Static file serving + reverse proxy, lightweight |
| `queue` (worker) | 512M | 1.0 | Module install jobs run migrations in tenant DBs; can spike |
| `scheduler` | 256M | 0.5 | `schedule:run` is lightweight; one process per minute |
| `mysql` | 1G | 1.0 | Multi-tenant with many databases; needs buffer pool |

### Nginx Hardening

| Config | Scope | Purpose | Why Recommended |
|--------|-------|---------|-----------------|
| Security headers | Both `app.conf` files | CSP, HSTS, X-Content-Type-Options, Referrer-Policy, Permissions-Policy | OWASP top-10 mitigations; `X-XSS-Protection` is deprecated (remove it), CSP `frame-ancestors` replaces `X-Frame-Options` |
| Gzip compression | Both `app.conf` files | Compress text responses | 60-80% reduction for HTML/CSS/JS; negligible CPU cost for web servers |
| Static asset caching | Both `app.conf` files | `Cache-Control` for `/build/`, fonts, images | Vite outputs hashed filenames in `public/build/` -- safe to cache aggressively (1 year) |
| `server_tokens off` | Both `app.conf` files | Hide nginx version | Information disclosure reduction |

**Security headers to add (production-ready, Livewire/Alpine compatible):**

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=(), payment=()" always;
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self' ws: wss:; frame-ancestors 'self';" always;
```

**CSP notes for this project:**
- `unsafe-inline` required for Livewire's inline scripts and Alpine.js bootstrapping
- `unsafe-eval` required for Livewire's morphing/DOM diffing engine
- `connect-src ws: wss:` required for Livewire's WebSocket support (if used)
- `frame-ancestors 'self'` blocks clickjacking (replaces X-Frame-Options long-term)
- HSTS should NOT include `preload` until domain strategy is finalized (multi-tenant with custom domains)

### OPcache Configuration

| Setting | Value | Why |
|---------|-------|-----|
| `opcache.enable` | `1` | Must be ON for production |
| `opcache.memory_consumption` | `128` | 128MB covers Laravel 12 + modules; tune up if more modules added |
| `opcache.interned_strings_buffer` | `16` | Standard for Laravel apps; reduces memory for repeated strings |
| `opcache.max_accelerated_files` | `10000` | Laravel 12 + stancl/tenancy + nwidart ~7000 classes; 10000 gives headroom |
| `opcache.validate_timestamps` | `0` | Files never change in a built container; disable for max performance |
| `opcache.revalidate_freq` | `0` | Irrelevant when validate_timestamps=0 |
| `opcache.save_comments` | `1` | Required -- Laravel, Stancl, and Livewire use annotation/docblock-based features |
| `opcache.jit` | `1255` | PHP 8.3 tracing JIT -- aggressive optimization; 1255 = tracing + register allocation |
| `opcache.jit_buffer_size` | `64M` | Buffer for JIT-compiled code; 64MB is the standard starting point |

**Integration approach:** Copy `php.ini-production` as base, overlay OPcache settings via `docker/php/conf.d/opcache.ini`. This keeps Dockerfile clean and makes OPcache toggleable.

**JIT note:** `opcache.jit=1255` is the most aggressive tracing JIT mode. If any instability occurs, fall back to `1254` (function-level + register allocation) or `1235` (function-level only).

### Scheduler Service

| Approach | Implementation | Why Recommended |
|----------|---------------|-----------------|
| `php artisan schedule:work` | Separate container in compose | Laravel 11+ native loop command; no shell `while/sleep` hack needed; handles locking, logging, and graceful shutdown natively |

**Why NOT cron:** Docker containers should run a single primary process. Installing `cronie` in the PHP-FPM container couples two concerns. A dedicated `scheduler` service is the Docker-native approach.

**Why NOT `schedule:run` in a while loop:** `schedule:work` was introduced in Laravel 11 specifically to replace this pattern. It handles the 60-second loop, respects schedule locking, and logs properly.

### Queue Worker Health Check

| Check | Method | Why |
|-------|--------|-----|
| PHP-FPM ping | `fastcgi_pass` to `/ping` endpoint | Standard FPM health check; lightweight; doesn't require curl in the container |
| Queue worker | `php artisan queue:healthcheck` or process check | Laravel 12 has no built-in queue healthcheck endpoint; use process-level check via entrypoint |

**Recommended approach:** Add `pm.status_path = /status` and `ping.path = /ping` to PHP-FPM's `www.conf`. Nginx proxies to it. For the queue service, use a `HEALTHCHECK` that verifies the `php artisan queue:work` process is running.

### CI Pipeline

| Component | Tool | Purpose | Why Recommended |
|-----------|------|---------|-----------------|
| Docker build validation | `docker build` in GitHub Actions | Proves Dockerfile builds cleanly | Catches Dockerfile syntax errors, missing deps, build failures before merge |
| Code style | `vendor/bin/pint --test --format=github` | Enforce Pint formatting | `--test` fails (doesn't auto-fix) in CI; `--format=github` adds inline PR annotations |
| Dependency audit | `composer audit` | Check for known CVEs | Composer 2.4+ built-in; catches vulnerable packages before deploy |
| Frontend build | `npm run build` | Validate Vite compilation | Catches broken JS/CSS before merge |
| Existing tests | `php artisan test --compact` | PHPUnit suite | Already in ci.yml; keep as-is |

**Recommended additions to existing `.github/workflows/ci.yml`:**

```yaml
# Add after "Build frontend assets" step:

- name: Check code style (Pint)
  run: vendor/bin/pint --test --format=github

- name: Audit dependencies
  run: composer audit

- name: Build Docker image
  run: docker build -t tenantsmith-test .
```

**What NOT to add to CI:**
- Docker Compose integration tests (too complex for this stage; adds 5+ minutes to CI)
- Trivy/Snyk image scanning (overkill for a solo dev project at this stage)
- Multi-platform builds (only targeting amd64 for now)

## Installation

```bash
# No new Composer or npm packages needed.
# All changes are configuration files:
# - .dockerignore (new)
# - Dockerfile (modify: add OPcache ext, non-root user, wire entrypoint, healthcheck)
# - docker-compose.yml (modify: security hardening, resource limits, scheduler)
# - docker-compose.prod.yml (modify: same as above)
# - docker/nginx/conf.d/app.conf (modify: headers, gzip, caching)
# - docker/nginx/conf.d/prod/app.conf (modify: headers, gzip, caching)
# - docker/php/conf.d/opcache.ini (new)
# - .github/workflows/ci.yml (modify: add pint, audit, docker build)
```

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| `php artisan schedule:work` | `supervisord` managing cron + queue + fpm | If you need to collapse scheduler + queue into a single container (not recommended for Docker Compose) |
| `opcache.jit=1255` | `opcache.jit=1235` (function-only) | If tracing JIT causes segfaults or instability on your specific PHP build |
| Security headers in nginx | Security headers in Laravel middleware | Nginx handles it at the edge before PHP even boots; middleware approach only works per-route and misses static assets/404 pages |
| `composer audit` in CI | `roave/security-advisories` Composer plugin | `roave/security-advisories` prevents installation entirely (too aggressive for dev); `composer audit` only checks at CI time |
| GitHub Actions CI | GitLab CI, Jenkins | Already using GitHub; Actions is zero-infra |

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| `X-XSS-Protection: 1; mode=block` | Deprecated; can introduce vulnerabilities in older browsers that misinterpret the header | CSP `script-src` directive (already included) |
| `X-Frame-Options: DENY` | Being replaced by CSP `frame-ancestors`; `DENY` blocks iframes you might need | CSP `frame-ancestors 'self'` (already included) |
| Alpine-based PHP-FPM image (`php:8.3-fpm-alpine`) | Different musl libc breaks `pdo_mysql`, `gd`, `intl` extensions; the current Debian-based image works correctly | Stick with `php:8.3-fpm` (Debian) |
| `cron` / `crond` in container | Couples scheduler to FPM container; Docker anti-pattern (one process per container) | Dedicated `scheduler` service using `schedule:work` |
| Supervisord | Overkill for 2 processes (FPM + queue); Docker Compose already orchestrates multiple containers | Separate services in compose |
| `nginx:latest` tag | Unpinned tags break reproducibility | `nginx:1.27-alpine` or current `nginx:alpine` (which is pinned by digest on Docker Hub) |
| PHP-FPM `ping` via HTTP through nginx | Requires `cgi-fcgi` binary or curl inside FPM container | Use `php-fpm-healthcheck` script or direct process check |

## Version Compatibility

| Component | Current Version | Compatible With | Notes |
|-----------|-----------------|-----------------|-------|
| `php:8.3-fpm` | 8.3.x | OPcache JIT 1255, all extensions | JIT stable since PHP 8.1; 8.3 has latest JIT improvements |
| `nginx:alpine` | 1.27.x | All directives used | `server_tokens`, `gzip`, `add_header` all standard |
| `caddy:2.8-alpine` | 2.8.x | On-demand TLS, Caddyfile | No changes needed to Caddy |
| `mysql:8.0` | 8.0.x | InnoDB, utf8mb4 | No changes needed |
| Composer 2 | 2.8.x | `composer audit` (since 2.4) | Already available |
| Laravel Pint | 1.24.x | `--test`, `--format=github` | Already in dev dependencies |
| GitHub Actions `actions/checkout` | v4 | `docker build` | Standard runner has Docker pre-installed |
| `shivammathur/setup-php` | v2 | PHP 8.3, extensions | Already in ci.yml |

## Sources

- Docker official docs -- `.dockerignore`, `HEALTHCHECK`, resource constraints, multi-stage builds
- OWASP -- Security headers cheat sheet, Docker security cheat sheet
- PHP manual -- OPcache configuration directives, JIT compilation
- Nginx official docs -- `gzip` module, `add_header`, `server_tokens`
- Laravel 12 docs -- `schedule:work` command, deployment recommendations
- MDN Web Docs -- Content-Security-Policy, Strict-Transport-Security, Permissions-Policy
- GitHub Actions docs -- Docker build workflow, `actions/checkout@v4`

---
*Stack research for: TenantSmith INFRA Hardening*
*Researched: 2026-06-27*
