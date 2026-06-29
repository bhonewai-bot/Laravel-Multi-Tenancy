# Phase 4: Dockerfile & Build Context - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-29
**Phase:** 4-Dockerfile & Build Context
**Areas discussed:** Build context strategy, Composer/vendor strategy, Entrypoint integration, Docker Compose prod changes

---

## Build Context Strategy

### Q1: Should the docker/ directory be excluded from the build context?

| Option | Description | Selected |
|--------|-------------|----------|
| Exclude entirely | docker/ stays out of the image. Nginx and PHP configs are bind-mounted at runtime. Cleaner image, smaller context. | ✓ |
| Include selectively | Keep docker/php/ in context (for OPcache ini in Phase 7), exclude docker/nginx/ and docker/prod/entrypoint.sh since those are bind-mounted or COPYed separately. | |
| Keep in context | Include everything. No harm, just extra context size. Simplest approach. | |

**User's choice:** Exclude entirely
**Notes:** Nginx and PHP configs are runtime concerns, not build-time.

### Q2: Should the Modules/ directory be in the build context?

| Option | Description | Selected |
|--------|-------------|----------|
| Exclude from image | Modules are installed per-tenant at runtime via ZIP upload. They don't need to be baked into the Docker image. | ✓ |
| Include in image | Bake current modules into the image. Simpler if you want the Product module available out of the box. | |

**User's choice:** Exclude from image
**Notes:** Modules are runtime-installable via ZIP, not build-time dependencies.

### Q3: Beyond standard exclusions, anything else to exclude?

| Option | Description | Selected |
|--------|-------------|----------|
| Standard exclusions only | .env, .env.*, .git/, .github/, vendor/, node_modules/, docker/, tests/, .planning/, .claude/, .editorconfig, *.md — clean and sufficient. | ✓ |
| Aggressive cleanup | Also exclude docs/, caddy/, phpunit.xml, vite.config.js, tailwind.config.js, postcss.config.js — anything not needed at runtime. Smaller image, but may break if any are referenced. | |

**User's choice:** Standard exclusions only
**Notes:** Keep it simple. Standard exclusions cover the security concerns.

### Q4: Keep the rm command as defense-in-depth?

| Option | Description | Selected |
|--------|-------------|----------|
| Keep rm as defense-in-depth | belt + suspenders. If .dockerignore is misconfigured, the rm still catches it. Only 1 line, no downside. | ✓ |
| Remove rm, trust .dockerignore | Cleaner Dockerfile. The .dockerignore is the canonical exclusion source. No redundant cleanup. | |

**User's choice:** Keep rm as defense-in-depth
**Notes:** Belt + suspenders approach. No downside to keeping it.

---

## Composer/Vendor Strategy

### Q1: Optimize with separate vendor-only copy layer?

| Option | Description | Selected |
|--------|-------------|----------|
| Copy composer files first | COPY composer.json composer.lock first, then composer install, then COPY rest. Dependencies cached until lock file changes. Standard Docker best practice. | ✓ |
| Keep current approach | COPY . . then composer install. Simple, but every code change triggers dependency resolution. Fine for small projects. | |

**User's choice:** Copy composer files first
**Notes:** Standard Docker layer caching optimization.

### Q2: Apply same optimization for npm?

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, same pattern for npm | COPY package*.json first, npm install, then COPY rest. Frontend deps cached until lock file changes. | ✓ |
| No, npm is fine as-is | npm install runs once in builder, gets copied. Less optimization needed since frontend builds are less frequent. | |

**User's choice:** Yes, same pattern for npm
**Notes:** Consistent approach for both PHP and JS dependency caching.

---

## Entrypoint Integration

### Q1: Wire entrypoint.sh as Dockerfile ENTRYPOINT?

| Option | Description | Selected |
|--------|-------------|----------|
| Wire as ENTRYPOINT | ENTRYPOINT ["docker/prod/entrypoint.sh"] — runs on every container start, handles perms, then execs the CMD (php-fpm). DOCKER-03 requirement. | ✓ |
| Wire with changes | Wire it but modify the script (e.g., add health checks, startup logging, or change the gosu behavior). | |

**User's choice:** Wire as ENTRYPOINT directly
**Notes:** Entrypoint is already well-structured. No modifications needed.

### Q2: Add USER directive or rely on gosu?

| Option | Description | Selected |
|--------|-------------|----------|
| Entrypoint handles it | The entrypoint already drops to www-data via gosu. A USER directive would break the entrypoint's root-level chown. Keep entrypoint as the authority. | ✓ |
| Add USER directive too | USER www-data after entrypoint COPY. Belt + suspenders, but may conflict with gosu if entrypoint needs root for chown. | |

**User's choice:** Entrypoint handles it
**Notes:** gosu handles the privilege drop. USER directive would break root-level chown in entrypoint.

---

## Docker Compose Prod Changes

### Q1: Update docker-compose.prod.yml in Phase 4?

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, update in Phase 4 | DOCKER-05 is explicitly in Phase 4 scope. The Dockerfile change (bake code) and compose change (remove bind-mount) are coupled — do them together. | ✓ |
| Defer to Phase 6 | Phase 6 is 'Docker Compose Security & Services'. Keep Phase 4 focused on Dockerfile only. Risk: DOCKER-05 is listed under Phase 4 requirements. | |

**User's choice:** Yes, update in Phase 4
**Notes:** DOCKER-05 is explicitly in Phase 4 requirements. Dockerfile and compose changes are coupled.

### Q2: Update both compose files for DockerFile → Dockerfile rename?

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, update both | Rename DockerFile → Dockerfile, update both docker-compose.yml and docker-compose.prod.yml. Consistent naming, fixes Linux CI builds. | ✓ |
| Only prod compose | Only update docker-compose.prod.yml. Dev compose is local-only and doesn't matter for CI. | |

**User's choice:** Yes, update both
**Notes:** Consistent naming across all compose files.

---

## Claude's Discretion

- OPcache extension install method — standard `docker-php-ext-install opcache`, no user input needed
- `.dockerignore` exact file list — user chose "standard exclusions", Claude picks the specific files

## Deferred Ideas

None — discussion stayed within phase scope.
