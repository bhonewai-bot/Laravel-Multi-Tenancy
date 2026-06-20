---
name: diagnose-custom-domain
description: "Use when a tenant reports their custom domain is not working — returning 403, 404, or SSL errors. Covers diagnosing Cloudflare hostname status, domain verification state, DNS misconfiguration, and stuck background sync jobs. Do not use for general tenant setup or Cloudflare account-wide issues."
metadata:
    author: team
---

# Diagnose Custom Domain

When a tenant says "my domain isn't working," work through these steps **in order**. Each step eliminates one possible cause.

## Step 1 — Get the domain

Ask the tenant for their domain name. Normalize it: lowercase, remove trailing dots, strip whitespace.

```
Example: "Shop.AcmeCorp.com " → "shop.acmecorp.com"
```

## Step 2 — Find the domain record

Query the central `domains` table:

```php
$domain = \App\Models\Domain::where('domain', $normalizedHost)->first();
```

If `null`:

- The domain was never added. Tell the user: _"This domain is not registered. Add it via the tenant dashboard under Domains → Add Domain."_
- **Stop here.**

## Step 3 — Check if it's a platform subdomain

Platform subdomains (ending in a configured central domain like `.app.localhost`) are trusted automatically. Check with:

```php
// A platform subdomain ends with one of config('tenancy.central_domains')
// Example: "t001.app.localhost" is a platform subdomain — trusted by default
```

If it IS a platform subdomain and not working:

- Something deeper is wrong. Check if the tenant exists, if the database exists, if nginx/caddy is routing correctly.
- Use `docker compose ps` to verify all services are up.

## Step 4 — Check Cloudflare hostname status

Read these columns from the domain record:

| Field                | Meaning                                                                    |
| -------------------- | -------------------------------------------------------------------------- |
| `cf_hostname_id`     | The Cloudflare custom hostname ID. `null` = never created.                 |
| `cf_hostname_status` | `active` = Cloudflare issued the hostname. `pending` = still provisioning. |
| `cf_ssl_status`      | `active` = SSL certificate ready. `pending` = still issuing.               |
| `cf_error`           | Any error message from Cloudflare (e.g., CAA record blocking).             |
| `verified_at`        | The timestamp when our app marked this domain as trusted.                  |
| `pollAttempt`        | (In queue job) How many times the background job has checked.              |

### Decision tree:

```
cf_hostname_id is NULL?
  → The domain was never sent to Cloudflare. Run sync manually or check logs.
  → Stop here.

cf_hostname_status is NOT 'active'?
  → Cloudflare is still provisioning. This takes 2-5 minutes normally.
  → Check if the polling job is running (Step 5).

cf_ssl_status is NOT 'active'?
  → SSL certificate is still being issued or failed.
  → Check cf_error for details. Common causes:
    - CAA DNS record blocking issuance
    - Origin server not reachable from Cloudflare
    - HTTP validation path returning non-200

cf_error is NOT null?
  → Read the error message. Common errors:
    - "CAA record" → The domain's DNS has a CAA record blocking Cloudflare
    - "timeout" → Cloudflare can't reach your origin
    - "validation failed" → The HTTP challenge file isn't being served

verified_at is NULL but cf_hostname_status='active' and cf_ssl_status='active'?
  → The polling job may have stopped before marking verified. Check the job (Step 5).
  → The domain should be working despite verified_at being NULL.
```

## Step 5 — Check the background polling job

The `SyncPendingCloudflareDomain` job polls Cloudflare up to **15 times** at **2-minute intervals** (30 minutes total).

```php
// Check if the job is still running or stuck
// Look in the jobs table for this domain:
\Illuminate\Support\Facades\DB::table('jobs')
    ->where('payload', 'like', '%SyncPendingCloudflareDomain%')
    ->where('payload', 'like', '%"domainId":' . $domain->id . '%')
    ->get();
```

Also check `failed_jobs` table in case the job exhausted its retries.

**Key numbers:**

- `pollAttempt < 15` and `shouldRetry()` returns true → job is still running, just wait
- `pollAttempt >= 15` → retry budget exhausted, Cloudflare took too long
- Job in `failed_jobs` → something crashed during polling

## Step 6 — Check DNS configuration

The domain's DNS must point to Cloudflare. Tell the tenant to verify:

1. The domain has a CNAME record pointing to the Cloudflare edge (the fallback origin or their Cloudflare zone)
2. DNS propagation can take up to 48 hours (usually 5-30 minutes)
3. Use `dig CNAME shop.acmecorp.com` or a public DNS checker

## Step 7 — Verify the middleware decision

The `RejectInvalidTenantHost` middleware makes three checks in order:

```
1. Is this a central host?   → abort(404)  // "central hosts don't serve tenant routes"
2. Does the domain exist?    → abort(404)  // "unknown domain"
3. Is the domain verified?   → abort(403)  // "domain exists but not trusted yet"
```

The verification check (`isVerifiedTenantHost`) passes if:

- `domain.verified_at !== null` (verified after Cloudflare sync), OR
- The domain is a platform subdomain (ends with a central domain)

## Summary cheat sheet

| Symptom               | Most Likely Cause                              | Check                         |
| --------------------- | ---------------------------------------------- | ----------------------------- |
| 404 error             | Domain not registered or central host mismatch | Step 2, Step 7                |
| 403 error             | Domain exists but not verified                 | Step 4, check `verified_at`   |
| SSL warning           | Cloudflare SSL still provisioning              | Step 4, check `cf_ssl_status` |
| Stuck "pending"       | Polling job stopped or exhausted               | Step 5                        |
| Works on some devices | DNS propagation incomplete                     | Step 6                        |
| `cf_error` filled     | Cloudflare rejected the hostname               | Step 4, read the error        |

## When to escalate

If all steps pass but the domain still doesn't work:

1. Check Cloudflare dashboard directly — does the custom hostname appear there?
2. Verify the Cloudflare API token still has valid permissions
3. Check Caddy/nginx logs: `docker compose logs caddy` and `docker compose logs nginx`
4. Run the Cloudflare health check skill (if available)
