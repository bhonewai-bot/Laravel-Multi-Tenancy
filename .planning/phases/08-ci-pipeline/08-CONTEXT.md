# Phase 8: CI Pipeline - Context

**Gathered:** 2026-06-29
**Status:** Ready for planning

<domain>
## Phase Boundary

Add code style enforcement (Pint), dependency security audit (composer audit), and Docker build validation to the existing CI pipeline. All three checks run as separate parallel jobs alongside the existing test job.

Requirements in scope: CI-01, CI-02, CI-03.

</domain>

<decisions>
## Implementation Decisions

### CI Structure (CI-01, CI-02)
- **D-01:** Three new separate jobs: `style`, `audit`, `docker` — run in parallel with existing `test` job
- **D-02:** Existing `test` job unchanged — continues to run tests
- **D-03:** Trigger on push/PR to main (same as existing workflow)

### Code Style (CI-01)
- **D-04:** New `style` job: checkout, setup PHP 8.3, install deps, run `vendor/bin/pint --test`
- **D-05:** Uses `--test` flag (dry-run, fails if violations found, doesn't modify files)
- **D-06:** Only needs PHP + composer (no Node.js needed)

### Dependency Audit (CI-02)
- **D-07:** New `audit` job: checkout, setup PHP 8.3, install deps, run `composer audit`
- **D-08:** Only needs PHP + composer (no Node.js needed)

### Docker Build (CI-03)
- **D-09:** New `docker` job: checkout, run `docker build .`
- **D-10:** Also run `docker compose -f docker-compose.prod.yml config` to validate prod compose
- **D-11:** Uses Docker buildx (available by default on ubuntu-latest)
- **D-12:** No need to start containers — just validate the build succeeds

### Claude's Discretion
- Job names (style, audit, docker) — Claude picks descriptive names
- Whether to cache composer dependencies (probably yes for speed)
- Whether pint job needs full composer install or just pint binary

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### CI Configuration
- `.github/workflows/ci.yml` — Existing CI workflow (checkout, PHP/Node setup, test job)

### Requirements
- `.planning/REQUIREMENTS.md` — CI-01, CI-02, CI-03 definitions

### Docker Infrastructure
- `Dockerfile` — Production Dockerfile (to be validated by docker build)
- `docker-compose.prod.yml` — Production compose (to be validated by docker compose config)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `.github/workflows/ci.yml` — Existing workflow with checkout, PHP 8.3 setup, Node 20 setup, composer validate, test job. Just needs new jobs added.

### Established Patterns
- Uses `actions/checkout@v4`, `shivammathur/setup-php@v2`, `actions/setup-node@v4`
- PHP 8.3 with composer v2
- Node 20 with npm cache
- Runs on `ubuntu-latest`

### Integration Points
- New jobs go in the same `.github/workflows/ci.yml` file
- Same trigger (push/PR to main)
- No dependencies between new jobs and existing test job

</code_context>

<specifics>
## Specific Ideas

- Existing workflow is clean and well-structured — add jobs, don't modify existing ones
- pint --test is a dry-run that exits non-zero on violations — perfect for CI
- composer audit checks for known CVEs in dependencies — fails if any found

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 8-CI Pipeline*
*Context gathered: 2026-06-29*
