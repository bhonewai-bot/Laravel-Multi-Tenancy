# Phase 5: Nginx Hardening - Context

**Gathered:** 2026-06-29
**Status:** Ready for planning

<domain>
## Phase Boundary

Harden the nginx configuration with security headers (CSP, HSTS, X-Content-Type-Options, Referrer-Policy), gzip compression for text-based assets, static file caching with Vite-hash-aware cache headers, and `server_tokens` off. Changes apply to both dev and prod nginx configs, with prod getting stricter CSP than dev.

Requirements in scope: NGINX-01, NGINX-02, NGINX-03, NGINX-04, NGINX-05, NGINX-06.

</domain>

<decisions>
## Implementation Decisions

### CSP Policy (NGINX-01)
- **D-01:** CSP header applied in BOTH dev and prod configs — dev gets more flexibility, prod gets maximum strictness
- **D-02:** Dev CSP: `script-src 'self' 'unsafe-inline' 'unsafe-eval';` — permissive for Livewire/Alpine development
- **D-03:** Prod CSP: `script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self';` — restricts outbound fetch/XHR to same-origin only
- **D-04:** Start with `Content-Security-Policy-Report-Only` header first (logs violations without blocking), switch to enforcing after confirming no breakage
- **D-05:** Livewire 4 requires `'unsafe-inline'` and `'unsafe-eval'` in `script-src` — this is a known constraint, not a security gap

### HSTS Policy (NGINX-02)
- **D-06:** `max-age=300` (5 minutes) — conservative for first deployment, easy to revert if something breaks
- **D-07:** `includeSubDomains` — covers all tenant subdomains (*.central_domain). Required for future HSTS preload eligibility
- **D-08:** No `preload` directive yet — add later when max-age is increased to 1 year after confirming everything works

### Additional Headers (NGINX-03)
- **D-09:** `X-Content-Type-Options: nosniff` — prevents MIME type sniffing
- **D-10:** `Referrer-Policy: strict-origin-when-cross-origin` — sends origin only on cross-origin requests

### Gzip Compression (NGINX-04)
- **D-11:** Gzip enabled at nginx level — compresses origin→Cloudflare traffic, also helps if Cloudflare is bypassed
- **D-12:** `gzip_min_length 256` — standard default, only compresses responses larger than 256 bytes
- **D-13:** `gzip_proxied any` — compress all responses regardless of proxy headers, safest default
- **D-14:** Gzip types: `text/html text/css application/javascript application/json image/svg+xml` (per ROADMAP success criteria)

### Asset Caching (NGINX-05)
- **D-15:** Vite-hashed assets (JS/CSS with content hash): `Cache-Control: public, max-age=31536000, immutable` — 1-year cache, browser never revalidates
- **D-16:** Non-hashed assets (images in public/, fonts): `Cache-Control: public, max-age=86400` — 1-day cache, browser revalidates daily
- **D-17:** HTML/PHP responses: `Cache-Control: no-cache, no-store, must-revalidate` — always revalidate, no stale dynamic content

### Server Tokens (NGINX-06)
- **D-18:** `server_tokens off` — hides nginx version from response headers and error pages

### Config Scope
- **D-19:** Both dev (`docker/nginx/conf.d/app.conf`) and prod (`docker/nginx/conf.d/prod/app.conf`) configs receive security headers
- **D-20:** Prod config gets stricter CSP (connect-src 'self'), dev config gets permissive CSP (no connect-src restriction)

### Claude's Discretion
- Exact gzip_types list — follows ROADMAP success criteria (text/html, text/css, application/javascript, application/json, image/svg+xml)
- Cache-Control for API responses (JSON from /api/*) — Claude can decide based on whether API routes exist
- Header placement in nginx config (http block vs server block) — standard practice applies

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` — NGINX-01 through NGINX-06 definitions and acceptance criteria

### Nginx Configuration
- `docker/nginx/conf.d/app.conf` — Dev nginx config (basic PHP-FPM setup, no security headers currently)
- `docker/nginx/conf.d/prod/app.conf` — Prod nginx config (SSL/HTTP2 with Cloudflare origin certs, HTTP→HTTPS redirect)

### Docker Infrastructure
- `docker-compose.yml` — Dev compose (nginx service with bind-mount)
- `docker-compose.prod.yml` — Prod compose (nginx service with bind-mount retained from Phase 4)
- `Dockerfile` — Production image (nginx not in image, configured via compose bind-mount)

### Codebase Maps
- `.planning/codebase/STACK.md` — Technology stack and platform requirements
- `.planning/codebase/ARCHITECTURE.md` — System architecture and component responsibilities

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `docker/nginx/conf.d/prod/app.conf` — Already has SSL termination with Cloudflare origin certs, HTTP→HTTPS redirect, and Cloudflare custom hostname challenge location block. Needs security headers, gzip, and caching added.
- `docker/nginx/conf.d/app.conf` — Simple dev config with PHP-FPM proxy. Needs security headers and gzip added.

### Established Patterns
- Nginx uses `fastcgi_pass app:9000` to proxy to PHP-FPM container
- Cloudflare Custom Hostnames challenge endpoint at `/.well-known/cf-custom-hostname-challenge/` must remain accessible
- SSL termination at nginx level (not Caddy) for prod — Cloudflare origin certs in `docker/nginx/ssl/`

### Integration Points
- `docker-compose.yml` nginx service — bind-mounts `docker/nginx/conf.d/` for config and `./:/var/www` for static files
- `docker-compose.prod.yml` nginx service — same bind-mount pattern (retained from Phase 4 decision)
- Vite build output in `public/build/` — assets have content hashes in filenames (e.g., `app-abc123.css`)

</code_context>

<specifics>
## Specific Ideas

- CSP starts as Report-Only — switch to enforcing after confirming no breakage in production
- HSTS max-age=300 is deliberately conservative — increase to 1 year after first successful deployment
- gzip enabled at nginx level even though Cloudflare compresses edge→client — covers direct-to-origin scenarios

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 5-Nginx Hardening*
*Context gathered: 2026-06-29*
