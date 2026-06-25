# Phase 1: Central Admin Authorization - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-25
**Phase:** 01-central-admin-authorization
**Areas discussed:** Gate vs Middleware, Error response style, Where to enforce, Middleware placement

---

## Gate vs Middleware

| Option | Description | Selected |
|--------|-------------|----------|
| Gate | Define in AppServiceProvider, check via Gate::allows(). One definition, checked everywhere. | ✓ |
| Middleware only | Dedicated EnsureCentralAdmin class. Applied to route groups. Simpler but doesn't protect form requests. | |
| Both Gate + Middleware | Define Gate AND middleware that calls it. Gate for requests/views, middleware for routes. | |

**User's choice:** Gate

---

## Error Response Style

| Option | Description | Selected |
|--------|-------------|----------|
| 403 Forbidden page | errors/403.blade.php — simple, secure, no info leak | ✓ |
| Redirect with flash | Redirect to dashboard with flash error | |
| 404 Not Found | Hide that the route exists from unauthorized users | |

**User's choice:** 403 Forbidden page

---

## Where to Enforce

| Option | Description | Selected |
|--------|-------------|----------|
| Both layers | Route middleware AND form request authorize() | ✓ |
| Route middleware only | TenantStoreRequest stays as-is since non-admins never reach it | |
| Middleware + Requests + Views | Add Blade @can to hide admin-only links too | |

**User's choice:** Both layers

---

## Middleware Placement

| Option | Description | Selected |
|--------|-------------|----------|
| Dedicated class | EnsureCentralAdmin, aliased as central.admin, applied in routes/web.php | ✓ |
| Inline closure | Closure in routes/web.php inside the auth group | |
| bootstrap/app.php | On the domain group itself | |

**User's choice:** Dedicated class

---

## Claude's Discretion

- Gate name: `access-central-admin`
- Middleware class: `app/Http/Middleware/EnsureCentralAdmin.php`
- Alias: `central.admin`
- Registration: `AppServiceProvider::boot()`
- Identity check: `$user->email === config('auth.superadmin.email')`
- No Blade @can changes in this phase

## Deferred Ideas

- Blade @can visibility for admin-only UI — future polish phase
- Multiple admin support — out of scope per PROJECT.md
