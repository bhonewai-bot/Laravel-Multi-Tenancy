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

## ADR-006: Keep module management UI self-contained with Blade layout
- Date: 2026-02-26
- Status: Accepted
- Context: Need fast, consistent UI for central and tenant module pages without coupling to global app CSS/Vite pipeline.
- Decision:
  - Introduce `resources/views/layouts/dark.blade.php`.
  - Build module pages on top of this layout for central and tenant screens.
  - Keep styling local to Blade during early rebuild milestones.
- Consequences:
  - Pros: Fast iteration, low blast radius, consistent look between central and tenant views.
  - Cons: Styling is not yet integrated with a global design system.

## ADR-007: Module catalog and requests remain central-connection models
- Date: 2026-02-26
- Status: Accepted
- Context: Tenant UI needs to query global module metadata and request workflow; tenant DB does not own `modules` catalog.
- Decision:
  - Keep `Module` and `ModuleRequest` using `Stancl\Tenancy\Database\Concerns\CentralConnection`.
  - Query these models from tenant controllers for marketplace/request status display.
- Consequences:
  - Pros: Single source of truth for module metadata and request lifecycle.
  - Cons: Requires careful route/controller logic to avoid accidental tenant-scoped table assumptions.

## ADR-008: Approval and installation are separate responsibilities
- Date: 2026-02-26
- Status: Accepted
- Context: Need governance control from central while preserving tenant-controlled install lifecycle.
- Decision:
  - Central app handles request review only (`pending` -> `approved`/`rejected`).
  - Tenant app performs actual install/uninstall and writes `installed_modules`.
- Consequences:
  - Pros: Clear responsibility split and auditable approval flow.
  - Cons: Requires additional install checks and migration hooks on tenant side.

## ADR-009: Enforce tenant module access with middleware + smoke-test gate
- Date: 2026-02-26
- Status: Accepted
- Context: Module routes must be inaccessible unless a module is installed for the current tenant.
- Decision:
  - Use `module:<slug>` middleware on tenant module routes (`customer`, `product`, `sale`).
  - Read tenant-installed module state from tenant metadata (`installed_modules`).
  - Keep manual smoke-test gate: uninstall should produce `403`, reinstall should restore `200`.
- Consequences:
  - Pros: Strong route-level safety and clear operational verification criteria.
  - Cons: Requires careful consistency of module slug naming across routes, UI, and tenant metadata.

## ADR-010: Central tenant onboarding provisions DB via tenancy events
- Date: 2026-02-27
- Status: Accepted
- Context: Central admin onboarding should create a fully usable tenant without manual `tenants:migrate`.
- Decision:
  - Use central `POST /tenants` onboarding to create tenant + primary domain.
  - Rely on `TenantCreated` event pipeline in `TenancyServiceProvider` for provisioning:
    - `CreateDatabase`
    - `MigrateDatabase`
  - Keep seeding out of Step 11 and defer to Step 12 (`SeedDatabase`).
- Consequences:
  - Pros: Predictable zero-manual provisioning flow and cleaner onboarding UX.
  - Cons: Requires event pipeline health/visibility for operational debugging.

## ADR-011: Use integrated Breeze-style sidebar shell for authenticated pages
- Date: 2026-02-27
- Status: Accepted
- Context: Previous page-level layouts and drawer-like sidebar made UI feel fragmented and inconsistent.
- Decision:
  - Standardize authenticated pages on a shared Breeze-style white app shell with fixed left sidebar.
  - Use grouped/collapsible sidebar navigation per context (central vs tenant).
  - Keep tables in full-width fixed layout for better scanability in admin screens.
- Consequences:
  - Pros: Consistent UX, better information density, cleaner navigation mental model.
  - Cons: Requires mobile sidebar behavior to be explicitly handled in a follow-up iteration.

## ADR-012: Seed tenant bootstrap data automatically on tenant creation
- Date: 2026-02-28
- Status: Accepted
- Context: New tenants should be usable immediately after central onboarding without manual `tenants:seed`.
- Decision:
  - Extend `TenantCreated` pipeline to include:
    1. `CreateDatabase`
    2. `MigrateDatabase`
    3. `SeedDatabase`
  - Configure tenancy seeder root class to `TenantBootstrapSeeder`.
  - Keep seed scope tenant-only and idempotent (`firstOrCreate` for default admin user).
  - Use deterministic credentials pattern:
    - email: `admin@{tenant_id}.local`
    - password: `TENANT_DEFAULT_ADMIN_PASSWORD` from env (never persisted in plaintext).
- Consequences:
  - Pros: Zero-manual bootstrap for new tenants; predictable login for onboarding/handover.
  - Cons: Requires strict tenant migration parity (e.g., cache table availability when `CACHE_STORE=database`).
