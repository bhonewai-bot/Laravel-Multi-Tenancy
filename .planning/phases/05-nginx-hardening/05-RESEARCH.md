# Phase 5: Nginx Hardening - Research

**Researched:** 2026-06-29
**Status:** Ready for planning

## Current State Analysis

### Dev Config (`docker/nginx/conf.d/app.conf`)
- Basic PHP-FPM proxy setup on port 80
- No security headers
- No gzip configuration
- No caching headers
- Cloudflare custom hostname challenge endpoint at `/.well-known/cf-custom-hostname-challenge/`

### Prod Config (`docker/nginx/conf.d/prod/app.conf`)
- HTTP→HTTPS redirect on port 80
- SSL/HTTP2 on port 443 with Cloudflare origin certs
- SSL session caching and TLS 1.2/1.3 protocols
- No security headers
- No gzip configuration
- No caching headers
- Same Cloudflare hostname challenge endpoint

### Architecture Notes
- Caddy sits in front of nginx in dev (docker-compose.yml) — handles TLS termination
- In prod, nginx handles SSL directly with Cloudflare origin certs
- Nginx bind-mounts `./:/var/www` for static file access (retained from Phase 4 decision)
- Vite builds output to `public/build/` with content hashes (e.g., `app-abc123.css`)

## Research Findings

### 1. CSP with Livewire 4

**Livewire 4 CSP requirements:**
- `script-src 'unsafe-inline' 'unsafe-eval'` — Livewire injects inline scripts and uses eval for dynamic component hydration
- `connect-src 'self'` — Livewire uses fetch() to the same origin for form submissions and component updates
- In dev, Livewire hot-reload uses WebSocket (`ws:` / `wss:`) — needs `connect-src` with WebSocket protocols
- Alpine.js (bundled with Livewire) also needs `'unsafe-inline'` for `x-data` attributes

**Report-Only approach:**
- `Content-Security-Policy-Report-Only` header logs violations to browser console without blocking
- Allows testing CSP policy before enforcement
- Switch to `Content-Security-Policy` after confirming no breakage
- No `report-uri` needed for initial testing — browser console is sufficient

**Dev vs Prod split:**
- Dev: more permissive to allow Vite HMR (WebSocket) and browser dev tools
- Prod: stricter — no WebSocket needed, restrict `connect-src` to `'self'`

### 2. HSTS Configuration

**Current setup:**
- Cloudflare handles TLS at edge for tenant custom domains
- Nginx handles SSL for origin connection (Cloudflare origin certs)
- HSTS header should be set at nginx level (origin)

**Recommended settings (per CONTEXT.md decisions):**
```nginx
add_header Strict-Transport-Security "max-age=300; includeSubDomains" always;
```

- `max-age=300` (5 min) — conservative for first deployment
- `includeSubDomains` — covers `*.central_domain` tenant subdomains
- No `preload` — add later when max-age increased to 1 year
- `always` flag — ensures header is set even on error responses

**Note:** HSTS only effective when served over HTTPS. The HTTP→HTTPS redirect in prod config means the HSTS header on the HTTPS response is what browsers cache.

### 3. Gzip Configuration

**Nginx gzip best practices:**
```nginx
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_min_length 256;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss image/svg+xml;
```

- `gzip_vary on` — adds `Vary: Accept-Encoding` header for proper cache key differentiation
- `gzip_comp_level 6` — good balance of compression ratio vs CPU usage (1-9 scale, 6 is default)
- `gzip_proxied any` — compress all responses regardless of proxy headers
- `gzip_min_length 256` — only compress responses >256 bytes
- `text/html` is always compressed by default (no need to list in `gzip_types`)

**Cloudflare interaction:**
- Cloudflare compresses edge→client automatically
- Nginx gzip compresses origin→Cloudflare (reduces bandwidth between origin and CF edge)
- Both can coexist — no conflict

### 4. Static Asset Caching

**Vite-hashed assets:**
- Vite outputs to `public/build/` with content hashes: `app-abc123.css`, `vendor-def456.js`
- Safe to cache aggressively — URL changes when content changes
- `Cache-Control: public, max-age=31536000, immutable`

**Nginx location matching for Vite assets:**
```nginx
location ~* \.(js|css)$ {
    add_header Cache-Control "public, max-age=31536000, immutable";
}
```

**Non-hashed assets (images, fonts):**
- Files in `public/` without content hashes (logos, favicons, fonts)
- `Cache-Control: public, max-age=86400` (1-day cache)

**Nginx location for images/fonts:**
```nginx
location ~* \.(jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf|eot)$ {
    add_header Cache-Control "public, max-age=86400";
}
```

**HTML/PHP responses:**
- Dynamic content — should not be cached
- `Cache-Control: no-cache, no-store, must-revalidate`
- Applied to PHP location block

### 5. Security Headers Summary

**Headers to add (all responses):**
```nginx
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

**server_tokens:**
```nginx
server_tokens off;
```
- Hides nginx version from `Server` response header and error pages
- Set in `nginx.conf` or in the server block

### 6. Header Placement Strategy

**Important:** `add_header` in nginx only applies if no other `add_header` exists in the same context. If the PHP location block has its own `add_header` directives, the server-level headers won't apply to PHP responses.

**Solution:** Use the `always` flag and ensure headers are set at the server level, or use `ngx_headers_more` module (not available in standard nginx:alpine).

**Alternative approach:** Set common headers in a shared include file, or set them at the `http` level in a custom `nginx.conf`.

### 7. Potential Issues

**Livewire SSE/WebSocket in dev:**
- Livewire 4 uses Server-Sent Events (SSE) by default, not WebSocket
- SSE needs `connect-src 'self'` (already covered in prod CSP)
- Dev CSP should allow `connect-src 'self' ws: wss:` for Vite HMR

**Cloudflare custom hostname challenge:**
- The `/.well-known/cf-custom-hostname-challenge/` location must remain accessible
- Security headers won't interfere with this endpoint

**Header inheritance:**
- `add_header` in a location block overrides server-level headers
- Need to ensure all location blocks either inherit or explicitly set the required headers
- Use `always` flag to ensure headers are set on error responses too

## Recommended Nginx Config Structure

### Dev Config (`app.conf`)
```nginx
server {
    listen 80;
    server_name _;
    root /var/www/public;
    index index.php index.html;

    # Security headers (dev - permissive CSP)
    add_header Content-Security-Policy-Report-Only "script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self' ws: wss:;" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_min_length 256;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml image/svg+xml;

    # server_tokens off (nginx.conf level)

    location ^~ /.well-known/cf-custom-hostname-challenge/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        # HTML caching for PHP
        add_header Cache-Control "no-cache, no-store, must-revalidate" always;
    }

    # Vite-hashed assets (JS/CSS)
    location ~* \.(js|css)$ {
        add_header Cache-Control "public, max-age=31536000, immutable";
    }

    # Non-hashed static assets
    location ~* \.(jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf|eot)$ {
        add_header Cache-Control "public, max-age=86400";
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### Prod Config (`prod/app.conf`)
- Same structure but with stricter CSP: `connect-src 'self'` (no ws:/wss:)
- SSL/HTTP2 configuration retained
- HTTP→HTTPS redirect retained

## Downstream Notes for Planner

1. **`server_tokens off`** must be set in `nginx.conf` or via a custom nginx config — not in the server block alone. Consider creating `docker/nginx/nginx.conf` or adding to the existing config.

2. **Header inheritance issue:** If PHP location block has its own `add_header`, server-level headers won't apply to PHP responses. The planner needs to decide: either set all headers at server level (and ensure no location-level overrides), or set headers in each location block.

3. **Dev vs Prod config files:** Both files need similar structure but different CSP policies. Consider extracting common configuration into a shared include file to avoid duplication.

4. **Vite asset location:** The `location ~* \.(js|css)$` block should be placed BEFORE the PHP location block to ensure Vite assets are served with cache headers, not processed by PHP.

---

*Phase: 5-Nginx Hardening*
*Researched: 2026-06-29*
