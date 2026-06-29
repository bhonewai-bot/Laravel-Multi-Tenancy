# Phase 5: Nginx Hardening - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-29
**Phase:** 5-Nginx Hardening
**Areas discussed:** CSP strictness, HSTS policy, Gzip tuning, Asset caching

---

## CSP strictness

### Q1: CSP connect-src for Livewire

| Option | Description | Selected |
|--------|-------------|----------|
| Minimal working CSP | script-src 'self' 'unsafe-inline' 'unsafe-eval'; No connect-src restriction | |
| Restrictive with connect-src | script-src + connect-src 'self' ws: wss: | |
| Full restrictive baseline | script-src + connect-src + style-src + img-src + font-src | |

**User's choice:** Minimal working CSP (Recommended)
**Notes:** User later clarified they want CSP in both dev and prod, with dev more permissive and prod maximum strictness.

### Q2: CSP scope (dev vs prod)

| Option | Description | Selected |
|--------|-------------|----------|
| Prod config only | Only apply CSP in production nginx | |
| Both dev and prod | Same CSP everywhere | |

**User's choice:** Both dev and prod — dev gets more flexibility, prod gets maximum strictness

### Q3: CSP split approach

| Option | Description | Selected |
|--------|-------------|----------|
| Dev: permissive, Prod: minimal working | Dev: script-src + connect-src ws/wss (Vite HMR). Prod: script-src only | ✓ |
| Both strict, dev adds HMR only | Same base policy, dev adds connect-src ws: wss: | |
| You decide | Claude picks best split | |

**User's choice:** Dev: permissive, Prod: minimal working (Recommended)
**Notes:** Dev gets connect-src ws: wss: for Vite HMR. Prod omits WebSocket support.

### Q4: Prod connect-src

| Option | Description | Selected |
|--------|-------------|----------|
| Add connect-src 'self' in prod | Blocks fetch/XHR to third-party origins | ✓ |
| Omit connect-src in prod | No restriction on fetch/XHR targets | |

**User's choice:** Add connect-src 'self' in prod (Recommended)

### Q5: CSP mode (enforce vs report-only)

| Option | Description | Selected |
|--------|-------------|----------|
| Enforce directly | Content-Security-Policy header | |
| Report-Only first | Content-Security-Policy-Report-Only header | ✓ |

**User's choice:** Report-Only first — logs violations without blocking, switch to enforcing after confirming no breakage

---

## HSTS policy

### Q1: HSTS max-age

| Option | Description | Selected |
|--------|-------------|----------|
| 1 year (31536000) | Standard production value | |
| 6 months (15768000) | More conservative | |
| Short (300 = 5 min) | Testing value, easy to revert | ✓ |

**User's choice:** Short (300 = 5 min) — conservative for first deployment

### Q2: includeSubDomains

| Option | Description | Selected |
|--------|-------------|----------|
| includeSubDomains | Covers all tenant subdomains | ✓ |
| Apex domain only | Only covers exact domain | |

**User's choice:** includeSubDomains (Recommended)

### Q3: HSTS preload

| Option | Description | Selected |
|--------|-------------|----------|
| Skip preload for now | Not applicable with 5-min max-age | ✓ |
| Add preload directive anyway | Include 'preload' for future readiness | |

**User's choice:** Skip preload for now — add later when max-age is increased

---

## Gzip tuning

### Q1: Enable gzip at nginx level

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, enable gzip | Compresses origin→Cloudflare traffic | ✓ |
| Skip gzip | Rely on Cloudflare for compression | |

**User's choice:** Yes, enable gzip (Recommended)

### Q2: Gzip min response size

| Option | Description | Selected |
|--------|-------------|----------|
| 256 bytes | Standard nginx default | ✓ |
| 1000 bytes | More conservative | |

**User's choice:** 256 bytes (corrected from accidental 1000-byte selection)

### Q3: gzip_proxied

| Option | Description | Selected |
|--------|-------------|----------|
| gzip_proxied any | Compress all responses regardless of proxy headers | ✓ |
| gzip_proxied no-cache no-store private expired | Only compress specific proxied responses | |

**User's choice:** gzip_proxied any (Recommended)

---

## Asset caching

### Q1: Vite-hashed asset cache

| Option | Description | Selected |
|--------|-------------|----------|
| Cache-Control: public, max-age=31536000, immutable | 1-year cache with immutable flag | ✓ |
| Cache-Control: public, max-age=86400 | 1-day cache | |

**User's choice:** Cache-Control: public, max-age=31536000, immutable (Recommended)

### Q2: Non-hashed assets (images, fonts)

| Option | Description | Selected |
|--------|-------------|----------|
| Cache-Control: public, max-age=86400 | 1-day cache | ✓ |
| Cache-Control: public, max-age=604800 | 1-week cache | |
| No special caching | Default browser behavior | |

**User's choice:** Cache-Control: public, max-age=86400 (Recommended)

### Q3: HTML/PHP response cache

| Option | Description | Selected |
|--------|-------------|----------|
| no-cache, no-store, must-revalidate | Always revalidate, no stale dynamic content | ✓ |
| max-age=0, must-revalidate | Similar effect, slightly different semantics | |
| Short max-age (60s) | Cache HTML for 1 minute | |

**User's choice:** no-cache, no-store, must-revalidate (Recommended)

---

## Claude's Discretion

- Exact gzip_types list — follows ROADMAP success criteria
- Cache-Control for API responses (JSON from /api/*)
- Header placement in nginx config (http block vs server block)

## Deferred Ideas

None — discussion stayed within phase scope
