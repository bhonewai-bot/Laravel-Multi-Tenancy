# Domain Pitfalls — INFRA Hardening

**Domain:** Docker infrastructure hardening for Laravel multi-tenancy platform
**Researched:** 2026-06-27

## Critical Pitfalls

Mistakes that cause broken deployments or security regressions.

### Pitfall 1: CSP Breaking Livewire

**What goes wrong:** Deploying a strict Content-Security-Policy header that omits `unsafe-inline` and `unsafe-eval` causes all Livewire interactions (form submissions, reactive updates, morphing) to fail silently in the browser. Console shows CSP violation errors. The site loads but nothing interactive works.

**Why it happens:** Livewire 4 injects inline `<script>` tags for component initialization and uses `eval()` internally for its morphing engine. Alpine.js also bootstraps via inline `x-data` attributes that generate inline script. A strict CSP blocks both.

**Consequences:** Complete loss of interactivity. Users see static pages that don't respond to clicks, form submissions, or navigation. Looks like a "broken app" but is actually a browser security enforcement.

**Prevention:** Always include `'unsafe-inline' 'unsafe-eval'` in the `script-src` directive. Test the full CSP in a staging environment before production. Check browser console for CSP violations after deployment.

**Detection:** Open browser DevTools -> Console -> look for "Refused to execute inline script" or "Content-Security-Policy directive violations". Any interactive element not working.

### Pitfall 2: OPcache validate_timestamps=0 with Bind-Mounts

**What goes wrong:** Setting `opcache.validate_timestamps=0` while the application code is bind-mounted (not baked into the image) causes PHP to serve stale cached bytecode. Code changes are invisible until the container is fully restarted.

**Why it happens:** `validate_timestamps=0` tells OPcache to never check if source files changed. This is correct for production (code doesn't change in a built image) but catastrophic during development or if a production deploy uses bind-mounts.

**Consequences:** Deployments appear to succeed but serve old code. Bug fixes, security patches, and feature additions are invisible. Can persist across container restarts if OPcache has `opcache.file_cache` enabled.

**Prevention:** Only set `validate_timestamps=0` in production when code is baked into the image (no bind-mounts). For development, keep `validate_timestamps=1` with a short `revalidate_freq`.

**Detection:** After deploying new code, verify a change is visible. If not, check `php -i | grep opcache.validate_timestamps`. Restart FPM to clear OPcache.

### Pitfall 3: DockerFile Case Mismatch on Linux

**What goes wrong:** `docker-compose.yml` references `dockerfile: DockerFile` but the actual file is `Dockerfile`. Works on macOS (case-insensitive filesystem) but fails on Linux CI runners with "Dockerfile not found."

**Why it happens:** Developer works on macOS where HFS+ is case-insensitive. The mismatch is invisible locally. GitHub Actions runners use Ubuntu with case-sensitive ext4.

**Consequences:** CI pipeline fails with a confusing "unable to prepare context: unable to evaluate symlinks in context path" error. Blocks all PRs.

**Prevention:** Rename the actual file to `Dockerfile` and update all compose references. Verify with `ls -la` that the file exists with the exact case used in compose files.

**Detection:** Run `docker compose config` on Linux or in CI. If the file reference is wrong, it will error.

## Moderate Pitfalls

### Pitfall 4: Entry Point Permission Race

**What goes wrong:** The entrypoint runs as root to set up storage permissions, then drops to `www-data`. If the permission setup fails (e.g., storage directory doesn't exist yet because a volume mount is missing), the drop to `www-data` still happens, and PHP-FPM starts without write access to storage.

**Prevention:** The existing `entrypoint.sh` already handles this correctly with `mkdir -p` and `chmod`. Ensure the ENTRYPOINT is wired BEFORE the USER directive in the Dockerfile. The entrypoint needs root to set permissions, then uses `gosu` or the existing exec logic to drop privileges.

### Pitfall 5: Resource Limits Too Aggressive

**What goes wrong:** Setting memory limits too low causes the queue worker to be OOM-killed mid-job. Module installation jobs (which run tenant database migrations) can spike memory. The container restarts, the job retries, and the same spike kills it again -- infinite crash loop.

**Prevention:** Start with generous limits (512M for app/queue) and tighten after observing real usage. Use `docker stats` during a module install to see actual memory consumption. The `--memory-reservation` soft limit can warn before hitting the hard limit.

### Pitfall 6: Security Headers in Error Contexts

**What goes wrong:** Nginx `add_header` without the `always` keyword only adds headers to successful responses (2xx, 3xx). Error responses (403, 404, 500) don't get security headers, leaking information about the server.

**Prevention:** Always use `add_header ... always;` (the `always` keyword) for security headers. This ensures headers are present on ALL response codes.

### Pitfall 7: HSTS with includeSubDomains on Multi-Tenant

**What goes wrong:** Setting `Strict-Transport-Security: max-age=63072000; includeSubDomains; preload` on a multi-tenant platform with custom domains locks all subdomains into HTTPS for 2 years. If a tenant's custom domain has DNS issues or needs HTTP for verification, HSTS prevents it.

**Prevention:** Use `includeSubDomains` but skip `preload`. Do NOT submit to the HSTS preload list until tenant domain strategy is finalized. The `max-age` can be reduced initially (e.g., 1 hour) and increased once stable.

## Minor Pitfalls

### Pitfall 8: Scheduler Container Restart Storm

**What goes wrong:** If `php artisan schedule:work` exits with an error, Docker restarts it immediately (`restart: unless-stopped`). If the error is persistent (e.g., database connection failure), the container restarts every few seconds, flooding logs.

**Prevention:** The `restart: unless-stopped` policy with Docker's built-in backoff handles this reasonably. But add a healthcheck so `depends_on: condition: service_healthy` prevents the scheduler from starting before MySQL is ready.

### Pitfall 9: Gzip on Already-Compressed Assets

**What goes wrong:** Enabling `gzip on` without excluding already-compressed formats (images, fonts, PDFs) wastes CPU. Gzip can't compress pre-compressed data and just adds latency.

**Prevention:** Use `gzip_types` to explicitly list compressible MIME types (text/html, text/css, application/javascript, application/json, image/svg+xml). Don't use `gzip on` without `gzip_types`.

### Pitfall 10: Composer Audit False Sense of Security

**What goes wrong:** `composer audit` only checks for known CVEs in PHP packages at the time of the check. It does NOT scan Docker base images, npm packages, or OS-level vulnerabilities. Teams may think "CI passed, we're secure."

**Prevention:** Document that `composer audit` covers PHP dependencies only. NPM has `npm audit` for JS dependencies. Docker base images need Trivy/Snyk (deferred to future milestone). Make clear what each CI step does and does not cover.

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Dockerfile hardening | Forgetting to install gosu before wiring entrypoint | Install gosu in `apt-get install` step; entrypoint uses it for privilege drop |
| Dockerfile hardening | USER directive before ENTRYPOINT prevents entrypoint from running as root | Keep ENTRYPOINT before USER; entrypoint needs root for permission setup, drops to www-data |
| Docker Compose security | `cap_drop: ALL` breaks PHP-FPM's ability to bind to port 9000 | Add `cap_add: NET_BIND_SERVICE` only if binding to ports < 1024; port 9000 doesn't need it |
| Docker Compose security | `read_only: true` breaks artisan commands that write to storage | Mount writable dirs as tmpfs: `/tmp`, `/var/www/storage`, `/var/www/bootstrap/cache` |
| Nginx hardening | CSP blocks inline Livewire scripts | Include `'unsafe-inline' 'unsafe-eval'` in script-src; test thoroughly |
| Nginx hardening | `add_header` in location block overrides server-level headers | Put security headers in a shared include file, include from both server blocks |
| OPcache | `save_comments=0` breaks Laravel's route caching and attribute parsing | Always set `save_comments=1` for Laravel projects |
| OPcache | JIT instability on certain PHP-FPM builds | Start with `opcache.jit=1255`; if segfaults occur, fall back to `1235` |
| Scheduler | Schedule runs before database is ready | Use `depends_on: mysql: condition: service_healthy` |
| CI pipeline | `pint --test` fails because existing code has style violations | Run `vendor/bin/pint` to auto-fix first, then commit, then add `--test` to CI |
| CI pipeline | `docker build` in CI downloads all layers every time | Use `docker/setup-buildx-action` with cache; or accept the 60s cost for now |

## Sources

- OWASP Docker Security Cheat Sheet -- container security best practices
- MDN Content-Security-Policy documentation -- CSP behavior with Livewire
- PHP OPcache documentation -- validate_timestamps behavior
- Docker documentation -- ENTRYPOINT vs CMD ordering, healthcheck backoff
- Nginx documentation -- add_header `always` keyword, gzip_types directive
- Laravel 12 documentation -- schedule:work, OPcache compatibility notes

---
*Pitfall research for: TenantSmith INFRA Hardening*
*Researched: 2026-06-27*
