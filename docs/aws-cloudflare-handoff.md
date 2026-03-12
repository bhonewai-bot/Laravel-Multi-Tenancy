# AWS + Cloudflare Custom Domain Handoff

Last updated: 2026-03-12 (Asia/Yangon)

This file is the restart document for continuing the Laravel multi-tenancy deployment thread in a new chat.

## 1) Repo and Goal

- Working repo: `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy`
- Main goal: make Cloudflare Custom Hostnames work against a real public origin for the Laravel app
- Local implementation status: app-side custom-domain lifecycle is mostly done
- Current blocker: public origin infrastructure, not Laravel domain CRUD logic

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

Conclusion:

- The problem is not mainly Cloudflare DNS anymore.
- The problem is no stable, internet-reachable Laravel/Nginx origin for the fallback hostname.

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

## 8) Domain / DNS Model

Keep this separation:

- `bhonewai.cc.cd` can stay wherever it already lives, including Vercel
- `proxy-fallback.bhonewai.cc.cd` should point to the Laravel origin on AWS

Do **not** point fallback origin to Vercel if custom-domain traffic is supposed to land on Laravel.

One fallback-origin hostname is enough for many tenant custom domains.

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

## 10) Immediate Next Steps In A New Thread

In the next thread, do this work in order:

1. Choose AWS EC2 shape
   - Ubuntu 22.04 or 24.04
   - likely `t3.small`
2. Attach an Elastic IP
3. Open security group ports:
   - `22` from user IP
   - `80` from internet
   - `443` from internet
4. Decide database path:
   - MySQL container or RDS
5. Bootstrap server:
   - Docker
   - Docker Compose
   - repo clone
   - production `.env`
6. Generate Cloudflare Origin Certificate
7. Place origin cert/key into:
   - `docker/nginx/ssl/origin.crt`
   - `docker/nginx/ssl/origin.key`
8. Run:
   - `docker compose -f docker-compose.prod.yml up -d --build`
9. Point `proxy-fallback.bhonewai.cc.cd` to the Elastic IP
10. Retest:
   - `https://proxy-fallback.bhonewai.cc.cd`
   - then a tenant custom domain

## 11) Suggested Prompt For New Thread

Use this:

```text
Continue from:
- docs/cloudflare-handoff-backup.md
- docs/aws-cloudflare-handoff.md

Current state:
- Laravel custom-domain lifecycle is mostly implemented
- local/home-network fallback origin approach was abandoned
- we are moving to AWS EC2 as the real public origin

Please help me deploy this repo to AWS EC2 in a way that matches docker-compose.prod.yml and Cloudflare Custom Hostnames.
Use nginx as the public origin, not Caddy.
Assume proxy-fallback.bhonewai.cc.cd will point to the EC2 Elastic IP.
Also help me decide whether to use RDS or a MySQL container for the fastest safe setup.
```

## 12) Files Most Relevant For The Next Thread

- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docker-compose.prod.yml`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docker/nginx/conf.d/prod/app.conf`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/config/cloudflare.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Services/CloudflareService.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/app/Http/Controllers/Tenant/DomainController.php`
- `/Users/appleclub/Documents/Professional Product Lab/Laravel-Multi-Tenancy/docs/cloudflare-handoff-backup.md`

