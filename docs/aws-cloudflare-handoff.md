# AWS + Cloudflare Custom Domain Handoff

Last updated: 2026-03-18 (Asia/Yangon)

This file is the restart document for continuing the Laravel multi-tenancy deployment thread in a new chat.

This document now reflects the latest production state after the EC2 + Cloudflare + tenant-domain activation work was pushed through to a usable end-to-end flow.

## 1) Repo and Goal

- Working repo: `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy`
- Main goal: make Cloudflare Custom Hostnames work against a real public origin for the Laravel app
- Local implementation status: app-side custom-domain lifecycle is done enough for production usage
- Current focus is production cleanup/hardening, not origin reachability

## 2) Current App Status

Completed on the Laravel side:

1. Cloudflare config wiring
2. Domain table `cf_*` fields
3. Cloudflare service integration
4. Domain create -> Cloudflare create flow
5. `check-status` endpoint
6. `verified_at`-based domain gating
7. Feature tests for status sync / tenancy flow
8. Tenant custom-domain UI exists and is usable

Completed in this AWS thread:

1. EC2 instance created in `ap-southeast-2`
2. Security group allows:
   - SSH `22` from user IP only
   - HTTP `80` from internet
   - HTTPS `443` from internet
3. Elastic IP allocated and associated
4. Cloudflare DNS `proxy-fallback.bhonewai.cc.cd` now points to the EC2 Elastic IP and is proxied
5. Cloudflare Fallback Origin is active on `proxy-fallback.bhonewai.cc.cd`
6. SSH to EC2 works
7. Docker and Docker Compose installed on EC2
8. Repo cloned onto EC2
9. Cloudflare Origin Certificate created and placed on EC2
10. Production stack is running behind nginx on EC2
11. Central app is reachable via:
    - `https://proxy-fallback.bhonewai.cc.cd/login`

Current behavior:

- Central login page loads successfully through Cloudflare
- Verified tenant custom domains load successfully through Cloudflare
- Earlier origin errors improved:
  - `525` -> fixed by moving to AWS and proper origin cert
  - `521` -> fixed by getting nginx/app running on EC2
  - custom-hostname HTTP validation failures -> fixed by serving the Cloudflare challenge route on pending custom domains
- Tenant-domain activation is now working through the in-app `Check Status` flow

Earlier handoff:

- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docs/cloudflare-handoff-backup.md`

Use that file for the earlier implementation timeline.

## 3) Root Issue We Traced

The failure is:

- Cloudflare returns `525 SSL handshake failed` for:
  - `https://proxy-fallback.bhonewai.cc.cd`
  - tenant custom domains like `https://audit.bhonewai.cc.cd`

What we proved:

1. Cloudflare custom hostname creation/status flow is not the main problem anymore.
2. Local Caddy could serve `proxy-fallback.bhonewai.cc.cd` on `127.0.0.1`.
3. The public IP path was not reaching the Laravel origin correctly.
4. The home router/public network path is the real blocker.

Important findings:

- Mac LAN IP: `192.168.1.18`
- Public IP detected from home network: `182.53.101.190`
- The earlier public IP `216.198.79.1` was serving the Vercel site for `bhonewai.cc.cd`, not the Laravel origin.
- Trying to use the home public IP led to the router/admin path, not the app origin.

Conclusion from the earlier phase:

- The problem was not mainly Cloudflare DNS.
- The problem was no stable, internet-reachable Laravel/Nginx origin for the fallback hostname.

Current status after AWS work:

- That origin problem is now substantially solved.
- Cloudflare can reach the origin.
- nginx and php-fpm are serving traffic on EC2.

## 4) Deployment Decision

We decided to stop trying to use the local Mac as the public fallback origin.

New direction:

1. Use a VPS/public server
2. Point `proxy-fallback.bhonewai.cc.cd` to that server
3. Keep Cloudflare Custom Hostnames fallback origin on that hostname

User update:

- DigitalOcean purchase failed because the prepaid Visa card was rejected
- User now has an AWS trial account

## 5) AWS Direction

Target architecture for AWS:

- `EC2` instance as public origin
- `Elastic IP` attached to EC2
- `proxy-fallback.bhonewai.cc.cd` -> Elastic IP
- Cloudflare fallback origin remains `proxy-fallback.bhonewai.cc.cd`

Reason for Elastic IP:

- Cloudflare fallback origin needs a stable public IP
- default EC2 public IPv4 is not stable across stop/start

Operational note:

- If the instance is no longer used, release the Elastic IP to avoid ongoing IPv4 charges

Actual deployed values:

- EC2 public Elastic IP: `16.176.238.35`
- Earlier ephemeral public IP seen before Elastic IP: `54.66.211.217`
- Current SSH target:
  - `ssh -i /Users/appleclub/Documents/AWS/bhonewai-ec2-sydney.pem ubuntu@16.176.238.35`

## 6) Repo Production Path to Use

Do **not** use local Caddy for AWS deployment.

Production path already exists in the repo:

- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docker-compose.prod.yml`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docker/nginx/conf.d/prod/app.conf`

Important production assumptions already in code:

1. `nginx` is the public origin
2. `app` runs behind nginx
3. `queue` worker runs separately
4. No Caddy in production compose
5. Nginx expects origin TLS cert files at:
   - `/etc/nginx/ssl/origin.crt`
   - `/etc/nginx/ssl/origin.key`

Important clarification from the deployment thread:

- `docker compose up -d` by itself uses `docker-compose.yml` and is the wrong path for AWS
- AWS deploy must use:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

## 7) Production Gap Still To Decide

`docker-compose.prod.yml` now includes:

- `app`
- `nginx`
- `queue`
- `mysql`

So the current production shape is:

- EC2 + app/nginx/queue + MySQL container on the same box

The architecture decision still left for later is:

1. Keep MySQL container on EC2 for the near term
2. Move to `RDS MySQL` for a cleaner long-term setup

What happened after the temporary sqlite phase:

- Production was moved toward MySQL-backed runtime on EC2
- During this migration, queue/cache/session database-backed settings caused instability while the central schema/runtime were being cleaned up
- The app was stabilized with temporary simpler runtime choices for production operations:
  - `CACHE_STORE=file`
  - `SESSION_DRIVER=file`
  - queue/database-backed background polling should not currently be treated as the source of truth for domain activation

Important current operating note:

- The trusted production activation flow is now:
  1. create tenant/domain in the app
  2. add or confirm Cloudflare DNS/custom-hostname setup
  3. use the tenant-side `Check Status` action
  4. once hostname + SSL show active, the domain becomes verified and starts serving traffic

## 8) Domain / DNS Model

Keep this separation:

- `bhonewai.cc.cd` can stay wherever it already lives, including Vercel
- `proxy-fallback.bhonewai.cc.cd` should point to the Laravel origin on AWS

Do **not** point fallback origin to Vercel if custom-domain traffic is supposed to land on Laravel.

One fallback-origin hostname is enough for many tenant custom domains.

Current Cloudflare state:

- `proxy-fallback.bhonewai.cc.cd` -> `16.176.238.35` and proxied
- Fallback Origin status in Cloudflare Custom Hostnames: `Active`
- Verified custom hostnames observed working:
  - `sale.bhonewai.cc.cd`
  - `tenant.bhonewai.cc.cd`
  - `staff.bhonewai.cc.cd`
- DNS should be managed in Cloudflare, not DNSHE
- DNSHE is effectively just the registrar / nameserver delegation point now

## 9) Known Local Infra Facts

Commands already run:

```bash
ipconfig getifaddr en0
curl ifconfig.me
lsof -nP -iTCP:443 -sTCP:LISTEN
```

Observed values:

- Mac LAN IP: `192.168.1.18`
- Home public IP: `182.53.101.190`
- Docker was listening locally on `443`

But the home network path was not suitable for a reliable public origin.

## 10) Current Deployment State On EC2

Confirmed working:

1. Docker installed
2. Docker Compose installed
3. Repo cloned to:
   - `~/Laravel-Multi-Tenancy`
4. Cloudflare Origin Certificate files created on EC2:
   - `docker/nginx/ssl/origin.crt`
   - `docker/nginx/ssl/origin.key`
5. nginx container starts and serves HTTPS
6. app container starts and handles requests
7. Central app login page renders through Cloudflare

Relevant current env shape on EC2:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://proxy-fallback.bhonewai.cc.cd`
- `TENANCY_CENTRAL_DOMAIN=proxy-fallback.bhonewai.cc.cd`
- `DB_CONNECTION=mysql`
- `DB_HOST=mysql`
- `DB_PORT=3306`
- `DB_DATABASE=central`
- `CLOUDFLARE_ENABLED=true`
- `CLOUDFLARE_FALLBACK_ORIGIN=proxy-fallback.bhonewai.cc.cd`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`

Reason for the current `file` choices:

- DB-backed cache/session/queue assumptions were causing queue/runtime instability while production schema state was being repaired
- The current production-safe path is to keep the user-facing app stable first, then return later to DB-backed hardening

Observed running containers:

- `laravel-multi-tenancy-app-1`
- `laravel-multi-tenancy-nginx-1`
- `laravel-multi-tenancy-queue-1`

## 11) Important Repo Changes That Now Matter In Production

These repo-side changes are important to the current working tenant-domain flow:

1. Cloudflare HTTP hostname challenge support
   - `app/Http/Controllers/CloudflareHostnameChallengeController.php`
   - host-agnostic route registration in `bootstrap/app.php`
2. Cloudflare sync extraction / reuse
   - `app/Services/DomainCloudflareSyncService.php`
3. Pending-domain polling support
   - `app/Jobs/SyncPendingCloudflareDomain.php`
   - controller dispatch/update logic in `app/Http/Controllers/Tenant/DomainController.php`
4. Production nginx behavior for challenge handling
   - `docker/nginx/conf.d/prod/app.conf`

Important nuance:

- The queue-backed delayed polling path exists in code
- but production should currently rely on the in-app `Check Status` flow rather than the background queue path

## 12) Current App-Level Findings

Central app:

- `https://proxy-fallback.bhonewai.cc.cd/login` works
- A user with email `superadmin@gmail.com` exists in the central `users` table
- The user was created manually through the register route during debugging
- Earlier expected seeded credentials were not reliably present

Seeding details:

- `DatabaseSeeder` calls:
  - `SuperAdminSeeder`
  - `ModuleSeeder`
- `SuperAdminSeeder` uses env-driven credentials:
  - `CENTRAL_SUPERADMIN_EMAIL`
  - `CENTRAL_SUPERADMIN_PASSWORD`

Tenant-domain state:

- Tenant custom domains are now verified through the app/UI flow once Cloudflare is active
- Confirmed working examples during validation:
  - `sale.bhonewai.cc.cd`
  - `tenant.bhonewai.cc.cd`
  - `staff.bhonewai.cc.cd`
- The domain detail/setup page shows:
  - hostname routing status
  - SSL status
  - verification status
  - Cloudflare hostname ID
  - a `Check Status` action for pending domains

Important clarification:

- `proxy-fallback.bhonewai.cc.cd` is currently acting as the **central domain**
- It is also the Cloudflare fallback origin hostname
- This is acceptable for now

## 13) Current Remaining Problem

The remaining blocker is no longer tenant-domain activation itself.

The current cleanup/hardening backlog is:

1. DB-backed queue/cache/session support still needs proper production hardening
2. Telescope should be made production-safe without manual/provider hacks
3. Background polling for pending Cloudflare domains should be revisited only after queue/runtime hardening is stable
4. More production-like tests are still needed for activation/sync behavior

## 14) Immediate Next Steps In A New Thread

Do this next:

1. Properly harden the MySQL-backed production runtime:
   - queue
   - cache
   - session
2. Decide whether to keep MySQL-on-EC2 for the near term or move to RDS next
3. Clean up Telescope so production and local/dev are separated properly
4. Add stronger production-like test coverage for Cloudflare activation and sync
5. Optionally improve the tenant domain list page so `Check Status` is one click more obvious

## 15) Suggested Prompt For New Thread

Use this:

```text
Continue from:
- docs/cloudflare-handoff-backup.md
- docs/aws-cloudflare-handoff.md

Current deployed state:
- AWS EC2 origin is live behind Cloudflare
- proxy-fallback.bhonewai.cc.cd works as the central domain and fallback origin
- central flows work
- tenant CRUD/module/role/user/domain flows work
- verified tenant custom domains work
- tenant admins can use the in-app `Check Status` flow to make pending custom domains ready

Remaining work:
1. clean up production queue/cache/session hardening on MySQL
2. make Telescope production-safe
3. add production-like tests for Cloudflare activation/sync
4. decide whether to keep MySQL container on EC2 or move to RDS next
```
## 16) Files Most Relevant For The Next Thread

- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docker-compose.prod.yml`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/DockerFile`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/bootstrap/providers.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docker/nginx/conf.d/prod/app.conf`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/config/cloudflare.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/config/tenancy.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Services/CloudflareService.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Http/Controllers/Tenant/DomainController.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Http/Controllers/CloudflareHostnameChallengeController.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Services/DomainCloudflareSyncService.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Jobs/SyncPendingCloudflareDomain.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/database/seeders/DatabaseSeeder.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/database/seeders/SuperAdminSeeder.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docs/cloudflare-handoff-backup.md`
