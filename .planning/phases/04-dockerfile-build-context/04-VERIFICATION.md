---
phase: 04-dockerfile-build-context
verified: 2026-06-29T07:46:00Z
status: human_needed
score: 9/11 must-haves verified
behavior_unverified: 2
re_verification: false
behavior_unverified_items:
  - truth: "Production container runs processes as www-data (non-root) after entrypoint completes privilege-sensitive setup"
    test: "Run `docker compose -f docker-compose.prod.yml up app` then `docker compose exec app whoami` — should show www-data"
    expected: "Process owner is www-data, not root"
    why_human: "Requires running the container; grep confirms gosu call in entrypoint but runtime privilege drop cannot be verified statically"
  - truth: "OPcache extension is available in the production container image"
    test: "Run `docker compose exec app php -m | grep opcache`"
    expected: "opcache appears in the loaded module list"
    why_human: "Requires running the container to verify php -m output; grep confirms opcache in docker-php-ext-install but runtime availability cannot be verified statically"
human_verification:
  - test: "Build the Docker image and run the production container, then execute `whoami` inside the container"
    expected: "Process owner is www-data, not root — confirming gosu privilege drop works"
    why_human: "Entrypoint runs as root for chown then drops to www-data via gosu; this runtime behavior requires a running container"
  - test: "Run `docker compose exec app php -m | grep opcache` against the production container"
    expected: "opcache appears in the loaded PHP module list"
    why_human: "OPcache is in the Dockerfile ext-install list but runtime availability needs container verification"
  - test: "Build the Docker image and verify .env is not included in the build context (e.g., `docker build --no-cache . 2>&1` and check no .env content leaks)"
    expected: "Build context transfer log does not include .env or other excluded files"
    why_human: "Docker daemon reads .dockerignore before sending context; actual exclusion behavior during build cannot be verified statically"
---

# Phase 04: Dockerfile & Build Context Verification Report

**Phase Goal:** The Docker image is built from a clean context with no secrets, runs as non-root, and has OPcache extension pre-installed
**Verified:** 2026-06-29T07:46:00Z
**Status:** human_needed
**Re-verification:** No -- initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Docker build context excludes .env, .git/, vendor/, node_modules/, docker/, tests/, Modules/, .planning/, .claude/, .editorconfig, *.md | VERIFIED | .dockerignore contains all 12 exclusion patterns, each confirmed via grep match |
| 2 | Production container runs processes as www-data (non-root) after entrypoint completes privilege-sensitive setup | PRESENT_BEHAVIOR_UNVERIFIED | Entrypoint calls `exec gosu www-data` (line 57), Dockerfile has no USER directive, gosu installed in base stage (line 16). Runtime behavior requires container verification |
| 3 | Entrypoint script executes on container start via ENTRYPOINT directive | VERIFIED | `ENTRYPOINT ["/var/www/docker/prod/entrypoint.sh"]` on Dockerfile line 49; file exists at path; git index 100755 |
| 4 | OPcache extension is available in the production container image | PRESENT_BEHAVIOR_UNVERIFIED | `opcache` in `docker-php-ext-install` list (Dockerfile line 26). Runtime availability requires `php -m` in container |
| 5 | Dockerfile is named Dockerfile (lowercase f) and tracked by git | VERIFIED | `git ls-files` tracks `Dockerfile`; macOS case-insensitive FS same inode for both spellings; zero `DockerFile` references in compose files |
| 6 | Layer caching is optimized -- dependency install layers are cached until lock files change | VERIFIED | `COPY composer.json composer.lock ./` (line 34) before `composer install` (line 35); `COPY package*.json ./` (line 37) before `npm ci` (line 38) |
| 7 | Production app service has no host bind-mount of the application directory | VERIFIED | docker-compose.prod.yml app service (lines 1-18) has no `volumes` key |
| 8 | Production queue service has no host bind-mount of the application directory | VERIFIED | docker-compose.prod.yml queue service (lines 35-61) has no `volumes` key |
| 9 | Both compose files reference Dockerfile (lowercase f), not DockerFile | VERIFIED | `dockerfile: Dockerfile` at docker-compose.prod.yml lines 5, 38 and docker-compose.yml lines 5, 92. Zero `DockerFile` occurrences |
| 10 | Nginx service retains its bind-mount (only reads public/ for static files, no secret exposure) | VERIFIED | docker-compose.prod.yml line 27: `- ./:/var/www` under nginx service volumes |
| 11 | docker-compose.prod.yml validates cleanly with docker compose config | VERIFIED | `docker compose -f docker-compose.prod.yml config` exit code 0 (env var warnings only, not errors) |

**Score:** 9/11 truths verified (2 present, behavior-unverified)

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `.dockerignore` | Build context exclusion rules (12 patterns) | VERIFIED | 13 lines, all 12 required exclusion patterns present |
| `Dockerfile` | Production-ready multi-stage Docker build | VERIFIED | 3 stages (base, builder, production); gosu, OPcache, layer caching, ENTRYPOINT/CMD all present; no USER directive; defense-in-depth rm -rf preserved |
| `docker/prod/entrypoint.sh` | Entrypoint with execute permission | VERIFIED | Git index 100755; gosu privilege drop to www-data; chown/chmod on storage dirs; 61 lines (exceeds min_lines: 55) |
| `docker-compose.prod.yml` | Production compose without app/queue bind-mounts | VERIFIED | App and queue services have no volumes key; nginx retains bind-mount; all references to Dockerfile (lowercase) |
| `docker-compose.yml` | Dev compose with corrected Dockerfile reference | VERIFIED | All references updated from DockerFile to Dockerfile; dev bind-mounts unchanged |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| Dockerfile | docker/prod/entrypoint.sh | ENTRYPOINT directive with absolute path /var/www/docker/prod/entrypoint.sh | VERIFIED | Dockerfile line 49: `ENTRYPOINT ["/var/www/docker/prod/entrypoint.sh"]`; file exists; WORKDIR /var/www resolves correctly |
| Dockerfile | .dockerignore | Docker daemon reads .dockerignore before sending context to builder | VERIFIED | .dockerignore exists at project root; excludes all 12 required patterns |
| docker-compose.prod.yml | Dockerfile | build.dockerfile reference | VERIFIED | Lines 5, 38: `dockerfile: Dockerfile` matches renamed file |
| docker-compose.yml | Dockerfile | build.dockerfile reference | VERIFIED | Lines 5, 92: `dockerfile: Dockerfile` matches renamed file |

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| (none) | - | - | - | No debt markers, stubs, or placeholder patterns found in any modified file |

### Human Verification Required

### 1. Container runs as www-data (non-root)

**Test:** Build the production image and start the app container, then run `whoami` inside
**Expected:** Output is `www-data`, confirming gosu privilege drop from root
**Why human:** Entrypoint executes as root for chown operations, then calls `exec gosu www-data` -- this runtime state transition cannot be verified statically

### 2. OPcache extension is loaded

**Test:** Run `docker compose exec app php -m | grep opcache` against the running production container
**Expected:** `opcache` appears in the PHP module list
**Why human:** The Dockerfile installs opcache via docker-php-ext-install, but actual module loading requires container runtime verification

### 3. Build context excludes secrets

**Test:** Build the Docker image with `docker build --no-cache .` and inspect the build context transfer log
**Expected:** No .env, .git/, vendor/, or other excluded files appear in the build context
**Why human:** Docker daemon applies .dockerignore before sending context; actual exclusion behavior during a real build cannot be verified with grep alone

### Gaps Summary

No implementation gaps found. All artifacts exist, are substantive, and are correctly wired. Two truths (non-root execution and OPcache availability) are classified as behavior-unverified because they assert runtime behavior that requires a running container to confirm. These are implementation-complete pending container-level verification.

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|----------|
| DOCKER-01 | 04-01 | .dockerignore excludes .env, .git/, vendor/, node_modules/, docker/, tests/, and other non-production files | SATISFIED | .dockerignore contains all 12 patterns including .env, .env.*, .git/, .github/, vendor/, node_modules/, docker/, tests/, Modules/, .planning/, .claude/, .editorconfig, *.md |
| DOCKER-02 | 04-01 | Production Dockerfile runs as non-root (www-data) using gosu privilege drop | SATISFIED | Entrypoint calls `exec gosu www-data` (line 57); no USER directive; gosu installed in apt-get (line 16) |
| DOCKER-03 | 04-01 | Entrypoint wired via ENTRYPOINT directive so it executes on container start | SATISFIED | `ENTRYPOINT ["/var/www/docker/prod/entrypoint.sh"]` on line 49; file at path with git 100755; CMD ["php-fpm"] on line 50 |
| DOCKER-04 | 04-01 | OPcache installed via docker-php-ext-install | SATISFIED | `opcache` in ext-install list (Dockerfile line 26) |
| DOCKER-05 | 04-02 | Production compose does not bind-mount application directory | SATISFIED | App and queue services in docker-compose.prod.yml have no volumes key; code baked via multi-stage build |
| DOCKER-06 | 04-01, 04-02 | Dockerfile named Dockerfile (not DockerFile) and all compose references updated | SATISFIED | Git tracks Dockerfile; all compose references use lowercase; macOS same-inode verified |

---

_Verified: 2026-06-29T07:46:00Z_
_Verifier: Claude (gsd-verifier)_
