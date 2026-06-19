# Multi-Tenancy Discovery Log

## [2026-06-17] — Cloudflare SSL for SaaS works with ANY domain, not just zone subdomains

- **What changed:** `references/cloudflare.md` and `references/architecture.md` — removed incorrect zone-domain validation assumption
- **Why:** Tested with `shop.example.com` and got Cloudflare error 1411. Initially assumed the domain must be a subdomain of `bhonewai.cc.cd`. Further research confirmed Cloudflare SSL for SaaS is designed for tenants to bring their own domains — any valid domain works regardless of zone scope. The error was because `example.com` is IANA-reserved, not because it's outside the zone.
- **Source:** Conversation during P0 #1 implementation, CloudflareService fix

## [2026-06-17] — Initial skill creation

- **What changed:** Created `references/architecture.md` and `references/cloudflare.md` — full subsystem maps
- **Why:** Encoded multi-tenancy subsystem knowledge (60+ files, data flows, middleware pipeline, Cloudflare flow) from Explore agent output into progressive-disclosure reference files
- **Source:** Explore agent run and General agent plan during initial multi-tenancy audit

## [2026-06-17] — Corrected stale claims in cloudflare.md and architecture.md

- **What changed:** `references/cloudflare.md` and `references/architecture.md`
  - Removed `deleteHostname()` from cloudflare.md (method does not exist in CloudflareService)
  - Replaced "Known bug: No zone validation" with accurate description of requestException catch and reserved domain handling
  - Rewrote known issue #1 in architecture.md to reflect the intentional design decision
  - Added `zone_domain` to SKILL.md config table
- **Why:** Subsystem-explorer agent audit found two contradictions between reference files and actual code. Cloudflare SSL for SaaS supports any domain — zone validation was intentionally NOT added because it would block legitimate use. The error 1411 with `shop.example.com` was caused by `example.com` being an IANA-reserved domain (RFC 2606), not by being outside the zone.
- **Source:** AI integration audit via general-purpose agent, write-back loop activation
