# Phase 7: OPcache & Performance - Context

**Gathered:** 2026-06-29
**Status:** Ready for planning

<domain>
## Phase Boundary

Configure OPcache with production settings and PHP 8.3 tracing JIT for 30-50% throughput improvement. Create an OPcache ini file that gets mounted into the PHP-FPM container.

Requirements in scope: OPCACHE-01, OPCACHE-02.

</domain>

<decisions>
## Implementation Decisions

### OPcache Settings (OPCACHE-01)
- **D-01:** `validate_timestamps=0` — no file stat checks in production (requires container restart to pick up changes)
- **D-02:** `max_accelerated_files=10000` — sufficient for a Laravel application
- **D-03:** `memory_consumption=128` — 128MB for OPcache storage

### JIT Configuration (OPCACHE-02)
- **D-04:** `opcache.jit=1255` — PHP 8.3 tracing JIT (aggressive optimization)
- **D-05:** `opcache.jit_buffer_size=128M` — 128MB JIT buffer
- **D-06:** Known caveat: opcache.jit=1255 may need environment-specific validation; fallback to 1235 if segfaults occur (from STATE.md)

### Deployment Strategy
- **D-07:** OPcache ini file created at `docker/php/conf.d/opcache.ini`
- **D-08:** Mounted into app container via volume in docker-compose.prod.yml: `./docker/php/conf.d/opcache.ini:/usr/local/etc/php/conf.d/opcache.ini:ro`
- **D-09:** Prod compose only — dev compose keeps default OPcache behavior (validate_timestamps=1 for hot-reload)

### Claude's Discretion
- Exact OPcache ini directive syntax — standard PHP documentation applies
- Whether to add opcache.enable=1 (enabled by default, explicit for clarity)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — OPCACHE-01 and OPCACHE-02 definitions

### Docker Infrastructure
- `docker-compose.prod.yml` — Production compose (needs OPcache volume mount)
- `Dockerfile` — Already has opcache extension installed (from Phase 4)

### Prior Decisions
- `.planning/phases/04-dockerfile-build-context/04-CONTEXT.md` — Phase 4 installed opcache extension via docker-php-ext-install

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- OPcache extension already installed in Dockerfile (Phase 4) — `docker-php-ext-install opcache`
- `docker/php/conf.d/` directory may not exist yet — needs creation

### Established Patterns
- Volume mounts for config files use `:ro` suffix (read-only)
- Prod compose uses `env_file` and `environment` overrides per service

### Integration Points
- `docker-compose.prod.yml` app service — needs OPcache ini volume mount
- OPcache is already loaded in the container (verified in Phase 4: `php -m | grep opcache` → `Zend OPcache`)

</code_context>

<specifics>
## Specific Ideas

- OPcache extension already installed — just need runtime configuration via ini file
- validate_timestamps=0 means code changes require container restart (expected in production)

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 7-OPcache & Performance*
*Context gathered: 2026-06-29*
