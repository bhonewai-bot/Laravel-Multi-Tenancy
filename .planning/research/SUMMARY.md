# Project Research Summary

**Project:** TenantSmith — INFRA Hardening
**Domain:** Docker infrastructure security hardening for Laravel multi-tenancy platform
**Researched:** 2026-06-27
**Confidence:** MEDIUM-HIGH

## Executive Summary

TenantSmith is a Laravel multi-tenancy platform with a Docker Compose deployment stack (PHP-FPM + Nginx + Caddy + MySQL). A security audit revealed the infrastructure is not production-ready: containers run as root, secrets can leak into image layers, nginx serves responses without security headers, OPcache is not installed, and the CI pipeline does not validate Docker builds. The research confirms that all identified gaps have straightforward, well-documented fixes requiring only configuration changes — no new packages or architectural rework.

The recommended approach follows a "harden what exists" strategy rather than introducing new tools. Every fix is a configuration file change: a `.dockerignore` file, Dockerfile directives, docker-compose security options, nginx header/gzip/cache blocks, an OPcache ini file, and CI pipeline steps. The existing `docker/prod/entrypoint.sh` already contains correct logic for privilege dropping — it just needs to be wired into the Dockerfile. This is low-risk work with high security payoff.

The key risk is Content-Security-Policy misconfiguration breaking Livewire 4 and Alpine.js interactivity. Livewire's morphing engine requires `unsafe-inline` and `unsafe-eval` in `script-src`, which must be included from the start. A secondary risk is OPcache `validate_timestamps=0` interacting badly if bind-mounts are not fully removed from production compose. Both pitfalls are well-understood and have clear prevention strategies documented in PITFALLS.md.

## Key Findings

### Recommended Stack

All changes are configuration-only. No new Composer packages or npm dependencies are needed. The stack remains PHP 8.3, Laravel 12, Livewire 4, MySQL 8.0, Nginx, Caddy 2.8.

**Core configurations:**
- **`.dockerignore`** (new): Prevents `.env`, `.git/`, `vendor/`, `node_modules/` from entering image layers — eliminates secret leakage vector
- **Non-root USER + entrypoint wiring**: `USER www-data` in Dockerfile production stage; wire existing `docker/prod/entrypoint.sh` via ENTRYPOINT; install `gosu` for privilege drop
- **Docker security options**: `cap_drop: ALL`, `security_opt: no-new-privileges`, resource limits (512M app/queue, 128M nginx, 256M scheduler, 1G MySQL)
- **OPcache extension + JIT**: `docker-php-ext-install opcache` with `opcache.jit=1255` (PHP 8.3 tracing JIT) — 30-50% throughput gain over baseline, additional 10-20% from JIT
- **Nginx hardening**: Security headers (CSP with Livewire-compatible `unsafe-inline`/`unsafe-eval`), gzip compression, static asset caching with 1-year expiry, `server_tokens off`
- **Scheduler service**: Dedicated container running `php artisan schedule:work` — Docker-native, no cron daemon needed
- **CI additions**: `vendor/bin/pint --test`, `composer audit`, `docker build` validation — all under 90 seconds added to pipeline

### Expected Features

**Must have (table stakes — P1, all 13 items):**
- `.dockerignore` file — prevents secrets in image layers
- Non-root container user + wired entrypoint + gosu — OWASP Docker security baseline
- Remove production bind-mount from `docker-compose.prod.yml` — defeats multi-stage build
- Nginx security headers (CSP, HSTS, X-Content-Type-Options, Referrer-Policy) — OWASP top-10 mitigations
- Nginx gzip compression — 60-80% bandwidth reduction for text assets
- Static asset caching — Vite-hashed files get 1-year cache headers
- OPcache extension + configuration — 30-50% PHP throughput improvement
- Scheduler service — Laravel scheduled tasks currently never execute
- Queue worker health check — silent crash detection
- Rename `DockerFile` to `Dockerfile` — breaks Linux CI (case-sensitive filesystem)
- Docker resource limits — prevents runaway container OOM on host
- CI: Docker build + Pint + Composer audit — quality gates before merge

**Should have (differentiators — P2):**
- OPcache JIT tuning — measure baseline first, then enable
- Scheduled Cloudflare domain sweep — auto-heal stuck verifications
- CI dependency caching — faster pipeline when it feels slow

**Defer (v2+):**
- Hadolint Dockerfile linting — add when team grows
- Trivy container scanning — add when compliance needs arise
- PHPStan static analysis — add when codebase complexity warrants

### Architecture Approach

The architecture remains unchanged: Internet -> Caddy (TLS) -> Nginx (reverse proxy) -> PHP-FPM -> MySQL. Hardening adds security layers to existing components without changing the request flow. A new `scheduler` service joins the existing `queue` service as a dedicated container sharing the same Docker image with a different CMD.

**Major components to modify:**
1. **Dockerfile** — Add OPcache extension, gosu install, ENTRYPOINT/CMD wiring, USER directive in production stage
2. **docker-compose.yml / docker-compose.prod.yml** — Add security options (cap_drop, no-new-privileges, resource limits), scheduler service, healthchecks, remove prod bind-mounts
3. **Nginx configs (dev + prod)** — Add security headers, gzip block, static asset caching, server_tokens off
4. **OPcache ini (new)** — `docker/php/conf.d/opcache.ini` with production-tuned settings including JIT
5. **CI pipeline** — Add Pint, composer audit, Docker build steps to `.github/workflows/ci.yml`
6. **`.dockerignore` (new)** — Exclude secrets, build artifacts, dev files from image context

### Critical Pitfalls

1. **CSP breaking Livewire** — Livewire 4 and Alpine.js require `unsafe-inline` and `unsafe-eval` in `script-src`. A strict CSP without these silently disables all interactive UI. Always include them; tighten iteratively later.
2. **OPcache with bind-mounts** — `validate_timestamps=0` only works when code is baked into the image. If bind-mounts remain in production compose, OPcache serves stale code. Remove bind-mounts BEFORE enabling OPcache.
3. **DockerFile case mismatch** — `dockerfile: DockerFile` in compose files works on macOS (case-insensitive) but fails on Linux CI. Rename to `Dockerfile` and update all references.
4. **ENTRYPOINT ordering** — The entrypoint must run BEFORE the USER directive so it can set up permissions as root, then drop to www-data via gosu. Reversing this breaks permission setup.
5. **Resource limits too aggressive** — Queue workers running module install migrations can spike memory. Start generous (512M) and tighten with observed data.

## Implications for Roadmap

Based on research, suggested phase structure:

### Phase 1: Dockerfile & Build Context Hardening
**Rationale:** Foundation for all other changes. `.dockerignore` must exist before CI Docker build validation is useful. Bind-mount removal is prerequisite for OPcache `validate_timestamps=0`. Dockerfile rename fixes Linux CI.
**Delivers:** Secure Dockerfile with non-root user, wired entrypoint, OPcache extension, `.dockerignore` file, renamed to `Dockerfile`
**Addresses:** `.dockerignore`, non-root user, remove bind-mount, wire entrypoint, rename DockerFile, OPcache extension
**Avoids:** Pitfall 3 (case mismatch on Linux CI), Pitfall 2 (OPcache stale code), Pitfall 4 (entrypoint permission race)

### Phase 2: Nginx Hardening
**Rationale:** Independent of Phase 1 Dockerfile changes. Can be developed and tested in parallel but logically follows the build foundation.
**Delivers:** Security headers (CSP, HSTS, X-Content-Type-Options, Referrer-Policy, Permissions-Policy), gzip compression, static asset caching, server_tokens off, sensitive file blocking
**Addresses:** nginx security headers, gzip, static asset caching, server_tokens off
**Avoids:** Pitfall 1 (CSP breaking Livewire), Pitfall 6 (missing headers on error responses with `always` keyword), Pitfall 9 (gzip on compressed assets), Pitfall 7 (HSTS preload on multi-tenant)

### Phase 3: Docker Compose Security & Services
**Rationale:** Depends on Phase 1 (Dockerfile changes must be in place for compose to reference). Adds security options and the new scheduler service.
**Delivers:** cap_drop ALL, no-new-privileges, resource limits on all services, scheduler service, queue worker healthcheck
**Addresses:** Docker resource limits, scheduler service, queue health check
**Avoids:** Pitfall 5 (resource limits too aggressive — start generous), Pitfall 8 (scheduler restart storm — healthcheck gates startup)

### Phase 4: OPcache & Performance
**Rationale:** Must come after Phase 1 (OPcache extension installed) and Phase 3 (bind-mounts removed). Enables JIT after baseline measurement.
**Delivers:** OPcache ini overlay with production settings, PHP 8.3 JIT (opcache.jit=1255), validate_timestamps=0 for production
**Addresses:** OPcache configuration, OPcache JIT tuning
**Avoids:** Pitfall 2 (stale code from validate_timestamps=0 with bind-mounts)

### Phase 5: CI Pipeline Hardening
**Rationale:** Comes last because it validates everything from Phases 1-4. The Docker build step requires `.dockerignore` (Phase 1) and Dockerfile (Phase 1) to be correct. Pint requires running auto-fix on existing code first.
**Delivers:** Pint code style enforcement, composer dependency audit, Docker build validation in CI
**Addresses:** CI Docker build, Pint check, Composer audit
**Avoids:** Pitfall 10 (composer audit false sense of security — document coverage scope), phase-specific warning about `pint --test` failing on existing violations (run auto-fix first)

### Phase Ordering Rationale

- **Phase 1 first** because `.dockerignore` and Dockerfile fixes are prerequisites for OPcache, CI Docker build, and security. Everything else depends on a correct Dockerfile.
- **Phase 2 independent** but placed after Phase 1 for logical grouping (all config-file changes).
- **Phase 3 after Phase 1** because compose security options reference the Dockerfile (ENTRYPOINT, USER).
- **Phase 4 after Phases 1+3** because OPcache requires the extension (Phase 1) and bind-mount removal (Phase 3).
- **Phase 5 last** because CI validates the cumulative result of all prior phases.
- Phases 1-4 are all configuration-only with no application code changes, keeping risk minimal.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 4:** OPcache JIT settings (`1255` vs `1235` vs `1237`) may need environment-specific validation. Fall-back strategy documented but actual stability testing needed during planning.

Phases with standard patterns (skip research-phase):
- **Phase 1:** Standard Dockerfile best practices — well-documented in Docker and OWASP docs
- **Phase 2:** Standard nginx configuration — security headers and gzip are boilerplate
- **Phase 3:** Standard docker-compose security options — well-documented directives
- **Phase 5:** Standard CI pipeline additions — GitHub Actions patterns are well-established

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All changes are configuration-only using well-documented Docker/nginx/PHP features. No new packages. Version compatibility verified. |
| Features | MEDIUM | Feature prioritization based on audit findings (docs/audit-2026-06-25.md) cross-verified with OWASP and Docker docs. MEDIUM because actual resource limit tuning requires production observation. |
| Architecture | HIGH | Architecture is unchanged — only adding security layers to existing components. Request flow, component boundaries, and data flow remain identical. |
| Pitfalls | HIGH | All pitfalls sourced from official documentation (OWASP, MDN, Docker, Nginx, PHP) with specific prevention strategies. CSP/Livewire interaction verified from Livewire 4 internals. |

**Overall confidence:** MEDIUM-HIGH — High confidence in all technical details. MEDIUM gap only on OPcache JIT stability and optimal resource limits, both of which require runtime observation.

### Gaps to Address

- **OPcache JIT stability:** The research recommends `opcache.jit=1255` (aggressive tracing) but notes fallback to `1235` if segfaults occur. Validation requires running the built container under load. Address in Phase 4 planning by defining a rollback procedure.
- **Resource limit tuning:** Starting limits (512M app/queue, 1G MySQL) are educated guesses. After Phase 3, run `docker stats` during a module install to calibrate. Document as a follow-up task.
- **Existing code style violations:** `pint --test` in CI will fail if existing code has style violations. Phase 5 must include a "run `vendor/bin/pint` to auto-fix, commit, then add `--test` to CI" sub-step.
- **Read-only filesystem viability:** The research flags `read_only: true` as an anti-feature due to Laravel's writable storage/bootstrap/cache needs. The tmpfs workaround exists but adds complexity. Recommend skipping read-only filesystem for this milestone and revisiting in v2+.

## Sources

### Primary (HIGH confidence)
- docs/audit-2026-06-25.md — INFRA-HIGH-1 through INFRA-LOW-2 findings (direct codebase analysis)
- Docker official documentation — .dockerignore, HEALTHCHECK, resource constraints, ENTRYPOINT vs CMD
- OWASP Docker Security Cheat Sheet — container security best practices
- OWASP Secure Headers Project — security header recommendations
- PHP manual — OPcache configuration directives, JIT compilation modes
- Nginx official documentation — gzip module, add_header `always` keyword, server_tokens
- Laravel 12 documentation — schedule:work command, deployment recommendations
- MDN Web Docs — Content-Security-Policy, Strict-Transport-Security, Permissions-Policy

### Secondary (MEDIUM confidence)
- Existing codebase inspection — Dockerfile, docker-compose.yml, docker-compose.prod.yml, nginx configs, entrypoint.sh, ci.yml
- Livewire 4 internals — inline script injection and eval() usage for CSP compatibility assessment

### Tertiary (LOW confidence)
- OPcache JIT mode `1255` stability — community consensus is positive but environment-specific validation needed
- Resource limit starting points — educated estimates; requires production observation to calibrate

---
*Research completed: 2026-06-27*
*Ready for roadmap: yes*
