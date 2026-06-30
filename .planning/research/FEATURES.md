# Feature Research: INFRA Hardening

**Domain:** Docker infrastructure, nginx hardening, PHP OPcache, Laravel scheduler, CI pipeline
**Researched:** 2026-06-27
**Confidence:** MEDIUM

## Feature Landscape

### Table Stakes (Production Blockers)

These are the features identified in the INFRA audit (docs/audit-2026-06-25.md) as HIGH severity. Missing any of these means the platform is not safe to deploy.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| `.dockerignore` file | Prevents secrets (.env) from being baked into Docker image layers. Currently absent entirely. | LOW | One new file. Exclude `.env`, `.git/`, `vendor/`, `node_modules/`, `storage/`, `tests/`, `.github/`, `.planning/`, `*.md`, `docker/`, `caddy/`. |
| Non-root container user | PHP-FPM currently runs as root. Container compromise = host compromise. OWASP top Docker security practice. | MEDIUM | Dockerfile needs `USER www-data` directive. Entrypoint already has gosu logic but (a) gosu is not installed, (b) ENTRYPOINT directive is missing. Requires wiring `docker/prod/entrypoint.sh` and adding `RUN apt-get install -y gosu`. |
| Remove production bind-mount | `docker-compose.prod.yml` mounts `./:/var/www`, defeating the multi-stage build entirely. Host files overwrite baked-in image. | LOW | Remove the `volumes: - ./:/var/www` line from `docker-compose.prod.yml` app, queue, and nginx services. The nginx config volume mounts are fine (read-only config). |
| Wire entrypoint in Dockerfile | `docker/prod/entrypoint.sh` exists with proper logic (ensure writable paths, gosu drop) but is never referenced. | LOW | Add `ENTRYPOINT ["docker/prod/entrypoint.sh"]` and `CMD ["php-fpm"]` to Dockerfile production stage. Requires gosu install first. |
| nginx security headers | No security headers in either dev or prod nginx config. Missing X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS. | LOW | Add `include /etc/nginx/conf.d/security-headers.conf;` or inline headers to both server blocks. Standard boilerplate. |
| nginx gzip compression | No gzip in any nginx config. All text assets (HTML, CSS, JS, JSON, SVG) served uncompressed. | LOW | Add gzip block to nginx.conf or conf.d. Standard config, 60-80% bandwidth reduction for text. |
| Static asset caching | Vite generates content-hashed filenames (e.g., `app-8GCeNMCe.js`) but nginx serves them with no cache headers. | LOW | Add `location ~* \.(css|js|ico|gif|jpeg|jpg|png|woff|woff2|ttf|svg|eot)$` block with `expires 1y; add_header Cache-Control "public, immutable";`. |
| OPcache extension | Dockerfile does not install `opcache`. Missing 30-50% throughput improvement for PHP-FPM. | LOW | Add `docker-php-ext-install opcache` to Dockerfile and copy `opcache.ini` to conf.d. Requires bind-mount removal (validate_timestamps=0 needs baked-in code). |
| Scheduler service | No scheduler service in either compose file. Laravel scheduled tasks never run. | LOW | Add `scheduler` service to both compose files running `php artisan schedule:work`. Reuses same Docker image, just different command. |
| Queue worker health check | `queue` service has no healthcheck. Silent crash goes undetected. | LOW | Add healthcheck using process check or heartbeat file. |
| Rename `DockerFile` to `Dockerfile` | Capital F violates convention, confuses tools that expect standard name. | LOW | Rename file and update `dockerfile: DockerFile` references in both compose files. |
| CI: Docker build validation | CI never validates the Dockerfile builds. Broken Dockerfile only caught at deploy time. | LOW | Add `docker build -t app-test .` step to GitHub Actions. |
| CI: Pint code style check | No automated code style enforcement. Violations caught manually. | LOW | Add `vendor/bin/pint --test` step. Test mode fails on violations without modifying files. |
| CI: Composer audit | No automated security scanning of composer dependencies. | LOW | Add `composer audit` step. Scans composer.lock for known CVEs. |

### Differentiators (Beyond Minimum)

These go beyond the audit findings but add real operational value. They differentiate a well-run platform from a minimally-deployed one.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Docker resource limits (mem_limit, cpus) | Prevents runaway containers from consuming all host resources. Critical for shared hosting. | LOW | Add `mem_limit` and `cpus` to app, queue, nginx, scheduler services in both compose files. |
| OPcache JIT for PHP 8.3 | PHP 8.3 JIT (opcache.jit=1255) adds 10-20% on top of OPcache baseline. Free performance. | LOW | Just config values in opcache.ini. Zero code changes. |
| nginx server_tokens off | Hides nginx version from response headers. Defense-in-depth. | LOW | One line in nginx config. |
| Hide sensitive files in nginx | Block access to `.env`, `composer.json`, `package.json` via nginx. Defense-in-depth. | LOW | Add location blocks with `deny all;`. Prod Caddy handles most of this already but nginx should also protect. |
| CI: Dependency caching | Cache composer and npm dependencies between CI runs. Faster pipeline. | LOW | Add `actions/cache` for `~/.composer/cache` and `node_modules`. |
| CI: Hadolint Dockerfile linting | Automated Dockerfile best-practice checking. Catches anti-patterns before they ship. | MEDIUM | Add Hadolint step or GitHub Action. Requires learning what warnings to suppress vs fix. |
| Scheduled Cloudflare domain sweep | Schedule `domains:sync-cloudflare --all` to periodically check all domains. Auto-heals stuck verifications. | MEDIUM | Requires artisan command modification to accept `--all` flag. Then add to `routes/console.php` schedule. |

### Anti-Features (Avoid These)

Features commonly requested for Docker/Laravel infra that create more problems than they solve.

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Cron-based scheduler in Docker | "Just use cron like a real server" | Requires cron daemon install, crontab setup, log forwarding, process supervision. Cron in containers fights the one-process-per-container principle. | Use `php artisan schedule:work` -- runs in foreground, logs to stdout, Docker manages lifecycle. |
| Supervisor for scheduler + queue | "Run multiple processes in one container" | Adds supervisor dependency, config complexity, obscures which process failed, makes container logs mixed. Docker Compose already handles multi-container orchestration. | Separate services in docker-compose (app, queue, scheduler). Each has its own logs, healthcheck, restart policy. |
| Full container vulnerability scanning (Trivy/Snyk) in CI | "Scan for CVEs in base images" | Adds 2-5 minutes to CI, generates noise from base image CVEs that have no impact, requires ongoing triage. Overkill for a solo-dev project at this stage. | Use `composer audit` for PHP dependency CVEs. Add Trivy later when the project has more contributors or compliance needs. |
| Read-only filesystem (--read-only) | "Best practice from OWASP" | Laravel storage/ and bootstrap/cache/ must be writable. Requires tmpfs mounts for each writable path, complex permission management, breaks artisan commands. | Use non-root user + proper file permissions. The entrypoint already handles writable path setup. |
| Multi-environment compose overrides (dev/staging/prod) | "Separate configs per environment" | Three compose files with override mechanics that are hard to reason about. Solo dev project with one production target. | Two compose files: `docker-compose.yml` (dev) and `docker-compose.prod.yml` (prod). Clear, explicit, no inheritance confusion. |
| Kubernetes / Docker Swarm | "Production-grade orchestration" | Massive operational complexity for a single-host deployment. Adds etcd, ingress controllers, service mesh, helm charts. | Docker Compose with restart policies. Simple, works on a single VPS, easy to understand and debug. |
| SSL certificate management in nginx | "Manage TLS in nginx directly" | Caddy already handles on-demand TLS via Cloudflare Custom Hostnames. Adding nginx TLS creates a second certificate management surface. | Keep Caddy as the TLS termination layer. nginx sits behind Caddy on port 80 only. Current architecture is correct. |
| PHPStan / static analysis in CI | "Catch bugs before they ship" | High setup cost, many false positives on dynamic Laravel code (facades, magic methods), ongoing maintenance of baseline. Solo dev learns more from tests. | Tests are the primary quality gate. Add PHPStan later if codebase grows or team expands. |

## Feature Dependencies

```
.dockerignore ─────────────────────────────────────────────────┐
                                                               │
Remove bind-mount ─────────────────────────────────────────────┤
                                                               ├──> OPcache (validate_timestamps=0)
Wire entrypoint ──requires──> Install gosu                     │     needs baked-in code
                                                               │
Rename Dockerfile ──standalone─────────────────────────────────┘

nginx security headers ──standalone──> (no dependencies)

nginx gzip ──standalone──> (no dependencies)

Static asset caching ──standalone──> (no dependencies)

Scheduler service ──standalone──> (shares image with app)

CI: Docker build ──depends──> .dockerignore + Dockerfile rename
CI: Pint ──standalone──> (no dependencies)
CI: Composer audit ──standalone──> (no dependencies)

OPcache JIT ──enhances──> OPcache base

Resource limits ──standalone──> (no dependencies)

Cloudflare sweep ──depends──> Scheduler service
```

### Dependency Notes

- **OPcache requires bind-mount removal:** Setting `validate_timestamps=0` means PHP never checks if files changed. This only works when code is baked into the image. If source is bind-mounted, OPcache serves stale code after edits.
- **CI Docker build depends on .dockerignore:** Without .dockerignore, the Docker build copies everything including .env into image layers in CI too. The .dockerignore must exist before the CI build step validates anything useful.
- **Cloudflare sweep depends on Scheduler:** The sweep command needs a running scheduler to execute periodically. Add the scheduler first, then schedule the sweep.
- **Entrypoint requires gosu:** The entrypoint script uses `gosu` to drop from root to www-data. gosu must be installed in the Dockerfile before the entrypoint is wired.

## MVP Definition

### Launch With (v1.1 INFRA Hardening)

Everything from the audit's HIGH and MEDIUM severity items, plus the LOW items that are trivially implementable.

- [x] `.dockerignore` -- prevents secret leakage in image layers (INFRA-HIGH-3)
- [x] Non-root container user + wire entrypoint + install gosu (INFRA-HIGH-2)
- [x] Remove production bind-mount (INFRA-HIGH-1)
- [x] nginx security headers (INFRA-HIGH-4)
- [x] nginx gzip compression (INFRA-HIGH-6)
- [x] Static asset caching (INFRA-HIGH-6)
- [x] Scheduler service (INFRA-HIGH-5)
- [x] OPcache extension + config (INFRA-MED-1)
- [x] Queue worker health check (INFRA-MED-3)
- [x] Docker build validation in CI (INFRA-MED-4)
- [x] Pint + Composer audit in CI (INFRA-MED-5)
- [x] Rename DockerFile to Dockerfile (INFRA-LOW-1)
- [x] Docker resource limits (INFRA-MED-2)

### Add After Validation (v1.2)

- [ ] OPcache JIT tuning -- measure baseline first, then enable JIT
- [ ] Scheduled Cloudflare domain sweep -- add once scheduler is running and stable
- [ ] CI dependency caching -- add when pipeline feels slow

### Future Consideration (v2+)

- [ ] Hadolint Dockerfile linting -- add when team grows
- [ ] Trivy container scanning -- add when compliance needs arise
- [ ] PHPStan static analysis -- add when codebase complexity warrants

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| `.dockerignore` | HIGH (security) | LOW (one file) | P1 |
| Non-root user + entrypoint | HIGH (security) | MEDIUM (Dockerfile + gosu) | P1 |
| Remove bind-mount | HIGH (security) | LOW (delete lines) | P1 |
| nginx security headers | HIGH (security) | LOW (boilerplate) | P1 |
| OPcache | HIGH (performance) | LOW (ext install + ini) | P1 |
| Scheduler service | HIGH (functionality) | LOW (one service) | P1 |
| nginx gzip | MEDIUM (performance) | LOW (config block) | P1 |
| Static asset caching | MEDIUM (performance) | LOW (location block) | P1 |
| CI: Docker build | MEDIUM (reliability) | LOW (one step) | P1 |
| CI: Pint + audit | MEDIUM (quality) | LOW (two steps) | P1 |
| Resource limits | MEDIUM (stability) | LOW (config values) | P1 |
| Rename DockerFile | LOW (convention) | LOW (rename + refs) | P1 |
| Queue health check | MEDIUM (reliability) | LOW (healthcheck) | P1 |
| OPcache JIT | MEDIUM (performance) | LOW (config values) | P2 |
| Cloudflare sweep | MEDIUM (automation) | MEDIUM (command + schedule) | P2 |
| CI caching | LOW (speed) | LOW (cache action) | P2 |
| Hadolint | LOW (quality) | MEDIUM (setup + suppression) | P3 |
| Trivy scanning | LOW (security depth) | MEDIUM (triage overhead) | P3 |
| PHPStan | LOW (quality) | HIGH (baseline setup) | P3 |

**Priority key:**
- P1: Must have for this milestone (INFRA Hardening)
- P2: Should have, add in next milestone
- P3: Nice to have, future consideration

## Current State Assessment

Based on direct codebase inspection, here is what exists vs what is missing:

### Exists (partially correct)

| Component | Status | Issue |
|-----------|--------|-------|
| Multi-stage Dockerfile | Correct structure (base, builder, production) | Missing: gosu, USER directive, ENTRYPOINT, opcache ext |
| `docker/prod/entrypoint.sh` | Correct logic (writable paths, gosu drop) | Never wired into Dockerfile via ENTRYPOINT |
| `docker-compose.yml` (dev) | Works for local development | Missing: scheduler service, health checks, resource limits |
| `docker-compose.prod.yml` | Has env_file, production env vars | Defeated by bind-mount `./:/var/www` |
| nginx dev config | Basic PHP-FPM proxy works | Missing: security headers, gzip, caching |
| nginx prod config | SSL termination, Cloudflare challenge path | Missing: security headers, gzip, caching, sensitive file blocking |
| CI pipeline | Runs tests, npm build, composer validate | Missing: Docker build, Pint, composer audit |

### Missing Entirely

| Component | Impact |
|-----------|--------|
| `.dockerignore` | Secrets leak into image layers |
| OPcache extension | 30-50% throughput left on table |
| Scheduler service | No scheduled tasks run in production |
| Security headers | No XSS/clickjacking/MIME-sniffing protection |
| Gzip compression | All text assets served uncompressed |
| Static asset cache headers | Vite-hashed files re-downloaded every page load |

## Sources

- **Primary:** docs/audit-2026-06-25.md (INFRA-HIGH-1 through INFRA-LOW-2, direct codebase analysis)
- **Codebase:** Dockerfile, docker-compose.yml, docker-compose.prod.yml, docker/nginx/conf.d/, docker/prod/entrypoint.sh, .github/workflows/ci.yml, routes/console.php (direct inspection)
- **Standards:** OWASP Docker Security Cheat Sheet, OWASP Secure Headers Project
- **Framework:** Laravel 12 deployment documentation, php:8.3-fpm Docker image documentation
- **Research confidence:** MEDIUM -- findings cross-verified between web research and direct codebase/audit inspection

---
*Feature research for: TenantSmith INFRA Hardening*
*Researched: 2026-06-27*
