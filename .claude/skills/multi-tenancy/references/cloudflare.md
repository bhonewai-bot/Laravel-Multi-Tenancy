# Cloudflare SSL for SaaS Flow

## Overview

This application uses Cloudflare SSL for SaaS to issue SSL certificates for tenant custom domains. Tenants can bring ANY domain (e.g., `shop.acmecorp.com`) as long as it's within the configured Cloudflare zone. The tenant CNAMEs their domain to the fallback origin (`proxy-fallback.bhonewai.cc.cd`), and Cloudflare handles SSL issuance automatically.

## Configuration

```php
// config/cloudflare.php
'enabled'         => (bool) env('CLOUDFLARE_ENABLED', false),
'async_polling'   => (bool) env('CLOUDFLARE_ASYNC_POLLING', false),
'api.base_url'    => 'https://api.cloudflare.com/client/v4',
'api.token'       => env('CLOUDFLARE_API_TOKEN'),
'api.zone_id'     => env('CLOUDFLARE_ZONE_ID'),
'api.timeout'     => 15,
'api.retry_times'  => 2,
'fallback_origin' => env('CLOUDFLARE_FALLBACK_ORIGIN'),
'validation_method' => 'http',
```

**.env keys:**

```
CLOUDFLARE_ENABLED=true
CLOUDFLARE_API_TOKEN=<token>
CLOUDFLARE_ZONE_ID=9b44b1e57ce8155ace9300619534a042
CLOUDFLARE_FALLBACK_ORIGIN=proxy-fallback.bhonewai.cc.cd
CLOUDFLARE_ASYNC_POLLING=false
CLOUDFLARE_VALIDATION_METHOD=http
CLOUDFLARE_ZONE_DOMAIN=bhonewai.cc.cd
```

**Zone:** `bhonewai.cc.cd` — all custom hostnames must be subdomains of this zone.

## End-to-end flow

### Phase A — Domain creation (DomainController::store)

```
1. Tenant POSTs {domain: "shop.acmecorp.com"} to tenant.domains.store

2. Validation:
   - TenantDomainService::isValidDomain()       — no protocol, no path, valid hostname
   - TenantDomainService::isCentralDomain()     — must not be central domain
   - TenantDomainService::isPrimarySubDomain()  — must not be primary subdomain
   - Unique on central domains table

3. Domain record saved:
   - tenant_id, domain
   - verified_at = null
   - verification_code = null

4. If config('cloudflare.enabled') === true:
   → DomainCloudflareSyncService::sync($domain, createWhenMissing: true)

5. If config('cloudflare.async_polling') === true AND shouldRetry($domain):
   → SyncPendingCloudflareDomain::dispatch($domain->id)
```

### Phase B — DomainCloudflareSyncService::sync()

```
1. Determine action:
   - cf_hostname_id exists → refresh (GET /zones/{id}/custom_hostnames/{hostname_id})
   - cf_hostname_id null   → create  (POST /zones/{id}/custom_hostnames)

2. CloudflareService::createHostname($domain->domain)
   - ensureConfigured()        — validates token and zone_id are set
   - POST /zones/{zoneId}/custom_hostnames
   - Body: {hostname, ssl: {method: 'http', type: 'dv'}}
   - Returns Cloudflare response JSON

3. CloudflareService::mapStatuses() parses response into:
   - cf_hostname_id
   - cf_hostname_status     (active / pending / deleted)
   - cf_ssl_status          (active / pending / deleted)
   - cf_error               (null or error message)
   - cf_payload             (full JSON response)

4. Domain record updated:
   - fill(attributes)
   - cf_last_checked_at = now()

5. Verification check:
   shouldMarkVerified():
     cf_hostname_status === 'active' && cf_ssl_status === 'active'
     → YES → verified_at = now()
     → NO  → verified_at = null
```

### Phase C — SyncPendingCloudflareDomain job (polling)

```
MAX_ATTEMPTS = 15
RETRY_DELAY_SECONDS = 120  (2 minutes)
TOTAL WINDOW = 30 minutes

constructor(int $domainId, int $pollAttempt = 1)

handle():
  1. Find Domain by ID
     - Not found → return (domain was deleted)
     - Already verified → return (done)

  2. $syncService->sync($domain)
     - Refreshes Cloudflare state
     - May set verified_at if hostname + SSL are active

  3. If pollAttempt < 15 AND shouldRetry($domain):
     - Dispatch self with delay(now()->addSeconds(120))
     - Increment pollAttempt

shouldRetry($domain):
  - cf_hostname_id !== null      (hostname was created)
  - verified_at === null         (not yet verified)
  - cf_error === null            (no terminal error)

failed():
  - Sets cf_error = exception message on the domain
  - Logs the failure
```

### Phase D — HTTP validation challenge

```
1. Cloudflare requests GET /.well-known/cf-custom-hostname-challenge/{hostnameId}

2. CloudflareHostnameChallengeController::__invoke()
   - Looks up Domain by request hostname
   - Verifies cf_payload.result.id === route hostnameId
   - Returns cf_payload.result.ownership_verification_http.http_body

3. Cloudflare validates the challenge → begins SSL issuance
```

### Phase E — Legacy DNS TXT flow (non-Cloudflare)

```
1. Tenant calls DomainController::verify()

2. If domain has cf_hostname_id:
   → delegates to checkStatus() (Cloudflare path)
   → Otherwise: generates verification_code (40-char random)

3. TenantDomainService::checkDnsTxtVerification()
   - Looks up _tenant-verification.{domain} TXT record via dns_get_record()
   - Compares to stored verification_code

4. Match → verified_at = now()
```

## Key services

### CloudflareService (`app/Services/CloudflareService.php`)

- `ensureConfigured()` — validates token and zone_id are non-empty
- `createHostname(string $hostname)` — POST custom hostname. Wraps Cloudflare HTTP errors (including `RequestException`) in `RuntimeException` with the extracted error message.
- `getHostname(string $hostnameId)` — GET custom hostname status
- `mapStatuses(array $response)` — parses API response → local fields (alias: `mapStatus`)
- **Design note:** Cloudflare SSL for SaaS is designed for tenants to bring ANY valid domain. No zone-domain validation is needed or wanted — Cloudflare handles rejection with error codes the service already extracts.

### DomainCloudflareSyncService (`app/Services/DomainCloudflareSyncService.php`)

- `sync(Domain $domain, bool $createWhenMissing)` — THE source of truth for sync
- `shouldRetry(Domain $domain): bool` — checks if polling should continue
- `shouldMarkVerified(array $statuses): bool` — checks both statuses are 'active'
- `createHostname(Domain $domain)` — delegates to CloudflareService
- `refreshHostname(Domain $domain)` — delegates to CloudflareService
- `updateDomain(Domain $domain, array $attributes)` — persists to DB

## Artisan command

```
php artisan domains:sync-cloudflare {domain}
```

- Accepts domain ID or hostname
- Currently duplicates sync logic (calls CloudflareService directly)
- Should be refactored to delegate to DomainCloudflareSyncService

## Error handling

| Error | Meaning | What to do |
|-------|---------|-----------|
| `cf_error` from Cloudflare API | Cloudflare rejected the hostname | Read the message — may be reserved domain, CAA issue, etc. |
| `cf_error = "timeout"` | Network/API timeout | Transient — should retry |
| `cf_error = "CAA record"` | DNS CAA blocks Cloudflare | Tenant must update DNS |
| `cf_error = null, cf_hostname_status = "pending"` | Still provisioning | Wait. Normal for first 2-5 min |
| `shouldMarkVerified = false but both active` | Bug or race condition | Check sync was called, cf_last_checked_at |

## Error handling in CloudflareService

`createHostname()` wraps HTTP calls in try/catch to convert `Illuminate\Http\Client\RequestException` into `RuntimeException` with the extracted Cloudflare error message. This means all Cloudflare API errors surface as clean `RuntimeException` — the controller's try/catch already handles this pattern.

Reserved domains (like `example.com` per RFC 2606) will be rejected by Cloudflare, not because they're outside the zone, but because they're reserved for documentation/testing. The error message from Cloudflare explains the reason.

## Local development

- `CLOUDFLARE_ENABLED=true` + localhost → all API calls fail (Cloudflare can't reach localhost)
- For local dev: set `CLOUDFLARE_ENABLED=false`
- Platform subdomains (`t001.app.localhost`) work without Cloudflare
- The test suite uses SQLite in-memory and mocks CloudflareService — no real API calls
