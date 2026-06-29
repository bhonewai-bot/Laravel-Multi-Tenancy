# Phase 4: Dockerfile & Build Context - Research

**Researched:** 2026-06-29
**Domain:** Docker multi-stage builds, PHP-FPM container security, build context hygiene
**Confidence:** HIGH

## Summary

This phase secures the Docker build pipeline for TenantSmith. The current Dockerfile has a working multi-stage structure (base -> builder -> production) but lacks: (1) a `.dockerignore` file to exclude secrets and dev artifacts from the build context, (2) gosu installation needed by the entrypoint script, (3) OPcache extension installation, (4) optimized layer caching for composer/npm, (5) entrypoint wiring, and (6) consistent `Dockerfile` naming.

The production compose file (`docker-compose.prod.yml`) bind-mounts `./:/var/www` on both `app` and `queue` services, which overwrites the baked-in code with the host filesystem — defeating the multi-stage build. The entrypoint script (`docker/prod/entrypoint.sh`) is well-structured and handles gosu privilege drop, storage permissions, and SQLite setup, but is not wired as an `ENTRYPOINT` in the Dockerfile.

**Primary recommendation:** Rewrite the Dockerfile with layer-cached dependency installation, install gosu for the entrypoint, add OPcache, and wire the entrypoint. Remove bind-mounts from prod compose. All changes are tightly scoped to 6 files.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| DOCKER-01 | `.dockerignore` excludes `.env`, `.git/`, `vendor/`, `node_modules/`, `docker/`, `tests/`, and other non-production files | Standard Laravel .dockerignore patterns identified |
| DOCKER-02 | Production stage runs as non-root (`www-data`) after entrypoint completes root-level setup | Entrypoint already handles gosu drop; no `USER` directive needed (D-08) |
| DOCKER-03 | `docker/prod/entrypoint.sh` wired as `ENTRYPOINT` | Entrypoint script analyzed; gosu must be installed in image first |
| DOCKER-04 | OPcache PHP extension installed via `docker-php-ext-install opcache` | `opcache` is bundled with PHP 8.3, single `docker-php-ext-install` call |
| DOCKER-05 | Production compose does not bind-mount application directory | Bind-mount on `app` and `queue` services identified and will be removed |
| DOCKER-06 | Dockerfile named `Dockerfile` (not `DockerFile`), all compose references updated | Git tracks `DockerFile`; both compose files reference `DockerFile` |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Docker image build | Docker BuildKit | -- | Build-time concern; not runtime |
| Entrypoint / privilege drop | Container runtime | -- | Runs at container start, not at build time |
| OPcache extension | PHP-FPM runtime | Docker build | Installed at build time, active at runtime |
| .dockerignore | Docker Build context | -- | Evaluated by Docker daemon before build starts |
| Production compose | Docker Compose | -- | Orchestrates container runtime behavior |

## Existing Patterns

### What the codebase already provides

1. **Multi-stage build structure** — The current DockerFile uses `base -> builder -> production` stages. The pattern is sound; it needs optimization, not replacement.

2. **Entrypoint script** — `docker/prod/entrypoint.sh` is production-ready:
   - Creates storage/bootstrap directories
   - Sets `chown`/`chmod` for `www-data`
   - Handles SQLite database path setup
   - Falls through to `gosu` privilege drop for non-php-fpm commands
   - Delegates to `docker-php-entrypoint` for php-fpm (preserves official image behavior)

3. **Composer via COPY --from** — `COPY --from=composer:2 /usr/bin/composer /usr/bin/composer` is already in place.

4. **PHP extension pattern** — Uses `docker-php-ext-configure` + `docker-php-ext-install` for gd, pdo_mysql, bcmath, intl, zip. OPcache follows the same pattern.

### Gaps found in current Dockerfile

| Gap | Impact | Fix |
|-----|--------|-----|
| No `.dockerignore` | `.env` secrets, `.git/`, `node_modules/` sent to Docker daemon in build context | Create `.dockerignore` file |
| gosu not installed | Entrypoint will fail on `exec gosu` | Add `gosu` to apt-get install in base stage |
| OPcache not installed | DOCKER-04 fails | Add `opcache` to `docker-php-ext-install` |
| `COPY . .` before dependency install | Every code change invalidates dependency cache layers | Reorder: copy lockfiles first, install, then copy source |
| No `ENTRYPOINT` directive | Entrypoint script never runs | Add `ENTRYPOINT ["docker/prod/entrypoint.sh"]` |
| `CMD ["php-fpm"]` without entrypoint | Works but bypasses entrypoint setup | Move to `CMD` after `ENTRYPOINT` |
| `DockerFile` name (capital F) | Works on macOS, works on Linux, but non-standard | Rename to `Dockerfile` |

## Standard Stack

### Core

| Component | Version | Purpose | Why Standard |
|-----------|---------|---------|--------------|
| `php:8.3-fpm` | 8.3 (Debian Bookworm) | Base image for PHP-FPM runtime | Official PHP image; includes FPM, apt, docker-php-ext-install helpers |
| `composer:2` | Latest 2.x | Composer binary (COPY --from) | Official Composer image; no runtime dependency on the image itself |
| gosu | Debian Bookworm apt | Privilege drop from root to www-data | Used by official PostgreSQL, Redis, MongoDB images; simple, battle-tested |
| OPcache | Bundled with PHP 8.3 | Bytecode caching extension | Ships with PHP; just needs `docker-php-ext-install` to compile and enable |

### Supporting

| Component | Purpose | When to Use |
|-----------|---------|-------------|
| `.dockerignore` | Excludes files from build context | Always — prevents secrets and bloat from reaching the image |
| `docker-php-ext-install` | Installs PHP extensions in official Docker images | For any PHP extension not enabled by default |

## Package Legitimacy Audit

No new external packages are installed in this phase. All changes use:
- Official Docker base images (`php:8.3-fpm`, `composer:2`)
- Debian Bookworm system packages (`gosu` via apt)
- PHP bundled extensions (`opcache` via `docker-php-ext-install`)

No package legitimacy concerns.

## Technical Approach: Per-Requirement

### DOCKER-01: .dockerignore

**Approach:** Create `.dockerignore` at project root following locked decisions D-01 through D-04.

The exact exclusion list from CONTEXT.md D-01:
```
.env
.env.*
.git/
.github/
vendor/
node_modules/
docker/
tests/
Modules/
.planning/
.claude/
.editorconfig
*.md
```

**Why this list:**
- `.env`, `.env.*` — secrets must never reach the image. `env_file` in compose injects them at runtime.
- `.git/` — typically 50-200MB; irrelevant for production.
- `vendor/` — installed fresh via `composer install` in the builder stage.
- `node_modules/` — installed fresh via `npm install` in the builder stage; also removed in production stage per D-04.
- `docker/` — nginx/php configs are bind-mounted at runtime, not baked into the image (D-02).
- `Modules/` — tenant modules installed at runtime via ZIP upload (D-03).
- `tests/`, `.planning/`, `.claude/` — development artifacts only.
- `*.md` — documentation files not needed in production image.
- `.editorconfig` — editor config, not needed.

**Defense in depth:** D-04 requires keeping `rm -rf node_modules .git tests` in the production stage. This catches anything that slips through `.dockerignore`.

**Pitfall:** The `.dockerignore` must exist BEFORE the `docker build` command reads the context. Docker reads it once at context initialization.

### DOCKER-02: Non-root execution

**Approach:** Entrypoint handles privilege drop via `gosu` — no `USER` directive in Dockerfile (D-08).

The entrypoint script starts as root (UID 0), performs `chown`/`chmod` on storage directories, then calls `exec gosu www-data "$@"` to drop to the `www-data` user before running the CMD.

**Critical dependency:** `gosu` must be installed in the image. The current Dockerfile does NOT install it. The apt package name is `gosu` and it is available in Debian Bookworm repositories.

**Why no `USER` directive:**
- A `USER www-data` directive would prevent the entrypoint from running `chown` (requires root).
- The entrypoint needs root to fix storage permissions on first boot.
- After the entrypoint completes, `gosu` drops privileges permanently.
- This is the same pattern used by official PostgreSQL and MySQL Docker images.

**Verification:** `docker run --rm <image> whoami` should show `www-data` (not `root`) after entrypoint execution.

### DOCKER-03: Entrypoint wiring

**Approach:** Add `ENTRYPOINT` and `CMD` directives to the production stage.

```dockerfile
ENTRYPOINT ["docker/prod/entrypoint.sh"]
CMD ["php-fpm"]
```

**How ENTRYPOINT + CMD interact:**
1. `ENTRYPOINT` defines the executable that always runs.
2. `CMD` provides default arguments to the ENTRYPOINT.
3. Result: `docker/prod/entrypoint.sh php-fpm`
4. Override: `docker run <image> artisan migrate` runs `docker/prod/entrypoint.sh artisan migrate`
5. The entrypoint script checks `$1 = "php-fpm"` and delegates to `docker-php-entrypoint`.

**Entrypoint path:** The WORKDIR is `/var/www` and the entrypoint is at `docker/prod/entrypoint.sh` relative to the project root, which becomes `/var/www/docker/prod/entrypoint.sh` in the container. The `ENTRYPOINT` directive should use the absolute path or relative to WORKDIR.

**Correct form:**
```dockerfile
ENTRYPOINT ["/var/www/docker/prod/entrypoint.sh"]
```

**Pitfall:** The entrypoint script must have execute permission. Verify with `ls -la docker/prod/entrypoint.sh` — current permissions show `-rw-r--r--` (644). It needs `chmod +x` either in the Dockerfile or in git.

### DOCKER-04: OPcache extension

**Approach:** Add `opcache` to the existing `docker-php-ext-install` command in the base stage.

```dockerfile
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    bcmath \
    intl \
    zip \
    gd \
    opcache
```

**Why this works:**
- OPcache is bundled with PHP 8.3 source code — no additional downloads needed.
- `docker-php-ext-install` compiles and enables it.
- After installation, `php -m | grep opcache` returns `Zend OPcache`.
- OPcache configuration (the `.ini` file) is Phase 7 scope, not Phase 4.

**Verification:** `docker run --rm <image> php -m | grep opcache` should output `Zend OPcache`.

### DOCKER-05: Bind-mount removal

**Approach:** Remove `volumes: - ./:/var/www` from `app` and `queue` services in `docker-compose.prod.yml` (D-09).

**Current state:**
- `app` service: `volumes: - ./:/var/www` (line 15)
- `queue` service: `volumes: - ./:/var/www` (line 60)
- `nginx` service: `volumes: - ./:/var/www` (line 29) — this one is SEPARATE; nginx needs access to `public/` for static files

**The nginx bind-mount question:** The `nginx` service also bind-mounts `./:/var/www`. After this change, nginx cannot read Laravel's `public/` directory from the host. Two options:
1. Use a shared Docker volume between `app` and `nginx` to share `/var/www/public`.
2. Keep the nginx bind-mount (it only reads static files, no secrets risk).

**Recommendation:** For Phase 4, remove bind-mounts from `app` and `queue` only. The nginx bind-mount is a separate concern that will be addressed when nginx config is hardened in Phase 5. The nginx service only reads from `public/` (no secret exposure risk).

### DOCKER-06: Dockerfile naming

**Approach:** Rename `DockerFile` to `Dockerfile` and update references in both compose files.

**Current state:**
- Git tracks: `DockerFile` (capital F)
- `docker-compose.yml` references: `dockerfile: DockerFile`
- `docker-compose.prod.yml` references: `dockerfile: DockerFile`
- macOS: case-insensitive filesystem — both names resolve to same file
- Linux CI: case-sensitive — `DockerFile` works but is non-standard

**Why it matters:** The Docker convention is `Dockerfile` (lowercase f). Docker's auto-detection looks for `Dockerfile` first. Linux CI systems and container registries expect `Dockerfile`. The CONTEXT.md decision D-10 mandates the rename.

**Git rename:** `git mv DockerFile Dockerfile` — git handles case-only renames with `git mv`.

## Common Pitfalls

### Pitfall 1: Entrypoint execute permission
**What goes wrong:** Container fails to start with "permission denied" on entrypoint script.
**Why it happens:** The entrypoint file is committed as 644 (no execute bit). Docker runs it as an executable.
**How to avoid:** Add `RUN chmod +x /var/www/docker/prod/entrypoint.sh` in the Dockerfile, or set the execute bit in git with `git update-index --chmod=+x docker/prod/entrypoint.sh`.
**Warning signs:** `docker run` exits immediately with code 126 or "permission denied".

### Pitfall 2: Composer/NPM cache invalidation
**What goes wrong:** Every code change triggers a full `composer install` and `npm install`.
**Why it happens:** `COPY . .` before dependency install means the entire layer is invalidated.
**How to avoid:** Copy `composer.json`/`composer.lock` and `package.json`/`package-lock.json` first, install dependencies, then `COPY . .`.
**Warning signs:** Build times of 2-5 minutes instead of 10-20 seconds for code-only changes.

### Pitfall 3: .dockerignore not excluding storage contents
**What goes wrong:** Build context includes local storage logs/cache/sessions.
**Why it happens:** If `.dockerignore` does not exclude `storage/` contents, local dev artifacts get copied in.
**How to avoid:** The entrypoint script creates these directories at runtime. Exclude `storage/logs/*`, `storage/framework/cache/*`, `storage/framework/sessions/*`, `storage/framework/views/*` from the build context but keep the `storage/` directory structure (use `!storage/.gitkeep` if needed).
**Note:** The current Dockerfile copies everything including storage. After adding `.dockerignore`, this is handled.

### Pitfall 4: ENTRYPOINT path wrong after WORKDIR change
**What goes wrong:** Container start fails with "no such file or directory" for the entrypoint.
**Why it happens:** The ENTRYPOINT path is relative and WORKDIR changes the resolution.
**How to avoid:** Use absolute path in ENTRYPOINT: `/var/www/docker/prod/entrypoint.sh`.

### Pitfall 5: Removing bind-mount breaks nginx static file serving
**What goes wrong:** After removing bind-mount from app/queue, nginx still mounts `./:/var/www` — this is fine for now but creates an inconsistency.
**Why it happens:** nginx reads static assets from `public/` which is in the host directory.
**How to avoid:** Phase 4 does NOT remove the nginx bind-mount. That is Phase 5 territory (when nginx config is hardened and can use a shared volume or bake files into the nginx image).

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Shell-based / Docker CLI (no PHPUnit — these are infrastructure tests) |
| Config file | None — manual verification commands |
| Quick run command | `docker build -t tenantsmith:test . && docker run --rm tenantsmith:test php -m \| grep opcache` |
| Full suite command | Full build + compose up + smoke test sequence below |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DOCKER-01 | `.dockerignore` excludes secrets from context | manual-only | `docker build . 2>&1` — inspect context size; `docker history` — verify no .env in layers | N/A |
| DOCKER-02 | Container runs as non-root | shell | `docker run --rm <image> whoami` should output `www-data` | N/A |
| DOCKER-03 | Entrypoint executes on container start | shell | `docker run --rm <image> php -r 'echo "ok";'` should succeed (entrypoint runs first) | N/A |
| DOCKER-04 | OPcache extension available | shell | `docker run --rm <image> php -m \| grep opcache` should output `Zend OPcache` | N/A |
| DOCKER-05 | No bind-mount in prod compose | manual-only | Inspect `docker-compose.prod.yml` — no `volumes: - ./:/var/www` on app/queue | N/A |
| DOCKER-06 | File named `Dockerfile` | shell | `test -f Dockerfile && echo "OK"` — git tracks `Dockerfile` not `DockerFile` | N/A |

### Sampling Rate
- **Per task commit:** Quick validation commands per requirement above
- **Per wave merge:** Full build + run + OPcache check
- **Phase gate:** `docker compose -f docker-compose.prod.yml config` validates the compose file; `docker build -t tenantsmith:test .` validates the Dockerfile

### Wave 0 Gaps
- [ ] No existing Docker build test infrastructure — verification is shell-command-based (acceptable for infrastructure phase)
- [ ] Entrypoint execute permission not set — must be fixed in this phase

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Docker | All Dockerfile/compose changes | -- | -- | Required — no fallback |
| Docker Compose | `docker-compose.prod.yml` validation | -- | -- | Required (compose v2 bundled with Docker Desktop) |
| git | `git mv` for Dockerfile rename | -- | -- | Required — no fallback |

**Note:** Environment availability cannot be probed in this research session (Docker may or may not be installed on the dev machine). The planner should assume Docker is available (project has Docker infrastructure) and add a `checkpoint:verify-docker` early in the plan if uncertainty exists.

**Missing dependencies with no fallback:**
- Docker and Docker Compose are required for this phase. There is no alternative approach.

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | N/A — not a Phase 4 concern |
| V3 Session Management | no | N/A |
| V4 Access Control | no | N/A |
| V5 Input Validation | no | N/A |
| V6 Cryptography | no | N/A |
| V14 Configuration | yes | `.dockerignore` prevents secret leakage into image layers; non-root execution via gosu |

### Known Threat Patterns for Docker Builds

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Secret leakage via build context | Information Disclosure | `.dockerignore` excludes `.env`, `.env.*` |
| Secret leakage via image layers | Information Disclosure | Multi-stage build; secrets only in builder stage, final stage copies artifacts |
| Container runs as root | Elevation of Privilege | Entrypoint gosu drop to `www-data` |
| Build context includes `.git/` (metadata, history) | Information Disclosure | `.dockerignore` excludes `.git/` |

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `gosu` is available via `apt-get install -y gosu` on `php:8.3-fpm` (Debian Bookworm) | DOCKER-02 | Entrypoint will fail; need to download gosu binary manually from GitHub releases |
| A2 | `docker-php-ext-install opcache` works without additional configure step on PHP 8.3 | DOCKER-04 | Low risk — OPcache is bundled; but may need `docker-php-ext-configure opcache` if build flags differ |
| A3 | Entrypoint execute permission can be set via `chmod +x` in Dockerfile or git index update | DOCKER-03 | If neither works on CI, entrypoint will fail to start |
| A4 | nginx bind-mount removal is NOT in scope for Phase 4 | DOCKER-05 | If user expects nginx volume changes in Phase 4, scope expands |
| A5 | `storage/` directory structure is preserved in the image (not excluded by .dockerignore) even though contents are excluded | DOCKER-01 | If storage dir is excluded entirely, `mkdir -p` in entrypoint handles it anyway |

## Sources

### Primary (HIGH confidence)
- [VERIFIED: codebase] Current `DockerFile` analyzed — multi-stage build structure, extension installation pattern, layer ordering
- [VERIFIED: codebase] `docker/prod/entrypoint.sh` analyzed — gosu usage, permission handling, php-fpm delegation
- [VERIFIED: codebase] `docker-compose.prod.yml` analyzed — bind-mount locations, service definitions
- [VERIFIED: codebase] `docker-compose.yml` analyzed — dev compose DockerFile reference
- [VERIFIED: codebase] `.planning/phases/04-dockerfile-build-context/04-CONTEXT.md` — locked decisions D-01 through D-10

### Secondary (MEDIUM confidence)
- [ASSUMED] `gosu` available in Debian Bookworm apt repos — standard for official Docker images but not verified via `apt-cache` in this session
- [ASSUMED] `docker-php-ext-install opcache` is sufficient without `--configure` step — standard for bundled extensions

### Tertiary (LOW confidence)
- None — all findings grounded in codebase analysis or established Docker patterns

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all components are official Docker images or bundled PHP extensions, verified from codebase
- Architecture: HIGH — current multi-stage structure analyzed directly from DockerFile
- Pitfalls: HIGH — all pitfalls identified from reading current code and entrypoint script
- Environment availability: MEDIUM — Docker assumed available but not probed

**Research date:** 2026-06-29
**Valid until:** 2026-07-29 (stable — Dockerfile patterns change slowly)
