---
name: tenantsmith-design
description: "Use when building or modifying UI components, layouts, or pages for TenantSmith. Covers the brand color palette, dark/light theme conventions, component patterns, typography, spacing rules, and the login page design system. Do not use for backend logic, database migrations, or API endpoints."
metadata:
  author: team
---

# TenantSmith Design System

TenantSmith uses a dual-theme (light/dark) design system built on Tailwind CSS v3 with `darkMode: 'class'`. All components must support both themes.

## Quick Reference

### Brand Colors

The `brand` color scale (aliased indigo) is the primary accent:

| Token | Hex | Usage |
|-------|-----|-------|
| `brand-50` | `#eef2ff` | Subtle backgrounds, tints |
| `brand-100` | `#e0e7ff` | Light accent bg |
| `brand-200` | `#c7d2fe` | Borders, dividers |
| `brand-400` | `#818cf8` | Active indicators |
| `brand-500` | `#6366f1` | **Primary accent** — focus rings, active states |
| `brand-600` | `#4f46e5` | **Primary buttons**, links |
| `brand-700` | `#4338ca` | Hover on primary actions |
| `brand-800` | `#3730a3` | Active/pressed primary |

### Dark Theme Colors (Dark Mode)

The dark theme uses custom hex values, NOT standard Tailwind grays:

| Surface | Hex | Tailwind Class | Usage |
|---------|-----|----------------|-------|
| Page bg | `#0a0a0f` | `dark:bg-[#0a0a0f]` | Body, page background |
| Surface | `#14141c` | `dark:bg-[#14141c]` | Cards, sidebar, header, inputs |
| Elevated | `#1e1e28` | `dark:bg-[#1e1e28]` | Hover states, dropdowns, active items |
| Border | `#2a2a38` | `dark:border-[#2a2a38]` | All borders, input borders |

**IMPORTANT**: Do NOT use `dark:bg-gray-800`, `dark:bg-gray-900`, etc. Always use the custom hex values above.

### Dark Mode Rule

**Every light-mode class MUST have a `dark:` counterpart.** The pattern:

```blade
{{-- Backgrounds — use custom hex, not Tailwind grays --}}
bg-white dark:bg-[#14141c]           {{-- cards, sidebar, header --}}
bg-gray-100 dark:bg-[#0a0a0f]        {{-- page background --}}
bg-gray-50 dark:bg-[#1e1e28]         {{-- hover, elevated --}}

{{-- Text — Tailwind grays are fine for text --}}
text-gray-900 dark:text-gray-100    {{-- primary text --}}
text-gray-700 dark:text-gray-300    {{-- secondary text --}}
text-gray-500 dark:text-gray-400    {{-- muted text --}}
text-gray-400 dark:text-gray-500    {{-- icons, bullets --}}

{{-- Borders — use custom hex --}}
border-gray-200 dark:border-[#2a2a38]

{{-- Interactive --}}
hover:bg-gray-100 dark:hover:bg-[#1e1e28]
hover:text-gray-900 dark:hover:text-gray-100
focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0f]
```

### Buttons

| Type | Classes |
|------|---------|
| Primary | `bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-lg px-6 py-3 text-sm font-semibold shadow-sm hover:shadow-md` |
| Secondary | `bg-white dark:bg-[#14141c] border-gray-300 dark:border-[#2a2a38] text-gray-700 dark:text-gray-300 rounded-lg` |
| Danger | `bg-red-600 hover:bg-red-500 active:bg-red-700 text-white rounded-lg` |

**Button patterns:**
- All buttons use `rounded-lg` (not `rounded-md`)
- Primary buttons have `shadow-sm hover:shadow-md` and `transition-all duration-200`
- Focus ring: `focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0f]`
- Loading state: spinner with `animate-spin w-4 h-4`, text "Signing in..."

### Input Fields (Floating Label)

The `auth-input` component uses a floating-label pattern with Alpine.js:

```blade
{{-- Structure --}}
<div x-data="{ focused: false, value: '{{ old($name) }}', showPassword: false }" class="relative">
    <label ... :class="{ 'top-2 !translate-y-0 text-xs font-medium': focused || value }">
    <input ... class="block w-full pt-5 pb-2 px-3 text-sm rounded-lg border shadow-sm
        bg-white dark:bg-[#14141c] text-gray-900 dark:text-gray-100
        border-gray-300 dark:border-[#2a2a38]
        focus:border-brand-500 focus:ring-brand-500" />
</div>
```

**Input tokens:**
- Border radius: `rounded-lg`
- Padding: `pt-5 pb-2 px-3` (floating label needs top padding)
- Font size: `text-sm`
- Shadow: `shadow-sm`
- Focus: `focus:border-brand-500 focus:ring-brand-500 dark:focus:border-brand-400 dark:focus:ring-brand-400`
- Error state: `border-red-400 dark:border-red-500` + `animate-shake` on error message

### Typography

- Font: **Figtree** (400, 500, 600 weights)
- Page headings: `text-2xl font-bold`
- Subtitles: `text-sm text-gray-500 dark:text-gray-400`
- Sidebar section labels: `text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500`
- Nav items: `text-sm`
- Form labels (floating): `text-sm`, shrinks to `text-xs font-medium` when focused

### Sidebar

**Structure:** Labeled sections → nav items. Each section has an uppercase label and one or more items.

**Section label:** `text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500` — hidden when collapsed via `x-show="!$store.sidebar.collapsed" x-cloak`.

**Nav item (single link):**
```blade
<a href="..." class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors
    hover:bg-gray-100 dark:hover:bg-[#1e1e28]
    {{ $active ? 'bg-gray-100 dark:bg-[#1e1e28] text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100' }}"
    :title="$store.sidebar.collapsed ? 'Label' : ''">
    <svg class="w-5 h-5 shrink-0 {{ $active ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}" .../>
    <span x-show="!$store.sidebar.collapsed" x-cloak class="whitespace-nowrap">Label</span>
</a>
```

**Active state rule:** Active items get background + brand icon. All nav icons must turn `text-brand-500 dark:text-brand-400` when their section is active — no exceptions.

**Accordion (parent with sub-items):** Same active state as single links. Two sub-displays:
- *Expanded:* `x-show="open && !$store.sidebar.collapsed"` — sub-links indented with `ps-4`, active sub-link uses `bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium`.
- *Collapsed flyout:* `x-show="$store.sidebar.collapsed && open"` — absolute-positioned dropdown with `shadow-lg`, `z-50`.

**Sections (central):** Overview (Dashboard) · Central (Tenants accordion) · Platform (Modules accordion)
**Sections (tenant):** Overview (Dashboard) · Platform (Modules) · Access (Users, Roles) · Network (Domains accordion)

**Bottom bar:** Profile link + Logout button, separated by `border-t`. Both use `flex items-center gap-2` in dropdown menus.

### Dashboard

**Page structure:** Page header (title + subtitle) → stat cards grid → two-column layout (activity feed 2/3 + quick actions 1/3).

**Stat cards:** `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`. Each uses `<x-stat-card>` with icon, value, label, description. Central and tenant get different stat sets via `DashboardController`.

**Activity feed:** `<x-card>` with header. Lists recent module requests with status dots (`bg-green-500` / `bg-red-500` / `bg-amber-500`) and `<x-badge>` status labels.

**Quick actions:** `<x-quick-action>` cards with icon, title, description, chevron. Different actions for central vs tenant via `@if ($isTenant)`.

### Layout

- Sidebar width: `w-64` (expanded) / `w-16` (collapsed), persisted in `localStorage`
- Header height: `h-16` (sticky, `z-30`, `backdrop-blur`)
- Content padding: `px-4 py-4 sm:px-6 lg:px-8`
- Theme toggle: placed in header, left of user dropdown

### Login Page (Split Screen)

**Layout structure:**
- Left panel (55% on lg, 50% on xl): brand gradient with illustration
- Right panel: form area, `bg-white dark:bg-[#0a0a0f]`
- Mobile: left panel hidden, compact brand header above form

**Left panel gradient:** `bg-gradient-to-br from-brand-600 via-brand-700 to-brand-950`

**Tenant-aware content:**
```blade
@php $isTenant = (bool) tenant(); @endphp

@if ($isTenant)
    {{-- Tenant: "Your workspace is ready" --}}
    {{-- Features: Team management, Available modules, Custom domains --}}
@else
    {{-- Central: "Manage your tenants with confidence" --}}
    {{-- Features: Multi-tenant ready, Custom domains, Module management --}}
@endif
```

**Illustration:** Inline SVG with floating cards, connected nodes, and stacked layers. Uses `animate-float` for subtle bobbing.

### Animations

Defined in `resources/css/app.css`:

| Class | Animation | Duration | Usage |
|-------|-----------|----------|-------|
| `animate-fade-up` | Fade in + slide up 16px | 0.4s | Page entrance |
| `animate-fade-up-delay-1` | Same, 0.1s delay | 0.4s + 0.1s | Staggered entrance |
| `animate-fade-up-delay-2` | Same, 0.2s delay | 0.4s + 0.2s | Staggered entrance |
| `animate-fade-up-delay-3` | Same, 0.3s delay | 0.4s + 0.3s | Staggered entrance |
| `animate-shake` | Horizontal shake 4px | 0.4s | Error validation |
| `animate-float` | Vertical bob 8px | 6s infinite | Illustration elements |
| `animate-float-delayed` | Same, 2s delay | 6s infinite | Secondary illustration |

**Usage pattern:** Wrap content in a div with the animation class. Use `[x-cloak]` to prevent flash of unstyled content.

### Logo

- SVG: stacked-layers (3 overlapping rounded rectangles in brand-300/400/500)
- File: `resources/views/components/application-logo.blade.php`
- Favicon: `public/favicon.svg`
- Brand name: **TenantSmith** (hardcoded in layouts)

## Before Adding a New Component

1. Check if an existing component covers the use case
2. Add `dark:` variants for every color utility using the custom hex values
3. Use `brand-*` for accent/focus, `gray-*` for text, custom hex for dark surfaces
4. Use `rounded-lg` for buttons and inputs (not `rounded-md`)
5. Add `shadow-sm` to inputs, `shadow-sm hover:shadow-md` to primary buttons
6. Test in both themes before finalizing

## Common Pitfalls

- **Using `dark:bg-gray-800` instead of `dark:bg-[#14141c]`** — the dark theme uses custom hex values, not standard Tailwind grays.
- **Missing `dark:` variant** — the most common mistake. Every `bg-white` needs `dark:bg-[#14141c]`.
- **Using raw `indigo-*` instead of `brand-*`** — the palette is aliased so it can be swapped later.
- **Forgetting `dark:focus:ring-offset-[#0a0a0f]`** on buttons — the focus ring offset must match the dark page background.
- **Using `rounded-md` on buttons/inputs** — use `rounded-lg` for the TenantSmith design.
- **Hardcoded hex colors in SVGs** — use brand tokens where possible; the logo uses brand-300/400/500.
- **Isolated Alpine scope on buttons** — put `x-data` on the `<form>`, not on the `<button>`, to avoid submit issues.

## References

- [Design Tokens](references/tokens.md) — full token table, component inventory, and dark mode checklist
