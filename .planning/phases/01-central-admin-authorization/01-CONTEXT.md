# Phase 1: Central Admin Authorization - Context

**Gathered:** 2026-06-25
**Status:** Ready for planning

## Phase Boundary

Gate all central CRUD routes (tenants, modules, module-requests) behind a super-admin check. Only the user whose email matches `CENTRAL_SUPERADMIN_EMAIL` can manage tenants, modules, and module requests. Non-admin authenticated users receive a 403 Forbidden response.

Requirements: AUTH-01, AUTH-02, AUTH-03, AUTH-04

## Implementation Decisions

### Authorization Mechanism
- **D-01:** Use a Laravel Gate defined in `AppServiceProvider::boot()`. The Gate compares the authenticated user's email against `config('auth.superadmin.email')`. One definition, checked via `Gate::allows('access-central-admin')` everywhere.
- **D-02:** Dedicated middleware class `EnsureCentralAdmin` (aliased as `central.admin` in `bootstrap/app.php`). Applied alongside `auth` in `routes/web.php`. Matches the existing pattern used by `module`, `role`, and `permission` middleware aliases.
- **D-03:** Admin identity check via email comparison against `config('auth.superadmin.email')` (existing `CENTRAL_SUPERADMIN_EMAIL` env var). No database column needed — single super-admin is the current requirement.

### Error Response
- **D-04:** Non-admin users receive a 403 Forbidden page. The app already has `resources/views/errors/403.blade.php` from earlier work. Middleware calls `abort(403)`; the existing error page renders.

### Enforcement Layers
- **D-05:** Two layers of defense:
  1. Route middleware (`EnsureCentralAdmin`) — blocks non-admins before controllers execute
  2. Form request `authorize()` — `TenantStoreRequest` checks `Gate::allows('access-central-admin')` instead of `(bool) $this->user()`
- **D-06:** Route middleware catches all central routes at the route group level. Form request authorize() is the belt-and-suspenders second layer.

### Claude's Discretion
- Gate name: `access-central-admin` — concise, self-documenting
- Middleware class: `app/Http/Middleware/EnsureCentralAdmin.php` — matches sibling naming (`EnsureModuleInstalled`, `EnsureTenantPermission`, `EnsureTenantRole`)
- Middleware alias: `central.admin` — matches the `module` / `role` / `permission` pattern in `bootstrap/app.php`
- Gate registration: inside `AppServiceProvider::boot()` — consistent with the existing `RolePolicy`
- Admin identity: `Gate::define('access-central-admin', fn (User $user) => $user->email === config('auth.superadmin.email'))` — Laravel 12 closure Gate style
- No Blade `@can` changes in this phase — route blocking is sufficient. Views can be updated later.

## Canonical References

### Requirements & Scope
- `.planning/ROADMAP.md` — Phase 1 section (success criteria, depends-on, plans)
- `.planning/REQUIREMENTS.md` — AUTH-01 through AUTH-04
- `.planning/PROJECT.md` — Core value, key decisions

### Existing Codebase
- `bootstrap/app.php` — Middleware aliases (line 43-48), routing closure (line 21-41), existing alias pattern to follow
- `routes/web.php` — Current central routes (line 14-35), currently `auth`-only middleware
- `config/auth.php` — Central admin config (`superadmin.email`, `superadmin.name`, `superadmin.password`)
- `app/Providers/AppServiceProvider.php` — Where Gate will be registered (existing policy registration at boot)
- `app/Http/Requests/TenantStoreRequest.php` — Form request to update (currently `return (bool) $this->user()`)
- `app/Http/Middleware/EnsureTenantPermission.php` — Reference middleware to follow (structure, naming, abort pattern)
- `resources/views/errors/403.blade.php` — Existing 403 page (already built)

### Audit Reference
- `docs/audit-2026-06-25.md` — CRITICAL-1 section documenting the authorization gap

## Existing Code Insights

### Reusable Assets
- **403 error page** (`resources/views/errors/403.blade.php`) — Already exists with minimal centered card design. No new error views needed.
- **Middleware pattern** (`EnsureTenantPermission`, `EnsureTenantRole`, `EnsureModuleInstalled`) — All follow the same structure: extract user, check condition, `abort(403)` on failure. New middleware mirrors this.

### Established Patterns
- **Middleware aliasing** — `bootstrap/app.php:44-48` uses `$middleware->alias([...])`. New middleware registered the same way.
- **Route grouping** — `routes/web.php:14` uses `Route::middleware('auth')->group(...)`. Admin check added as `Route::middleware(['auth', 'central.admin'])->group(...)`.
- **Gate registration** — `AppServiceProvider::boot()` already registers policies. Gate defined here for consistency.

### Integration Points
- **`routes/web.php:14`** — Current `auth` middleware group. Will become `['auth', 'central.admin']`.
- **`bootstrap/app.php:44`** — Middleware alias array. Add `'central.admin' => EnsureCentralAdmin::class`.
- **`TenantStoreRequest::authorize()`** — Replace `(bool) $this->user()` with `Gate::allows('access-central-admin')`.

## Specific Ideas

None — decisions above cover all requirements with standard Laravel patterns.

## Deferred Ideas

- Blade `@can` / `@cannot` visibility for admin-only UI elements — belongs in a polish phase, not security hardening
- Multiple admin support — out of scope per PROJECT.md (single super-admin is sufficient)
- Rate limiting on admin login — separate concern, not in this milestone

---

*Phase: 1-Central Admin Authorization*
*Context gathered: 2026-06-25*
