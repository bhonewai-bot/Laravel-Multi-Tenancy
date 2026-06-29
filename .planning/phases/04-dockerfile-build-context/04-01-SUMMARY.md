---
phase: 04-dockerfile-build-context
plan: 01
subsystem: infra
tags: [docker, dockerfile, opcache, gosu, multi-stage-build, layer-caching]

# Dependency graph
requires: []
provides:
  - ".dockerignore with secret and dev artifact exclusions"
  - "Production Dockerfile with gosu, OPcache, layer-cached dependencies, and ENTRYPOINT"
  - "Entrypoint script with execute permission"
affects: [04-02, nginx-hardening, ci-pipeline]

# Tech tracking
tech-stack:
  added: [gosu, opcache]
  patterns: [dependency-first-layer-caching, entrypoint-gosu-privilege-drop]

key-files:
  created:
    - .dockerignore
  modified:
    - Dockerfile
    - docker/prod/entrypoint.sh

key-decisions:
  - "Used npm ci instead of npm install for reproducible builds from lockfile"
  - "No USER directive in Dockerfile — entrypoint handles privilege drop via gosu"
  - "Entrypoint uses absolute path /var/www/docker/prod/entrypoint.sh for WORKDIR safety"

patterns-established:
  - "Dependency-first layer caching: copy lockfiles first, install, then copy source"
  - "Entrypoint privilege drop: entrypoint runs as root for chown, then gosu to www-data"

requirements-completed: [DOCKER-01, DOCKER-02, DOCKER-03, DOCKER-04, DOCKER-06]

# Metrics
duration: 2min
completed: 2026-06-29
status: complete
---

# Phase 4 Plan 1: Dockerfile & Build Context Summary

**Hardened production Dockerfile with .dockerignore, gosu, OPcache, dependency-first layer caching, and ENTRYPOINT wiring**

## Performance

- **Duration:** 2 min
- **Started:** 2026-06-29T06:34:49Z
- **Completed:** 2026-06-29T06:37:19Z
- **Tasks:** 2 (plus 1 auto-fix commit)
- **Files modified:** 3

## Accomplishments

- Created `.dockerignore` excluding `.env`, `.git/`, `vendor/`, `node_modules/`, `docker/`, `tests/`, `Modules/`, `.planning/`, `.claude/`, `.editorconfig`, `*.md` — preventing secrets and dev artifacts from reaching the Docker build context
- Rewrote Dockerfile with gosu for entrypoint privilege drop, OPcache extension, and optimized dependency-first layer caching using `npm ci`
- Wired `ENTRYPOINT ["/var/www/docker/prod/entrypoint.sh"]` and `CMD ["php-fpm"]` in the production stage
- Set execute permission on `docker/prod/entrypoint.sh` (100644 -> 100755)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create .dockerignore and rename Dockerfile** - `7782ca3` (chore)
2. **Task 2: Rewrite Dockerfile with layer caching, gosu, OPcache, and ENTRYPOINT** - `a8bf6d6` (feat)
3. **Auto-fix: Set execute permission on entrypoint.sh** - `bc7fac7` (fix)

## Files Created/Modified

- `.dockerignore` - Build context exclusion rules (12 patterns per D-01/D-02/D-03)
- `Dockerfile` - Renamed from `DockerFile`, rewritten with gosu, OPcache, layer caching, ENTRYPOINT/CMD
- `docker/prod/entrypoint.sh` - Execute permission set in git index (100755)

## Decisions Made

- Used `npm ci` instead of `npm install` for reproducible builds that respect `package-lock.json` exactly
- No `USER` directive in Dockerfile — entrypoint runs as root for `chown`/`chmod` on storage dirs, then drops to `www-data` via `gosu`
- Entrypoint uses absolute path `/var/www/docker/prod/entrypoint.sh` to avoid WORKDIR resolution issues (Pitfall 4)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Entrypoint execute permission reverted during Task 1 commit**
- **Found during:** Task 2 verification (overall verification step)
- **Issue:** `git update-index --chmod=+x` was run before Task 1's commit, but the permission change was not persisted through the commit. The `git ls-files -s` check after Task 2 showed 100644 instead of 100755.
- **Fix:** Re-applied `git update-index --chmod=+x docker/prod/entrypoint.sh` and staged the mode change explicitly via a dedicated commit.
- **Files modified:** `docker/prod/entrypoint.sh` (mode change only: 100644 -> 100755)
- **Verification:** `git ls-files -s docker/prod/entrypoint.sh` confirms 100755
- **Committed in:** `bc7fac7`

---

**Total deviations:** 1 auto-fixed (1 bug)
**Impact on plan:** Permission fix necessary for Docker ENTRYPOINT to execute. No scope creep.

## Issues Encountered

None beyond the permission revert documented above.

## User Setup Required

None - no external service configuration required.

## Known Stubs

None - all deliverables are fully functional.

## Threat Flags

No new security surface introduced beyond what the plan's threat model already covers:
- T-04-01: `.dockerignore` prevents secret leakage into build context (mitigated)
- T-04-02: Multi-stage build prevents builder artifacts in production image (mitigated)
- T-04-03: Entrypoint gosu drop prevents root execution at runtime (mitigated)

## Next Phase Readiness

- `Dockerfile` is production-ready and references `docker/prod/entrypoint.sh` via ENTRYPOINT
- Ready for Plan 02 (DOCKER-05: remove bind-mounts from `docker-compose.prod.yml`, update compose references)
- `.dockerignore` prevents secrets from reaching the build context on every future `docker build`

---
*Phase: 04-dockerfile-build-context*
*Completed: 2026-06-29*
