# Requirements — v1.1 INFRA Hardening

**Defined:** 2026-06-27
**Core Value:** The Docker infrastructure is secure, performant, and production-ready.

## Validated (v1.0 — Security Hardening)

All v1 requirements are validated and complete:

- **AUTH-01 to AUTH-04**: Central admin authorization — `EnsureCentralAdmin` middleware + Gate
- **UPLOAD-01 to UPLOAD-04**: Module upload security — ZIP sanitization, extension blocklist
- **STATE-01 to STATE-04**: Module state persistence — `module_installations` + `module_operations` tables

## v1.1 Requirements

### Dockerfile & Build Context

- [x] **DOCKER-01**: A `.dockerignore` file excludes `.env`, `.git/`, `vendor/`, `node_modules/`, `docker/`, `tests/`, and other non-production files from the Docker build context
- [x] **DOCKER-02**: The production Dockerfile stage runs as non-root user (`www-data`) using a `USER` directive after entrypoint execution
- [x] **DOCKER-03**: The existing `docker/prod/entrypoint.sh` is wired into the Dockerfile via `ENTRYPOINT` so it executes on container start (handles storage permissions and gosu privilege drop)
- [x] **DOCKER-04**: The OPcache PHP extension is installed in the Dockerfile via `docker-php-ext-install opcache`
- [x] **DOCKER-05**: Production `docker-compose.prod.yml` does not bind-mount the application directory — code is baked into the image via multi-stage build
- [x] **DOCKER-06**: The Dockerfile is named `Dockerfile` (not `DockerFile`) and all compose file references are updated to match — fixes Linux CI builds

### Nginx Hardening

- [ ] **NGINX-01**: Nginx adds Content-Security-Policy header with `script-src 'self' 'unsafe-inline' 'unsafe-eval'` compatible with Livewire 4 and Alpine.js
- [ ] **NGINX-02**: Nginx adds `Strict-Transport-Security` header (HSTS) with appropriate max-age
- [ ] **NGINX-03**: Nginx adds `X-Content-Type-Options: nosniff` and `Referrer-Policy: strict-origin-when-cross-origin` headers
- [ ] **NGINX-04**: Nginx enables gzip compression for text/html, text/css, application/javascript, application/json, and image/svg+xml
- [ ] **NGINX-05**: Nginx caches Vite-hashed static assets (JS, CSS, images) with 1-year expiry and immutable directive
- [ ] **NGINX-06**: Nginx disables `server_tokens` to hide version information

### Docker Compose Security & Services

- [ ] **COMPOSE-01**: All containers use `cap_drop: [ALL]` to remove unnecessary Linux capabilities
- [ ] **COMPOSE-02**: All containers use `security_opt: [no-new-privileges]` to block privilege escalation
- [ ] **COMPOSE-03**: Resource limits are set for all services (app: 512M/1.0 CPU, nginx: 128M/0.5 CPU, queue: 512M/1.0 CPU, scheduler: 256M/0.5 CPU, mysql: 1G/1.0 CPU)
- [ ] **COMPOSE-04**: A `scheduler` service is added running `php artisan schedule:work` using the same Docker image as the app
- [ ] **COMPOSE-05**: The `queue` service has a health check that monitors worker responsiveness

### OPcache & Performance

- [ ] **OPCACHE-01**: An OPcache configuration file (`docker/php/conf.d/opcache.ini`) is created with production settings: `validate_timestamps=0`, `max_accelerated_files=10000`, `memory_consumption=128`
- [ ] **OPCACHE-02**: OPcache JIT is configured with `opcache.jit=1255` for PHP 8.3 tracing JIT

### CI Pipeline

- [ ] **CI-01**: CI pipeline runs `vendor/bin/pint --test` to enforce code style — fails the build on violations
- [ ] **CI-02**: CI pipeline runs `composer audit` to check for known vulnerabilities in dependencies
- [ ] **CI-03**: CI pipeline validates that `docker build` succeeds — catches Dockerfile errors before merge

## Out of Scope

| Feature | Reason |
|---------|--------|
| Hadolint Dockerfile linting | Defer to when team grows |
| Trivy container scanning | Defer to compliance milestone |
| PHPStan static analysis | Defer to codebase complexity milestone |
| CI dependency caching | Optimize later when pipeline feels slow |
| Scheduled Cloudflare domain sweep | Defer to v2 |
| VPS public IP / custom domain deployment | Deferred per user request |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| DOCKER-01 | Phase 4 | Complete |
| DOCKER-02 | Phase 4 | Complete |
| DOCKER-03 | Phase 4 | Complete |
| DOCKER-04 | Phase 4 | Complete |
| DOCKER-05 | Phase 4 | Complete |
| DOCKER-06 | Phase 4 | Complete |
| NGINX-01 | Phase 5 | Pending |
| NGINX-02 | Phase 5 | Pending |
| NGINX-03 | Phase 5 | Pending |
| NGINX-04 | Phase 5 | Pending |
| NGINX-05 | Phase 5 | Pending |
| NGINX-06 | Phase 5 | Pending |
| COMPOSE-01 | Phase 6 | Pending |
| COMPOSE-02 | Phase 6 | Pending |
| COMPOSE-03 | Phase 6 | Pending |
| COMPOSE-04 | Phase 6 | Pending |
| COMPOSE-05 | Phase 6 | Pending |
| OPCACHE-01 | Phase 7 | Pending |
| OPCACHE-02 | Phase 7 | Pending |
| CI-01 | Phase 8 | Pending |
| CI-02 | Phase 8 | Pending |
| CI-03 | Phase 8 | Pending |

**Coverage:**

- v1.1 requirements: 22 total
- Mapped to phases: 22
- Unmapped: 0

---
*Requirements defined: 2026-06-27*
*Last updated: 2026-06-27*
