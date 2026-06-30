# Phase 4: Dockerfile & Build Context - Context

**Gathered:** 2026-06-29
**Status:** Ready for planning

<domain>
## Phase Boundary

Build a secure Docker image from a clean context — `.dockerignore` excludes secrets and dev files, the container runs as non-root via entrypoint gosu drop, the entrypoint is wired into the Dockerfile, OPcache extension is pre-installed, and production compose bakes code into the image with no host bind-mount.

Requirements in scope: DOCKER-01, DOCKER-02, DOCKER-03, DOCKER-04, DOCKER-05, DOCKER-06.

</domain>

<decisions>
## Implementation Decisions

### Build Context Strategy
- **D-01:** `.dockerignore` excludes `.env`, `.env.*`, `.git/`, `.github/`, `vendor/`, `node_modules/`, `docker/`, `tests/`, `Modules/`, `.planning/`, `.claude/`, `.editorconfig`, `*.md`
- **D-02:** The `docker/` directory is excluded entirely — nginx/php configs are bind-mounted at runtime, not baked into the image
- **D-03:** `Modules/` excluded — tenant modules are installed at runtime via ZIP upload, not build-time
- **D-04:** Keep the `rm -rf node_modules .git tests` in production stage as defense-in-depth (belt + suspenders alongside `.dockerignore`)

### Composer/Vendor Strategy
- **D-05:** Optimize Dockerfile layer caching — `COPY composer.json composer.lock` first, then `composer install`, then `COPY . .` (dependencies cached until lock file changes)
- **D-06:** Same pattern for npm — `COPY package*.json` first, then `npm install`, then `COPY . .`

### Entrypoint Integration
- **D-07:** Wire `docker/prod/entrypoint.sh` as `ENTRYPOINT` in the Dockerfile (DOCKER-03)
- **D-08:** No `USER` directive — the entrypoint handles privilege drop via `gosu` to `www-data`. A `USER` directive would break the entrypoint's root-level `chown` operations (DOCKER-02 satisfied via entrypoint)

### Docker Compose Prod Changes
- **D-09:** Update `docker-compose.prod.yml` in Phase 4 — remove host bind-mount `./:/var/www` from `app` and `queue` services (DOCKER-05)
- **D-10:** Rename `DockerFile` to `Dockerfile` and update both `docker-compose.yml` and `docker-compose.prod.yml` to reference `Dockerfile` (DOCKER-06)

### Claude's Discretion
- OPcache extension install method (`docker-php-ext-install opcache`) — standard approach, no user input needed
- `.dockerignore` exact file list — user chose "standard exclusions", Claude picks the specific files

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — DOCKER-01 through DOCKER-06 definitions and acceptance criteria

### Docker Infrastructure
- `Dockerfile` — Current Dockerfile (named `DockerFile`, needs rename + modifications)
- `docker-compose.prod.yml` — Production compose (bind-mount to remove, DockerFile reference to fix)
- `docker-compose.yml` — Dev compose (DockerFile reference to fix)
- `docker/prod/entrypoint.sh` — Entrypoint script (to be wired as ENTRYPOINT)

### Codebase Maps
- `.planning/codebase/STACK.md` — Technology stack and platform requirements
- `.planning/codebase/ARCHITECTURE.md` — System architecture and component responsibilities

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `docker/prod/entrypoint.sh` — Already handles storage permissions, SQLite paths, and gosu privilege drop. Just needs ENTRYPOINT wiring.
- Multi-stage build pattern — Already in place (base → builder → production). Just needs layer optimization.

### Established Patterns
- `php:8.3-fpm` base image — Standard PHP-FPM, extensions installed via `docker-php-ext-configure` / `docker-php-ext-install`
- Composer 2 via `COPY --from=composer:2` — Already in place
- Node.js from apt — Already installed in base stage for npm builds

### Integration Points
- `docker-compose.prod.yml` `app` service — consumes the built image, needs bind-mount removal
- `docker-compose.prod.yml` `queue` service — same image, same bind-mount removal needed
- `docker-compose.yml` dev services — reference fix only (DockerFile → Dockerfile)

</code_context>

<specifics>
## Specific Ideas

- Entrypoint script is already well-structured — wire it as-is, no modifications needed
- OPcache JIT stability note from STATE.md: `opcache.jit=1255` may need environment-specific validation; fallback to 1235 if segfaults occur (relevant to Phase 7, not Phase 4)

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 4-Dockerfile & Build Context*
*Context gathered: 2026-06-29*
