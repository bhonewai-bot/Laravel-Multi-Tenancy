# Phase 6: Docker Compose Security & Services - Context

**Gathered:** 2026-06-29
**Status:** Ready for planning

<domain>
## Phase Boundary

Lock down all production containers with Linux security constraints (cap_drop, no-new-privileges), enforce memory/CPU resource limits, add a scheduler service for Laravel scheduled tasks, and give the queue worker a health check for crash detection.

Requirements in scope: COMPOSE-01, COMPOSE-02, COMPOSE-03, COMPOSE-04, COMPOSE-05.

</domain>

<decisions>
## Implementation Decisions

### Security Hardening Scope (COMPOSE-01, COMPOSE-02)
- **D-01:** `cap_drop: [ALL]` and `security_opt: [no-new-privileges]` applied to prod compose only — dev compose stays unrestricted to avoid interfering with debugging tools (xdebug, strace, process inspection)
- **D-02:** All prod services get the security constraints: app, nginx, queue, scheduler, mysql

### Scheduler Service (COMPOSE-04)
- **D-03:** New `scheduler` service using the same Docker image as `app`
- **D-04:** Same `env_file` and `depends_on` as the `app` service — scheduler needs DB access for scheduled tasks
- **D-05:** Command: `php artisan schedule:work` (Laravel's built-in scheduler loop)
- **D-06:** `restart: unless-stopped` for resilience

### Queue Health Check (COMPOSE-05)
- **D-07:** Process-based health check: `pgrep -f 'queue:work'` — simple, catches crashes
- **D-08:** Interval: 30s, timeout: 5s, retries: 3, start_period: 10s

### Resource Limits (COMPOSE-03)
- **D-09:** Use ROADMAP defaults:
  - app: 512M memory, 1.0 CPU
  - nginx: 128M memory, 0.5 CPU
  - queue: 512M memory, 1.0 CPU
  - scheduler: 256M memory, 0.5 CPU
  - mysql: 1G memory, 1.0 CPU

### Claude's Discretion
- Whether to add resource limits to dev compose (probably not — dev needs flexibility)
- Exact health check timing parameters
- Whether mysql needs cap_drop (it runs as mysql user internally)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — COMPOSE-01 through COMPOSE-05 definitions and acceptance criteria

### Docker Compose
- `docker-compose.prod.yml` — Production compose (target for all changes)
- `docker-compose.yml` — Dev compose (no security changes per D-01)
- `Dockerfile` — Production image used by app, queue, and scheduler services

### Prior Decisions
- `.planning/phases/04-dockerfile-build-context/04-CONTEXT.md` — Phase 4 decisions (bind-mount removal, entrypoint wiring)
- `.planning/phases/05-nginx-hardening/05-CONTEXT.md` — Phase 5 decisions (nginx.conf mount)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `docker-compose.prod.yml` — Already has app, nginx, queue, mysql services. Just needs security constraints and resource limits added.
- Docker image from Phase 4 — Multi-stage build with gosu, OPcache, entrypoint. Ready for scheduler service.

### Established Patterns
- `depends_on` with `condition: service_healthy` — Already used for mysql healthcheck
- `restart: unless-stopped` — Already used on all services
- `env_file: - .env` — Already used on app and queue services

### Integration Points
- Scheduler service needs same image, env_file, and mysql dependency as app
- Queue health check goes in the existing queue service definition
- cap_drop/security_opt added to each service individually (not at top level)

</code_context>

<specifics>
## Specific Ideas

- Scheduler uses `php artisan schedule:work` (runs every minute, checks for due commands)
- Health check uses `pgrep` to check if the queue:work process is alive
- Security hardening is prod-only to keep dev experience smooth

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 6-Docker Compose Security & Services*
*Context gathered: 2026-06-29*
