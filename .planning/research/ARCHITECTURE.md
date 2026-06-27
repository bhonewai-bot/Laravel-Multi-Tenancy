# Architecture Patterns — INFRA Hardening

**Domain:** Docker infrastructure hardening for Laravel multi-tenancy platform
**Researched:** 2026-06-27

## Current Architecture (Pre-Hardening)

```
Internet -> Caddy (TLS, port 443) -> Nginx (port 80) -> PHP-FPM (port 9000)
                                                       |
                                                       v
                                                    MySQL (port 3306)

Separate containers: app (PHP-FPM), nginx, caddy, mysql, phpmyadmin, queue
```

**Issues identified:**
- `app` container runs as root, bind-mounts entire project directory
- No OPcache extension installed (every request re-parses PHP)
- No scheduler container (Laravel scheduled tasks never execute)
- Nginx serves responses without security headers, gzip, or cache headers
- CI pipeline only runs tests; no Docker build validation, code style, or dependency audit
- `DockerFile` case mismatch (works on macOS, breaks on Linux CI)

## Recommended Architecture (Post-Hardening)

```
Internet -> Caddy (TLS termination, port 443)
              |
              v
           Nginx (reverse proxy, security headers, gzip, caching, port 80)
              |
              v
         PHP-FPM (app, non-root, OPcache+JIT, port 9000)
              |
              v
           MySQL (port 3306, internal only)

Additional services:
  - scheduler (php artisan schedule:work, shares app image)
  - queue (php artisan queue:work, shares app image, healthcheck)
  - phpmyadmin (dev only, port 9000)

All containers: cap_drop ALL, no-new-privileges, resource limits
Production: no bind-mounts, code baked into image
```

### Component Boundaries (Post-Hardening)

| Component | Responsibility | Security Posture | Communicates With |
|-----------|---------------|------------------|-------------------|
| Caddy | TLS termination, on-demand certificates | No changes needed | -> Nginx:80 |
| Nginx | Reverse proxy, security headers, gzip, static caching | NEW: security headers, gzip, caching, server_tokens off | -> PHP-FPM:9000 |
| PHP-FPM (app) | Application logic | NEW: non-root user, OPcache+JIT, read-only filesystem | -> MySQL:3306 |
| Queue worker | Async job processing | NEW: resource limits, healthcheck | -> MySQL:3306 |
| Scheduler | Laravel scheduled tasks | NEW: dedicated container, resource limits | -> MySQL:3306 |
| MySQL | Database | Existing: healthcheck, persistent volume | <- app, queue, scheduler |
| phpMyAdmin | Database admin UI | Dev only; removed from prod compose | -> MySQL:3306 |

### Data Flow (Unchanged)

```
Request: Browser -> Caddy (TLS) -> Nginx (proxy) -> PHP-FPM -> MySQL
Response: MySQL -> PHP-FPM -> Nginx (add headers, gzip) -> Caddy -> Browser
```

Security headers are added by Nginx on the response path, AFTER PHP-FPM returns the response. This means headers apply to all responses including error pages (nginx `always` keyword), static files, and PHP-generated pages.

## Patterns to Follow

### Pattern 1: Entrypoint + CMD Split

**What:** Use ENTRYPOINT for setup scripts, CMD for the default process.
**When:** Every container that needs pre-boot configuration.
**Why:** The existing `docker/prod/entrypoint.sh` handles storage permissions and user switching. CMD provides the default command that can be overridden per-service (app=php-fpm, queue=queue:work, scheduler=schedule:work).

```dockerfile
COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
```

### Pattern 2: OPcache via ini Overlay

**What:** Install OPcache extension in Dockerfile, configure via separate ini file.
**When:** All production PHP-FPM containers.
**Why:** Keeps configuration modular. The ini file can be bind-mounted in dev for tweaking, or baked in for production.

```dockerfile
# In Dockerfile:
RUN docker-php-ext-install opcache
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
```

### Pattern 3: Security Headers in Shared Include

**What:** Define security headers in a shared nginx include file, reference from both dev and prod configs.
**When:** Multiple server blocks need identical headers.
**Why:** Single source of truth; update once, applies everywhere. Both `app.conf` files include the same header block.

### Pattern 4: Healthcheck for Managed Services

**What:** Every Docker Compose service gets a `healthcheck` directive.
**When:** All services that other services depend on.
**Why:** Enables `depends_on: condition: service_healthy` for proper startup ordering. Currently only MySQL has a healthcheck.

```yaml
app:
  healthcheck:
    test: ["CMD-SHELL", "php-fpm-healthcheck || exit 1"]
    interval: 30s
    timeout: 3s
    start_period: 10s
    retries: 3
```

### Pattern 5: CI Pipeline as Quality Gate

**What:** Add code style, security audit, and Docker build validation to existing CI.
**When:** Every push to main and every PR.
**Why:** These checks are fast (Pint <10s, composer audit <5s, Docker build ~60s) and catch issues before merge.

## Anti-Patterns to Avoid

### Anti-Pattern 1: Bind-Mount in Production

**What:** Using `volumes: - ./:/var/www` in production compose.
**Why bad:** Defeats the multi-stage build. Host files (including `.env`, `vendor/`, `node_modules/`) overwrite the baked-in image. Also prevents `opcache.validate_timestamps=0` from working correctly.
**Instead:** Remove bind-mounts from `docker-compose.prod.yml`. Code is already copied into the image via `COPY --from=builder`.

### Anti-Pattern 2: Running as Root

**What:** Not setting `USER` in Dockerfile production stage.
**Why bad:** If PHP-FPM or any child process is compromised, attacker has root access inside the container, which maps to root on the host (without user namespaces).
**Instead:** `USER www-data` in production stage. Entrypoint handles initial root tasks (permission setup) then drops privileges via `gosu` or the existing `exec` logic.

### Anti-Pattern 3: Multiple Processes in One Container

**What:** Using supervisord to run FPM + queue + scheduler in a single container.
**Why bad:** Mixes logs, makes healthchecks ambiguous (which process failed?), defeats Docker Compose's orchestration.
**Instead:** Separate `app`, `queue`, and `scheduler` services in compose, each sharing the same image with different commands.

### Anti-Pattern 4: Strict CSP Without Testing

**What:** Deploying `Content-Security-Policy` without `unsafe-inline` and `unsafe-eval`.
**Why bad:** Livewire 4 morphing engine and Alpine.js both require inline scripts. Strict CSP breaks all interactive UI.
**Instead:** Start with permissive CSP (`unsafe-inline`, `unsafe-eval`), then tighten iteratively. The headers still protect against all other injection vectors.

### Anti-Pattern 5: Unpinned Image Tags

**What:** Using `nginx:latest` or `mysql:latest` in compose files.
**Why bad:** `latest` is a moving target. A rebuild 6 months later pulls a different nginx version with different defaults, potentially breaking the configuration.
**Instead:** Use `nginx:alpine` (pinned to minor) or `mysql:8.0` (pinned to major). The current `mysql:8.0` is correct.

## Scalability Considerations

| Concern | Current State | After Hardening | At 100 Tenants |
|---------|--------------|-----------------|-----------------|
| PHP throughput | No OPcache (30-50% wasted) | OPcache + JIT enabled | Same -- OPcache is per-process, scales linearly |
| Memory per container | Unbounded (can OOM host) | Resource limits applied | Tune limits based on observed usage |
| Scheduler | Not running | Dedicated container | Same -- schedule:work is single-process, sufficient |
| Queue throughput | Single worker | Single worker with healthcheck | Add more queue workers: `docker compose up --scale queue=3` |
| CI pipeline time | ~2 min (tests only) | ~3 min (+pint, audit, docker build) | Same -- CI doesn't grow with tenants |
| Image size | Unknown (no .dockerignore) | Smaller (excludes .git, tests, node_modules) | Same -- multi-stage build is fixed overhead |

## Sources

- Existing codebase: Dockerfile, docker-compose.yml, docker-compose.prod.yml, docker/prod/entrypoint.sh, nginx configs, ci.yml
- Docker documentation: ENTRYPOINT vs CMD, healthcheck, multi-stage builds
- OWASP: Docker Security Cheat Sheet
- Nginx documentation: security headers, gzip, caching directives
- PHP documentation: OPcache configuration, JIT settings
- Laravel 12 documentation: scheduler, queue worker, deployment

---
*Architecture research for: TenantSmith INFRA Hardening*
*Researched: 2026-06-27*
