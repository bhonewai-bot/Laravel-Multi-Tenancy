---
phase: 05-nginx-hardening
plan: 01
subsystem: infrastructure
tags: [nginx, security-headers, csp, hsts, docker]
dependencies:
  requires: []
  provides: [nginx-security-headers, server-tokens-off]
  affects: [docker-compose, nginx-config]
tech_stack:
  added: []
  patterns: [nginx-security-headers, custom-nginx-conf]
key_files:
  created:
    - docker/nginx/nginx.conf
  modified:
    - docker/nginx/conf.d/app.conf
    - docker/nginx/conf.d/prod/app.conf
    - docker-compose.yml
    - docker-compose.prod.yml
decisions:
  - "CSP starts as Report-Only per D-04 to allow violation logging before enforcement"
  - "Dev CSP includes ws:/wss: for Vite HMR WebSocket connectivity per D-02"
  - "HSTS max-age=300 with includeSubDomains and no preload per D-06/D-07/D-08"
  - "Custom nginx.conf replicates default nginx:alpine config plus server_tokens off per D-18"
metrics:
  duration_minutes: ~5
  completed_date: 2026-06-29
  tasks_completed: 2
  tasks_total: 2
status: complete
---

# Phase 5 Plan 01: Nginx Hardening Summary

Security headers and server_tokens off applied to both dev and prod nginx configurations via a custom nginx.conf and server-level add_header directives.

## What Was Done

### Task 1: Custom nginx.conf with server_tokens off

Created `docker/nginx/nginx.conf` replicating the default nginx:alpine configuration with `server_tokens off;` added in the `http` block. This hides the nginx version from the `Server` response header and error pages.

The file includes the `include /etc/nginx/conf.d/*.conf;` directive so existing server block configs continue to load.

Mounted in both compose files:
- `docker-compose.yml` (dev): added `./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro` to nginx service volumes
- `docker-compose.prod.yml` (prod): added `./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro` to nginx service volumes

### Task 2: Security headers in dev and prod configs

Added four security headers at the server level in all nginx server blocks:

**Dev config** (`docker/nginx/conf.d/app.conf`) -- single server block:
- `Content-Security-Policy-Report-Only` with `script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self' ws: wss:` (permissive for Vite HMR)
- `Strict-Transport-Security` with `max-age=300; includeSubDomains`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`

**Prod config** (`docker/nginx/conf.d/prod/app.conf`) -- two server blocks (HTTP port 80 and HTTPS port 443):
- Same four headers on both blocks
- CSP uses strict `connect-src 'self';` only (no ws:/wss:)

All headers use the `always` flag for coverage on error responses (4xx, 5xx).

## Acceptance Criteria Verification

| Criterion | Status |
|-----------|--------|
| `docker/nginx/nginx.conf` exists with `server_tokens off` in http block | Verified |
| File includes `/etc/nginx/conf.d/*.conf` | Verified |
| `docker-compose.yml` nginx service has nginx.conf bind-mount | Verified |
| `docker-compose.prod.yml` nginx service has nginx.conf bind-mount | Verified |
| Dev config has all 4 security headers | Verified |
| Dev CSP includes `connect-src 'self' ws: wss:` | Verified |
| Prod HTTP server block has all 4 headers with strict CSP | Verified |
| Prod HTTPS server block has all 4 headers with strict CSP | Verified |
| CSP is `Content-Security-Policy-Report-Only` (not enforcing) | Verified |
| HSTS max-age=300 with includeSubDomains, no preload | Verified |
| All headers use `always` flag | Verified |
| Cloudflare challenge location block unchanged | Verified |
| PHP fastcgi directives preserved | Verified |

## Known Stubs

None. All headers have real values wired to the nginx configs.

## Threat Flags

None. All changes are defensive security measures (header additions, version suppression).

## Notes

- Plan 05-02 will add Cache-Control headers to location blocks. At that time, all security headers must be repeated in those location blocks to prevent nginx's add_header inheritance override from stripping them.
- CSP is Report-Only intentionally (per D-04). After confirming no breakage via violation reports, it can be upgraded to enforcing CSP in a future plan.
