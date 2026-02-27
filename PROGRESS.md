# Progress Log

## 2026-02-27

### Done
- Completed Step 11: Central Tenant Onboarding.
  - Central tenant CRUD routes/controller are active for onboarding.
  - `TenantStoreRequest` validates unique `tenant_id` (`tenants.id`) and `domain` (`domains.domain`).
  - `TenantController@store` creates tenant + primary domain in central DB.
  - No manual `tenants:migrate` is needed after onboarding.
- Confirmed automatic tenant provisioning pipeline is wired:
  - `TenantCreated` -> `CreateDatabase` -> `MigrateDatabase`
  - Implemented in `app/Providers/TenancyServiceProvider.php`.
- Refactored app UI from dark custom layout to Breeze-style shell:
  - Replaced old dark layout usage with `x-app-layout` across tenant/module pages.
  - Implemented integrated sidebar app shell and removed isolated/floating behavior.
  - Added sidebar logout button.
- Updated table layouts for better full-width data distribution:
  - Tenant list uses action dropdown and fixed-width table columns.
  - Modules list and module requests list use full-width fixed column layouts.
  - Module request statuses now have more visual badges (dot + color + border).

### Commands Run
- `php artisan view:clear`
- `php artisan view:cache`
- `rg -n "TenantCreated|CreateDatabase|MigrateDatabase" app/Providers config/tenancy.php`

### Result
- New tenant onboarding is central-first and automatic:
  - create tenant + domain in central
  - tenant DB created
  - tenant migrations run
  - tenant is reachable without manual migration command
- UI is now consistent with Breeze-style white theme and shared sidebar shell.
- Tenant/module/module-request tables are more readable and proportional.

### Next
1. Step 12: Auto-seeding on tenant provision (`SeedDatabase` pipeline + baseline tenant data).
2. Step 13: Tenant RBAC (roles/permissions/policies + middleware enforcement).
3. Step 14: Custom domain lifecycle (add, verify, activate/deactivate).
4. Add feature tests for central onboarding pipeline and tenant usability after create.

### Blockers
- None currently.

## 2026-02-26

### Done
- Completed Step 9 module platform skeleton (central-first):
  - Added central views:
    - `resources/views/modules/index.blade.php`
    - `resources/views/modules/create.blade.php`
    - `resources/views/module-requests/index.blade.php`
  - Added shared dark layout:
    - `resources/views/layouts/dark.blade.php`
  - Applied dark layout to tenant modules page:
    - `resources/views/tenant/modules/index.blade.php`
- Fixed central route typo:
  - `POST /module-requests/{moduleRequest}/approve` (was misspelled as `/modues-requests/...`)
- Fixed module model field mismatch:
  - `app/Models/Module.php` fillable key changed from `image_path` to `icon_path`.
- Fixed tenant module list rendering bug:
  - Replaced wrong variable `$requests` with `$requestModules` in tenant module view.
- Verified route wiring for both central and tenant module endpoints.
- Confirmed request/approval/install state transitions in UI:
  - Tenant can request module.
  - Central can approve request.
  - Tenant can install approved module.
  - Tenant can uninstall installed module.
- Completed Step 10 install flow behavior for module lifecycle:
  - Tenant install validates module is active + approved before execution.
  - Tenant module migrations run against tenant connection during install.
  - Tenant `installed_modules` state is updated on install/uninstall.
- Completed module access guard setup:
  - Middleware alias `module` is active.
  - Module routes enforce `module:customer`, `module:product`, and `module:sale`.
- Completed smoke test wrap-up (manual validation):
  - Verified module middleware visibility in route list (`route:list -v`).
  - After uninstalling `Customer`, `/customers` returned `403`.
  - After reinstalling `Customer`, `/customers` returned `200`.
  - `Product` route access remained available when installed.

### Commands Run
- `docker compose exec app php artisan route:list | rg "modules|module-requests"`
- `docker compose exec app php artisan view:clear`
- `php artisan route:list -v` (module middleware verification)

### Result
- Central module pages now render without `View [modules.index] not found`.
- Tenant module page now shows consistent dark UI and correct request status.
- Module request approve/reject routes are correctly addressable.
- Step 9 is complete.
- Step 10 is complete.
- Module route protection is confirmed by smoke test.

### Next
1. Step 11: Central tenant onboarding (tenant create flow with domain + provisioning).
2. Step 12: Automatic tenant bootstrap seeding (default admin + baseline data).
3. Step 13: Tenant RBAC (roles/permissions + policy/middleware enforcement).
4. Step 14: Custom domain lifecycle (add, verify TXT, activate/deactivate).
5. Add automated feature tests for the completed module lifecycle smoke flow.

### Blockers
- None currently.

## 2026-02-25

### Done
- Created new project: `Laravel-Multi-Tenancy`.
- Added Docker-based local stack (`app`, `nginx`, `mysql`, `phpmyadmin`) using `docker-compose.yml`.
- Configured `.env` for central domain routing:
  - `APP_URL=http://app.localhost:8000`
  - `TENANCY_CENTRAL_DOMAIN=app.localhost`
- Installed and bootstrapped core packages:
  - `stancl/tenancy`
  - `nwidart/laravel-modules`
- Ran central migrations successfully (`users`, `cache`, `jobs`, `tenants`, `domains`).
- Enabled tenancy provider and route split:
  - Central routes in `routes/web.php` (scoped by central domains)
  - Tenant routes in `routes/tenant.php`
- Fixed tenant model wiring issues:
  - Added `App\Models\Tenant` with `HasDatabase`, `HasDomains`
  - Added `App\Models\Domain`
  - Updated `config/tenancy.php` to use app models
- Verified tenant database-per-tenant behavior:
  - Tenant `t001` resolves by domain `t001.app.localhost`
  - Database created as `tenantt001`
- Created baseline repository commit:
  - `d110bd2 chore(init): bootstrap Laravel Multi-Tenancy with Docker and tenancy foundation`

### Commands Run
- `composer create-project laravel/laravel Laravel-Multi-Tenancy`
- `docker compose up -d --build`
- `docker compose exec app composer require stancl/tenancy nwidart/laravel-modules`
- `docker compose exec app php artisan tenancy:install`
- `docker compose exec app php artisan migrate`
- `docker compose exec app php artisan tenants:migrate --tenants=t001`
- `docker compose exec app php artisan optimize:clear`
- `docker compose exec app php artisan tinker ...` (tenant/domain verification and recovery)

### Result
- Milestone complete: Step 1-8 foundation is working.
- Central app resolves at `app.localhost`.
- Tenant app resolves at `t001.app.localhost`.
- Data isolation pattern confirmed: database-per-tenant.

### Next
1. Step 9: Module platform skeleton (`modules`, `module_requests`, central and tenant module pages).
2. Step 10: Module install flow (approve/request/install/uninstall lifecycle).
3. Add module middleware (`module:<Name>`) and enforce access guard on tenant routes.
4. Add end-to-end tests for request/approve/install path.

### Blockers
- None currently.
