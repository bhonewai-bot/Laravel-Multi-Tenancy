# Write-Back Convention

Project knowledge lives in reference files (`.claude/skills/*/references/*.md`). When we discover something that changes what a reference says, we update it. This document defines when and how.

## When to trigger a write-back

Propose an update when ANY of these are true:

1. **Contradiction** — something the reference says is wrong (e.g., "Cloudflare requires domain to be within zone" → actually SSL for SaaS works with ANY domain)
2. **New insight** — we learned something the reference doesn't mention (e.g., a specific error code, a timing constraint, a config gotcha)
3. **Bug found** — a known issue worth recording (e.g., "EnsureVerifiedTenantDomain middleware is referenced by tests but doesn't exist")
4. **Feature change** — code was modified in a way that invalidates what the reference says
5. **New file addition** — a new service, middleware, or config is added to the subsystem

## How to propose an update

1. Read the current reference file
2. Draft the change as a diff or inline edit
3. Show the user what would change and ask permission
4. After approval, make the edit
5. Append an entry to `log.md` in the same `references/` directory

## Log format

Each `references/log.md` is append-only. Entries follow this format:

```
## [YYYY-MM-DD] — <one-line summary>

- **What changed:** <file path and section>
- **Why:** <discovery or reason>
- **Source:** <conversation, agent run, test failure, etc.>
```

Keep entries factual — not conversational. Someone reading the log 6 months later should understand what happened and why.

## Scope

This convention applies to ALL skills under `.claude/skills/`. Every skill with reference files gets write-back. The pattern is identical regardless of domain.
