# Architecture Decisions

## ADR-001: Build from sketch, use `lara-ums` as reference only
- Date: 2026-02-25
- Status: Accepted
- Context: Need to demonstrate ownership and understanding, not copy existing implementation.
- Decision: Rebuild multi-tenancy in a new repo (`Laravel-Multi-Tenancy`) and use `lara-ums` only for comparison.
- Consequences:
  - Pros: Better architectural understanding, cleaner ownership.
  - Cons: Longer setup and implementation time.

## ADR-002: Use Dockerfile + docker-compose (not Sail)
- Date: 2026-02-25
- Status: Accepted
- Context: Requirement is Docker-based environment for reproducible setup.
- Decision: Standardize local dev using `docker compose` with app, nginx, mysql, redis, and optional phpmyadmin.
- Consequences:
  - Pros: Production-like environment and clear service boundaries.
  - Cons: Slightly more ops setup upfront.

## ADR-003: Run framework commands inside app container
- Date: 2026-02-25
- Status: Accepted
- Context: Host and container PHP/composer versions can diverge.
- Decision: Execute `composer` and `artisan` using `docker compose exec app ...`.
- Consequences:
  - Pros: Consistent runtime and fewer environment mismatch bugs.
  - Cons: Commands are slightly longer.

## ADR-004: Use app-level tenancy models instead of package defaults
- Date: 2026-02-25
- Status: Accepted
- Context: Domain relationship methods (`domains()`, `createDomain()`) are required by provisioning flow.
- Decision:
  - Create `App\Models\Tenant` extending Stancl base tenant with `HasDatabase` and `HasDomains`.
  - Create `App\Models\Domain` extending Stancl base domain.
  - Bind both in `config/tenancy.php` (`tenant_model`, `domain_model`).
- Consequences:
  - Pros: Explicit control and predictable tenant-domain behavior.
  - Cons: Slightly more setup than using package defaults directly.

## ADR-005: Database-per-tenant isolation strategy
- Date: 2026-02-25
- Status: Accepted
- Context: Need strong isolation between tenants for rebuild architecture.
- Decision: Use dedicated database per tenant (`tenant` prefix + tenant id, e.g. `tenantt001`) and keep platform data in central DB.
- Consequences:
  - Pros: Strong isolation and easier tenant-level backup/restore.
  - Cons: More operational complexity as tenant count grows.
