# AWS + Cloudflare Custom Domain Handoff

Last updated: 2026-03-12 (Asia/Yangon)

This file is the restart document for continuing the Laravel multi-tenancy deployment thread in a new chat.

## 1) Repo and Goal

- Working repo: `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy`
- Main goal: make Cloudflare Custom Hostnames work against a real public origin for the Laravel app
- Local implementation status: app-side custom-domain lifecycle is mostly done
- Current blocker: tenant-domain activation/verification and production repo cleanup, not AWS reachability

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
- Earlier origin errors improved:
  - `525` -> fixed by moving to AWS and proper origin cert
  - `521` -> fixed by getting nginx/app running on EC2
- Current remaining issue is tenant-domain state, not origin reachability

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

`docker-compose.prod.yml` currently has:

- `app`
- `nginx`
- `queue`

It does **not** currently include:

- `mysql`

So production needs one of these choices:

1. Use `RDS MySQL`
2. Add a MySQL container for a cheaper single-server setup

Fastest path:

- start with EC2 + app/nginx/queue + MySQL container on the same box

Cleaner path:

- EC2 + app/nginx/queue
- RDS MySQL

What was actually done temporarily on EC2:

- To get the central app up quickly, EC2 was configured to use `sqlite`
- This required temporary runtime/build fixes on the EC2 clone
- This is a deployment workaround, not yet a cleaned-up committed production design

Important warning:

- The EC2 clone has manual changes that are **not yet committed back to the repo**
- If the server is rebuilt from the current repo state, the same issues will come back unless those fixes are committed

## 8) Domain / DNS Model

Keep this separation:

- `bhonewai.cc.cd` can stay wherever it already lives, including Vercel
- `proxy-fallback.bhonewai.cc.cd` should point to the Laravel origin on AWS

Do **not** point fallback origin to Vercel if custom-domain traffic is supposed to land on Laravel.

One fallback-origin hostname is enough for many tenant custom domains.

Current Cloudflare state:

- `proxy-fallback.bhonewai.cc.cd` -> `16.176.238.35` and proxied
- Fallback Origin status in Cloudflare Custom Hostnames: `Active`
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
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=/var/www/database/database.sqlite`
- `CLOUDFLARE_ENABLED=true`
- `CLOUDFLARE_FALLBACK_ORIGIN=proxy-fallback.bhonewai.cc.cd`
- `SESSION_DRIVER=file` was switched during debugging to avoid session-table issues

Observed running containers:

- `laravel-multi-tenancy-app-1`
- `laravel-multi-tenancy-nginx-1`
- `laravel-multi-tenancy-queue-1`

## 11) Important Manual EC2-Only Fixes Not Yet Committed

These were made directly on the EC2 clone to get the app up:

1. `DockerFile`
   - added `libsqlite3-dev`
   - added `pkg-config`
   - changed extension install concurrency from `-j$(nproc)` to `-j1`
   - added `pdo_sqlite`
   - removed the attempted `sqlite3` extension install after it broke the build
2. `bootstrap/providers.php`
   - removed `App\Providers\TelescopeServiceProvider::class` for production because `composer install --no-dev` does not install Telescope

This means the repo should be updated to persist these fixes.

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

- Current `domains` table state observed on EC2:

```php
[
  [
    "id" => 1,
    "tenant_id" => "t001",
    "domain" => "delivery.bhonewai.cc.cd",
    "verified_at" => null,
    "cf_hostname_status" => null,
    "cf_ssl_status" => null,
  ],
]
```

Meaning:

- Only one tenant domain record exists right now: `delivery.bhonewai.cc.cd`
- It is **not verified**
- Cloudflare sync fields are still null
- So tenant routing is not active yet for that domain

Important clarification:

- `proxy-fallback.bhonewai.cc.cd` is currently acting as the **central domain**
- It is also the Cloudflare fallback origin hostname
- This is acceptable for now

## 13) Current Remaining Problem

The remaining blocker is no longer AWS origin reachability.

The current blocker is:

1. tenant domains are not active / verified in the application
2. repo production configuration changes made on EC2 are not yet committed back
3. production DB strategy is still temporary (`sqlite`)

Observed tenancy error from logs:

- `Tenant could not be identified on domain www.tutoroo.co`

Interpretation:

- that request reached Laravel successfully
- but there is no matching active domain record for `www.tutoroo.co`
- so this is now an application/domain-data issue, not an origin problem

## 14) Immediate Next Steps In A New Thread

Do this next:

1. Commit the EC2-only fixes back into the repo:
   - `DockerFile`
   - `bootstrap/providers.php`
2. Decide whether to keep temporary sqlite for now or move to MySQL/RDS immediately
3. Inspect tenant/domain provisioning flow on the deployed server:
   - why `cf_hostname_status`, `cf_ssl_status`, and `verified_at` remain null
4. Verify EC2 `.env` has valid Cloudflare API values:
   - `CLOUDFLARE_API_TOKEN`
   - `CLOUDFLARE_ZONE_ID`
   - `CLOUDFLARE_ENABLED=true`
5. Re-test domain creation / `check-status` from the deployed central app
6. Confirm whether `delivery.bhonewai.cc.cd` should become the first working tenant domain
7. If needed, run seeding explicitly and normalize central admin credentials
8. Clean up Telescope so production and local/dev are separated properly instead of requiring manual provider removal

## 15) Suggested Prompt For New Thread

Use this:

```text
Continue from:
- docs/cloudflare-handoff-backup.md
- docs/aws-cloudflare-handoff.md

Current deployed state:
- AWS EC2 origin is live behind Cloudflare
- proxy-fallback.bhonewai.cc.cd works as the central domain and fallback origin
- central login page is reachable in production
- nginx/app/queue are running on EC2
- remaining issue is tenant-domain activation, not AWS reachability

Important:
- the EC2 clone has manual fixes not yet committed back to the repo
- DockerFile was changed for sqlite support / lower-memory build
- bootstrap/providers.php was changed to remove Telescope provider in production

Please help me:
1. turn the EC2-only fixes into proper repo changes
2. verify Cloudflare custom-domain status sync on the deployed server
3. make delivery.bhonewai.cc.cd become an active verified tenant domain
4. decide whether to stay on temporary sqlite or move to MySQL/RDS next
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
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/database/seeders/DatabaseSeeder.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/database/seeders/SuperAdminSeeder.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docs/cloudflare-handoff-backup.md`
