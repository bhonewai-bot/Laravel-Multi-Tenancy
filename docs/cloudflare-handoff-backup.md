# AWS + Cloudflare + Tenant Domain Handoff

Last updated: 2026-03-17 (Asia/Yangon)

## Repo
- Working repo: `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy`
- Earlier reference repo: `/Users/appleclub/Documents/Professional Product Lab/cloudflare/lara-ums`

## Main Goal
- Deploy the Laravel multi-tenancy app to AWS EC2 behind Cloudflare
- Make Cloudflare Custom Hostnames / tenant custom domains work against a real public origin
- Remove the need for manual domain sync commands in normal app flow

## High-Level Progress
What is already working:
- AWS EC2 instance was created in `ap-southeast-2`
- Elastic IP was allocated and attached
- Cloudflare DNS `proxy-fallback.bhonewai.cc.cd` was pointed to the EC2 Elastic IP and proxied
- Cloudflare fallback origin was set to `proxy-fallback.bhonewai.cc.cd`
- Cloudflare fallback origin status became active
- SSH to EC2 worked previously
- Docker + Docker Compose were installed on EC2
- Repo was cloned onto EC2
- Cloudflare Origin Certificate was created and placed on EC2
- `docker-compose.prod.yml` path was used for the real AWS deployment
- nginx/app/queue came up on EC2
- Earlier Cloudflare errors improved:
  - `525` -> fixed by real AWS origin + origin cert
  - `521` -> fixed by getting nginx/app actually serving
- Central app is now reachable in production at:
  - `https://proxy-fallback.bhonewai.cc.cd/login`

What is not finished yet:
- EC2-only deployment fixes were not yet committed back into the repo
- Production DB strategy is still temporary (`sqlite`)
- SSH access from local Mac is currently timing out again and likely needs AWS SG/My IP review

## Important Root Cause History
Earlier traced issue:
- `525 SSL handshake failed` was not mainly about Laravel CRUD logic
- it was caused by origin reachability / origin TLS
- the home-router/local-Mac path was abandoned because it was not a stable public origin

Current situation:
- origin reachability is mostly solved
- app is deployed and reachable centrally
- remaining problem is tenant-domain activation / verification / sync

## Current AWS Architecture
- EC2 instance in AWS `ap-southeast-2`
- Elastic IP: `16.176.238.35`
- Central/fallback hostname:
  - `proxy-fallback.bhonewai.cc.cd`
- Cloudflare fallback origin:
  - `proxy-fallback.bhonewai.cc.cd`

Meaning:
- `proxy-fallback.bhonewai.cc.cd` is currently serving as:
  1. the central/admin domain
  2. the Cloudflare fallback origin hostname

This is acceptable for now.

## Cloudflare / DNS State
Cloudflare:
- `A proxy-fallback.bhonewai.cc.cd -> 16.176.238.35`
- proxied = on
- fallback origin status = active

DNSHE:
- acts as registrar / nameserver delegation only
- live DNS changes should be managed in Cloudflare, not DNSHE

## Current Production Runtime On EC2
Stack:
- `app`
- `nginx`
- `queue`

Central app:
- login page loads via Cloudflare
- manual user registration worked
- `superadmin@gmail.com` now exists in central DB

Observed container health:
- nginx running
- php-fpm running
- queue running
- `php artisan about` works
- `php artisan route:list` works

## Important EC2-Only Fixes That Were Made
These were made directly on the EC2 clone and are NOT yet committed back into the repo.

### 1. DockerFile changes
Changes made on EC2:
- added `libsqlite3-dev`
- added `pkg-config`
- changed extension install concurrency from `-j$(nproc)` to `-j1`
- added `pdo_sqlite`
- removed attempted `sqlite3` extension install
- kept `pdo_mysql`, `bcmath`, `intl`, `zip`, `gd`

Reason:
- needed sqlite support for temporary production DB
- reduced build pressure on small EC2 instance
- `sqlite3` extension install caused build failure (`Cannot find config.m4`)

### 2. bootstrap/providers.php
Change made on EC2:
- removed `App\Providers\TelescopeServiceProvider::class`

Reason:
- production install used `composer install --no-dev`
- Telescope package is dev-only
- leaving provider enabled caused runtime/bootstrap failure

These two file changes need to be copied back into the local repo and committed.

## Current Temporary Production Env Shape
On EC2, production was adjusted roughly like this:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://proxy-fallback.bhonewai.cc.cd`
- `TENANCY_CENTRAL_DOMAIN=proxy-fallback.bhonewai.cc.cd`
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=/var/www/database/database.sqlite`
- `CLOUDFLARE_ENABLED=true`
- `CLOUDFLARE_FALLBACK_ORIGIN=proxy-fallback.bhonewai.cc.cd`
- `SESSION_DRIVER=file` was used during debugging

Important:
- sqlite is only a temporary workaround
- long-term production DB still needs a decision:
  - MySQL container
  - or RDS MySQL

## Tenant Domain Current State
Current `domains` table on deployed app showed:

- one record:
  - `domain = delivery.bhonewai.cc.cd`
  - `tenant_id = t001`
  - `verified_at = null`
  - `cf_hostname_status = null`
  - `cf_ssl_status = null`

Meaning:
- tenant custom-domain DB flow exists
- but this domain is not yet active/verified
- Cloudflare sync fields are not populated
- tenant custom domain is not fully live yet

There was also a request seen for:
- `www.tutoroo.co`

Laravel log showed:
- `Tenant could not be identified on domain www.tutoroo.co`

Interpretation:
- request successfully reached Laravel
- but there was no matching active tenant domain record
- so this is now an application/domain-data issue, not an origin/TLS issue

## Central Login / Seeder Notes
Observed:
- `superadmin@gmail.com` did not exist initially or was not usable as expected
- user was manually created by using register flow
- current tinker check confirmed:
  - user exists in sqlite central DB
  - email = `superadmin@gmail.com`

Seeder notes:
- `DatabaseSeeder` calls:
  - `SuperAdminSeeder`
  - `ModuleSeeder`
- `SuperAdminSeeder` uses env values:
  - `CENTRAL_SUPERADMIN_EMAIL`
  - `CENTRAL_SUPERADMIN_PASSWORD`
  - `CENTRAL_SUPERADMIN_NAME`

Potential follow-up:
- normalize central admin credentials / seeding so it is repeatable

## lara-ums Reference Behavior
Reference branch studied:
- `origin/dev/cloudflare-custom-hostnames`

How `lara-ums` handled domain flow:
- on domain create, controller immediately called Cloudflare create
- domain detail page showed DNS instructions
- user manually clicked verify/check-status
- controller polled Cloudflare and updated hostname/SSL status
- domain considered live only when both hostname and SSL were active

Difference from current project:
- current project direction is more automatic
- goal is to avoid manual Artisan sync commands like:
  - `docker compose -f docker-compose.prod.yml exec -T app php artisan domains:sync-cloudflare rift.bhonewai.cc.cd`

## Current User Direction / Decisions
User intent stated:
- `syncCloudflareForDomain()` was moved into controller/app flow to avoid manual sync commands
- user is aware SSL automation could later be improved (e.g. Cloudflare for SaaS / Let's Encrypt / Certbot on origin)
- user wants to revisit GitHub Actions later for CI/CD:
  - push -> build -> test -> deploy to AWS
- immediate focus is NOT auto-deploy yet
- immediate focus is:
  1. commit EC2-only fixes back into repo
  2. continue tenant-domain activation/debugging

## Current Blocking Issue Right Now
The newest operational issue:
- SSH from local Mac to EC2 is timing out again:
  - `ssh: connect to host 16.176.238.35 port 22: Operation timed out`

Most likely causes:
- EC2 stopped
- Elastic IP no longer attached
- SG rule for SSH no longer matches current public IP
- local IP changed and SSH source is still locked to old IP

Likely fix:
- check AWS console:
  - instance still running
  - Elastic IP still associated
  - SG inbound has `22` from current `My IP`

## What To Do Next In The New Thread
Priority order:

1. Recover SSH access to EC2
   - verify instance state
   - verify Elastic IP association
   - verify SG inbound SSH rule
2. Compare EC2 and local versions of:
   - `DockerFile`
   - `bootstrap/providers.php`
3. Apply the EC2-only fixes locally
4. Commit and push those repo changes
5. Re-check EC2 `.env` has the real Cloudflare API values:
   - `CLOUDFLARE_API_TOKEN`
   - `CLOUDFLARE_ZONE_ID`
   - `CLOUDFLARE_ENABLED=true`
   - `CLOUDFLARE_FALLBACK_ORIGIN=proxy-fallback.bhonewai.cc.cd`
6. Trace why `delivery.bhonewai.cc.cd` still has:
   - `verified_at = null`
   - `cf_hostname_status = null`
   - `cf_ssl_status = null`
7. Confirm whether controller-level `syncCloudflareForDomain()` is running in production
8. Decide whether to keep temporary sqlite for now or move to MySQL/RDS

## Most Relevant Files
Local repo:
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/DockerFile`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/bootstrap/providers.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docker-compose.prod.yml`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docker/nginx/conf.d/prod/app.conf`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/config/cloudflare.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/config/tenancy.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Http/Controllers/Tenant/DomainController.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Services/CloudflareService.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/database/seeders/DatabaseSeeder.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/database/seeders/SuperAdminSeeder.php`

Earlier backup:
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docs/cloudflare-handoff-backup.md`

## Suggested Prompt For New Thread
Continue from this handoff plus:

- `docs/cloudflare-handoff-backup.md`

Current deployed state:
- AWS EC2 origin is live behind Cloudflare
- `proxy-fallback.bhonewai.cc.cd` works as the central domain and fallback origin
- central login page is reachable in production
- nginx/app/queue are running on EC2
- remaining issue is tenant-domain activation, not AWS reachability

Important:
- the EC2 clone has manual fixes not yet committed back into the repo
- `DockerFile` was changed for sqlite support / lower-memory build
- `bootstrap/providers.php` was changed to remove Telescope provider in production
- EC2 is currently using sqlite as a temporary production workaround
- SSH from local machine to EC2 is timing out again and likely needs AWS console/security-group review

Please help me:
1. recover SSH access
2. turn the EC2-only fixes into proper repo changes
3. verify Cloudflare custom-domain status sync on the deployed server
4. make `delivery.bhonewai.cc.cd` become an active verified tenant domain
5. advise whether to stay on temporary sqlite or move to MySQL/RDS next
