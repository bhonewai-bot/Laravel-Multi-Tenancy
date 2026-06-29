# Roadmap: TenantSmith

## Milestones

- [x] **v1.0 Security Hardening** - Phases 1-3 (shipped 2026-06-27)
- [ ] **v1.1 INFRA Hardening** - Phases 4-8 (in progress)

## Phases

**Phase Numbering:**

- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

<details>
<summary>[x] v1.0 Security Hardening (Phases 1-3) - SHIPPED 2026-06-27</summary>

### Phase 1: Central Admin Authorization

**Goal**: Only the configured super-admin can access central CRUD routes -- any other authenticated user is blocked
**Plans**: 2 plans

Plans:

- [x] 01-01: TBD
- [x] 01-02: TBD

### Phase 2: Module Upload Security

**Goal**: Module ZIP uploads are restricted to admins and sanitized so that no executable file types can be extracted to the filesystem
**Plans**: 2 plans

Plans:

- [x] 02-01: TBD
- [x] 02-02: TBD

### Phase 3: Module State Persistence

**Goal**: Module installation and operation records are stored in dedicated database tables with atomic transactions, eliminating race conditions from JSON blob read-modify-write
**Plans**: 2 plans

Plans:

- [x] 03-01: TBD
- [x] 03-02: TBD

</details>

### v1.1 INFRA Hardening (In Progress)

**Milestone Goal:** Production-ready Docker infrastructure with security headers, caching, OPcache, scheduler, and CI hardening.

- [ ] **Phase 4: Dockerfile & Build Context** - Secure the Dockerfile with .dockerignore, non-root user, wired entrypoint, OPcache extension, bind-mount removal, and consistent naming
- [ ] **Phase 5: Nginx Hardening** - Add security headers (CSP, HSTS, X-Content-Type-Options, Referrer-Policy), gzip compression, static asset caching, and disable server_tokens
- [ ] **Phase 6: Docker Compose Security & Services** - Drop capabilities, block privilege escalation, set resource limits, add scheduler service, and add queue worker health check
- [ ] **Phase 7: OPcache & Performance** - Configure OPcache with production settings and PHP 8.3 tracing JIT for 30-50% throughput improvement
- [ ] **Phase 8: CI Pipeline** - Validate Docker builds, enforce code style with Pint, and audit dependencies for known vulnerabilities

## Phase Details

### Phase 4: Dockerfile & Build Context

**Goal**: The Docker image is built from a clean context with no secrets, runs as non-root, and has OPcache extension pre-installed
**Depends on**: Phase 3 (v1.0 milestone complete)
**Requirements**: DOCKER-01, DOCKER-02, DOCKER-03, DOCKER-04, DOCKER-05, DOCKER-06
**Success Criteria** (what must be TRUE):

  1. Building the Docker image excludes `.env`, `.git/`, `vendor/`, `node_modules/`, `docker/`, `tests/`, and other non-production files from the build context
  2. The production container runs processes as `www-data` (non-root) after the entrypoint completes privilege-sensitive setup
  3. The existing `docker/prod/entrypoint.sh` executes on container start, handling storage permissions and gosu privilege drop
  4. The OPcache PHP extension is available in the production container (verified via `php -m | grep opcache`)
  5. Production `docker-compose.prod.yml` bakes application code into the image via multi-stage build with no host bind-mount of the application directory

**Plans**: 1/2 plans executed

Plans:

- [x] 04-01-PLAN.md
- [ ] 04-02-PLAN.md
- [x] 04-01: Dockerfile hardening — .dockerignore, gosu install, OPcache extension, layer caching, ENTRYPOINT wiring, Dockerfile rename
- [ ] 04-02: Docker Compose prod updates — remove app/queue bind-mounts, fix Dockerfile references in both compose files

### Phase 5: Nginx Hardening

**Goal**: Nginx serves responses with security headers, compresses text assets, caches static files, and hides version information
**Depends on**: Phase 4
**Requirements**: NGINX-01, NGINX-02, NGINX-03, NGINX-04, NGINX-05, NGINX-06
**Success Criteria** (what must be TRUE):

  1. HTTP responses include a `Content-Security-Policy` header with `script-src 'self' 'unsafe-inline' 'unsafe-eval'` that does not break Livewire 4 or Alpine.js interactivity
  2. HTTP responses include `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, and `Referrer-Policy: strict-origin-when-cross-origin` headers
  3. Text/html, text/css, application/javascript, application/json, and image/svg+xml responses are gzip-compressed (verified by checking `Content-Encoding: gzip` on a request)
  4. Vite-hashed static assets (JS, CSS, images) are served with `Cache-Control` headers containing a 1-year max-age and `immutable` directive
  5. Nginx does not expose version information in response headers or error pages (`server_tokens` is off)

**Plans**: TBD

Plans:

- [ ] 05-01: TBD
- [ ] 05-02: TBD

### Phase 6: Docker Compose Security & Services

**Goal**: All containers run with minimal Linux privileges, resource limits prevent OOM runaway, a scheduler service runs Laravel scheduled tasks, and the queue worker has crash detection
**Depends on**: Phase 4
**Requirements**: COMPOSE-01, COMPOSE-02, COMPOSE-03, COMPOSE-04, COMPOSE-05
**Success Criteria** (what must be TRUE):

  1. All containers have `cap_drop: [ALL]` and `security_opt: [no-new-privileges]` set in docker-compose configuration
  2. Resource limits are enforced for all services: app (512M/1.0 CPU), nginx (128M/0.5 CPU), queue (512M/1.0 CPU), scheduler (256M/0.5 CPU), mysql (1G/1.0 CPU)
  3. A `scheduler` service exists that runs `php artisan schedule:work` using the same Docker image as the app
  4. The `queue` service has a health check that monitors worker responsiveness and reports unhealthy if the worker stops processing

**Plans**: TBD

Plans:

- [ ] 06-01: TBD
- [ ] 06-02: TBD

### Phase 7: OPcache & Performance

**Goal**: PHP-FPM serves requests with OPcache bytecode caching and JIT compilation active, delivering 30-50% throughput improvement
**Depends on**: Phase 4, Phase 6
**Requirements**: OPCACHE-01, OPCACHE-02
**Success Criteria** (what must be TRUE):

  1. An OPcache configuration file (`docker/php/conf.d/opcache.ini`) is loaded with `validate_timestamps=0`, `max_accelerated_files=10000`, and `memory_consumption=128`
  2. OPcache JIT is configured with `opcache.jit=1255` (PHP 8.3 tracing JIT) and `opcache.jit_buffer_size=128M`

**Plans**: TBD

Plans:

- [ ] 07-01: TBD

### Phase 8: CI Pipeline

**Goal**: The CI pipeline validates Docker builds, enforces code style, and audits dependencies before code merges
**Depends on**: Phase 4, Phase 5, Phase 6, Phase 7
**Requirements**: CI-01, CI-02, CI-03
**Success Criteria** (what must be TRUE):

  1. A push or PR triggers `vendor/bin/pint --test` in CI and the build fails if any PHP file violates the code style rules
  2. A push or PR triggers `composer audit` in CI and the build fails if any dependency has a known security vulnerability
  3. A push or PR triggers `docker build` in CI and the build fails if the Dockerfile has errors or the image does not build cleanly

**Plans**: TBD

Plans:

- [ ] 08-01: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 4 -> 5 -> 6 -> 7 -> 8

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Central Admin Authorization | v1.0 | 2/2 | Done | 2026-06-26 |
| 2. Module Upload Security | v1.0 | 2/2 | Done | 2026-06-26 |
| 3. Module State Persistence | v1.0 | 2/2 | Done | 2026-06-27 |
| 4. Dockerfile & Build Context | v1.1 | 1/2 | In Progress|  |
| 5. Nginx Hardening | v1.1 | 0/2 | Not started | - |
| 6. Docker Compose Security & Services | v1.1 | 0/2 | Not started | - |
| 7. OPcache & Performance | v1.1 | 0/1 | Not started | - |
| 8. CI Pipeline | v1.1 | 0/1 | Not started | - |
