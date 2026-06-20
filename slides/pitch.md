---
marp: true
paginate: true
transition: fade
# PechaKucha: 6 slides, 20s auto-advance. Do not change the count.
auto-advance: 20
---

<!-- slide 1 -->

# Who's my person?

<!-- 20s -->

A developer building a SaaS platform one feature at a time. Every feature I build, I understand deeply for about two weeks. Then it fades.

---

<!-- slide 2 -->

# Their problem

Complex subsystems — multi-tenancy, Cloudflare SSL for SaaS, queue jobs, middleware pipelines — span 60+ files. Six months later, I can't remember how `DomainCloudflareSyncService::shouldMarkVerified()` works or why `RejectInvalidTenantHost` must run before `InitializeTenancyByDomain`. I'm re-reading my own code from scratch. **Only god knows how it works after 2-3 months.**

---

<!-- slide 3 -->

# What I built

An AI-integrated development workflow where project knowledge lives in files, not in my head. Skills encode step-by-step procedures. Agents explore and map subsystems autonomously. MCP connects AI directly to live databases and logs. A write-back loop keeps reference files accurate as the codebase evolves.

---

<!-- slide 4 -->

# How I built it

- **MCP:** `laravel-boost` — live database queries, schema inspection, logs. No more `dd()` debugging.
- **Skill:** `multi-tenancy` — progressive disclosure. Instructions in SKILL.md, architecture maps in `references/`. Loads cheap, scales deep.
- **Agent:** `subsystem-explorer` — reads 60 files, traces data flows, finds bugs. Maps entire subsystems in one autonomous pass.

---

<!-- slide 5 -->

# Why it matters

Solo devs and small teams ship complex systems but can't afford to re-learn them every quarter. These AI tools are project memory that doesn't decay. The codebase teaches itself to the next developer — or to you, six months later. **Knowledge outlasts memory.**

---

<!-- slide 6 -->

# Done checklist

- [x] repo public
- [x] MCP + skill + agent used
- [x] report.md in team repo
