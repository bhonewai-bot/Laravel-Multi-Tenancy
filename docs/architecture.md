# Architecture Overview

## Topology

```mermaid
flowchart LR
    Browser["User Browser"] --> Host{"Host"}
    Host -->|app.localhost| Central["Central Routes"]
    Host -->|tenant/custom domain| Tenant["Tenant Routes"]

    Central --> CentralDB[(Central DB)]
    Tenant --> Tenancy["stancl/tenancy context bootstrap"]
    Tenancy --> TenantDB[(Tenant DB)]

    Queue["Queue Worker"] --> InstallJobs["Install/Uninstall Jobs"]
    InstallJobs --> TenantDB
    InstallJobs --> CentralDB
```

## Data ownership

- Central DB (`central`)
  - `tenants`, `domains`
  - `modules`, `module_requests`
  - central `users` (super admin / central auth)
- Tenant DB (`tenant{tenant_id}`)
  - tenant `users`
  - RBAC tables: `roles`, `features`, `permissions`, `role_permissions`
  - tenant business/module tables (for installed modules)

## Central tenant onboarding flow

```mermaid
sequenceDiagram
    participant A as Central Admin
    participant C as TenantController@store
    participant D as Central DB
    participant E as TenantCreated Event Pipeline
    participant T as Tenant DB

    A->>C: POST /tenants (tenant_id, domain, metadata)
    C->>D: Insert tenant + primary domain
    D-->>E: TenantCreated
    E->>T: Create database
    E->>T: Run tenant migrations
    E->>T: Run tenant bootstrap seeder
```

## Module install/uninstall flow (hardened)

```mermaid
sequenceDiagram
    participant U as Tenant User
    participant M as ModuleRequestController
    participant R as TenantModuleRegistry
    participant Q as Queue Job
    participant I as TenantModuleInstaller
    participant T as Tenant DB

    U->>M: POST /modules/install
    M->>R: mark operation queued
    M->>Q: dispatch install job
    U->>M: GET /modules?watch...
    M->>R: read operation state
    M-->>U: show Installing...
    Q->>R: mark running
    Q->>I: install()
    I->>T: migrate/seed + update installed_modules
    Q->>R: mark success/failure
    U->>M: GET /modules?watch...
    M->>R: read terminal state + clear
    M-->>U: final success/failure alert
```

## Custom domain acceptance model

- Tenant can add custom domain.
- Verification requires TXT record match (`verification_code`).
- `verified_at` is set only after DNS verification passes.
- Internal domain-check endpoint only returns `OK` for:
  - central domains
  - verified custom domains

This allows edge proxy (Caddy) to gate host acceptance.
