# TenantSmith Design Tokens

## Color Palette

### Brand (Primary Accent)

| Token | Hex | Tailwind Class |
|-------|-----|----------------|
| brand-50 | `#eef2ff` | `bg-brand-50` |
| brand-100 | `#e0e7ff` | `bg-brand-100` |
| brand-200 | `#c7d2fe` | `bg-brand-200` |
| brand-300 | `#a5b4fc` | `bg-brand-300` |
| brand-400 | `#818cf8` | `bg-brand-400` |
| brand-500 | `#6366f1` | `bg-brand-500` |
| brand-600 | `#4f46e5` | `bg-brand-600` |
| brand-700 | `#4338ca` | `bg-brand-700` |
| brand-800 | `#3730a3` | `bg-brand-800` |
| brand-900 | `#312e81` | `bg-brand-900` |
| brand-950 | `#1e1b4b` | `bg-brand-950` |

### Dark Theme Surface Colors (Custom Hex — NOT Tailwind Grays)

| Surface | Hex | Tailwind Class | Usage |
|---------|-----|----------------|-------|
| Page bg | `#0a0a0f` | `dark:bg-[#0a0a0f]` | Body background, focus ring offset |
| Surface | `#14141c` | `dark:bg-[#14141c]` | Cards, sidebar, header, inputs, dropdowns |
| Elevated | `#1e1e28` | `dark:bg-[#1e1e28]` | Hover states, active nav items, accordion content |
| Border | `#2a2a38` | `dark:border-[#2a2a38]` | All borders, input borders, dividers |

### Light Theme Surface Colors (CSS Custom Properties)

| Variable | Light Value | Dark Value |
|----------|------------|------------|
| `--color-bg-page` | `rgb(243 244 246)` | `rgb(10 10 15)` |
| `--color-bg-surface` | `rgb(255 255 255)` | `rgb(20 20 28)` |
| `--color-bg-elevated` | `rgb(249 250 251)` | `rgb(30 30 40)` |
| `--color-text-primary` | `rgb(17 24 39)` | `rgb(243 244 246)` |
| `--color-text-secondary` | `rgb(107 114 128)` | `rgb(156 163 175)` |
| `--color-text-muted` | `rgb(156 163 175)` | `rgb(107 114 128)` |
| `--color-border` | `rgb(229 231 235)` | `rgb(55 65 81)` |
| `--color-border-strong` | `rgb(209 213 219)` | `rgb(75 85 99)` |

### Feedback Colors

| State | Light | Dark | Component |
|-------|-------|------|-----------|
| Success | `text-green-600` | `dark:text-green-400` | `auth-session-status` |
| Error | `text-red-600` | `dark:text-red-400` | `input-error`, `auth-input` |
| Danger | `bg-red-600` | (same) | `danger-button` |

## Component Tokens

### Input Fields (auth-input)

| Property | Value | Tailwind |
|----------|-------|----------|
| Border radius | `rounded-lg` | `rounded-lg` |
| Padding | top 20px, bottom 8px, sides 12px | `pt-5 pb-2 px-3` |
| Font size | 14px | `text-sm` |
| Shadow | subtle | `shadow-sm` |
| Border (light) | `#d1d5db` | `border-gray-300` |
| Border (dark) | `#2a2a38` | `dark:border-[#2a2a38]` |
| Focus border | `#6366f1` | `focus:border-brand-500` |
| Focus ring | `#6366f1` | `focus:ring-brand-500` |
| Error border | `#f87171` | `border-red-400` |
| Error shake | horizontal 4px | `animate-shake` |

### Buttons

| Property | Primary | Secondary | Danger |
|----------|---------|-----------|--------|
| Border radius | `rounded-lg` | `rounded-lg` | `rounded-lg` |
| Padding | `px-6 py-3` | `px-4 py-2` | `px-4 py-2` |
| Font size | `text-sm` | `text-xs` | `text-xs` |
| Font weight | `font-semibold` | `font-semibold` | `font-semibold` |
| Shadow | `shadow-sm hover:shadow-md` | `shadow-sm` | `shadow-sm` |
| Transition | `transition-all duration-200` | `transition` | `transition` |
| Focus ring | `focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0f]` | Same | `focus:ring-red-500` |

### Primary Button Full Classes

```
group relative w-full flex items-center justify-center gap-2
px-6 py-3 text-sm font-semibold text-white rounded-lg
bg-brand-600 hover:bg-brand-700 active:bg-brand-800
focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#0a0a0f]
shadow-sm hover:shadow-md
transition-all duration-200 ease-in-out
disabled:opacity-75 disabled:cursor-not-allowed
```

### Sidebar Nav Item

| State | Background | Text | Icon |
|-------|-----------|------|------|
| Inactive | none | `text-gray-600 dark:text-gray-400` | `text-gray-400 dark:text-gray-500` |
| Hover | `hover:bg-gray-100 dark:hover:bg-[#1e1e28]` | `hover:text-gray-900 dark:hover:text-gray-100` | — |
| Active | `bg-gray-100 dark:bg-[#1e1e28]` | `text-gray-900 dark:text-gray-100 font-medium` | `text-brand-500 dark:text-brand-400` |

All nav items: `flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors`

### Sidebar Sub-Link (in accordion)

| State | Background | Text |
|-------|-----------|------|
| Inactive | none | `text-gray-600 dark:text-gray-400` |
| Hover | `hover:bg-gray-50 dark:hover:bg-[#1e1e28]` | `hover:text-gray-900 dark:hover:text-gray-100` |
| Active | `bg-brand-50 dark:bg-brand-900/20` | `text-brand-700 dark:text-brand-300 font-medium` |

Sub-links: `flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors`

### Dropdown

| Property | Value |
|----------|-------|
| Background (light) | `bg-white` |
| Background (dark) | `dark:bg-[#14141c]` |
| Ring | `ring-1 ring-black ring-opacity-5 dark:ring-opacity-20` |
| Border radius | `rounded-md` |

### Modal

| Property | Value |
|----------|-------|
| Backdrop | `bg-gray-500 dark:bg-gray-900 opacity-75` |
| Content (light) | `bg-white` |
| Content (dark) | `dark:bg-[#14141c]` |
| Border radius | `rounded-lg` |
| Shadow | `shadow-xl` |

## Animation Tokens

| Class | Keyframes | Duration | Delay | Usage |
|-------|-----------|----------|-------|-------|
| `animate-fade-up` | opacity 0→1, translateY 16px→0 | 0.4s ease-out | none | Page entrance |
| `animate-fade-up-delay-1` | Same | 0.4s ease-out | 0.1s | Staggered entrance |
| `animate-fade-up-delay-2` | Same | 0.4s ease-out | 0.2s | Staggered entrance |
| `animate-fade-up-delay-3` | Same | 0.4s ease-out | 0.3s | Staggered entrance |
| `animate-shake` | translateX 0→-4→4→0 | 0.4s ease-in-out | none | Error validation |
| `animate-float` | translateY 0→-8px→0 | 6s ease-in-out | none | Illustration bobbing |
| `animate-float-delayed` | Same | 6s ease-in-out | 2s | Secondary illustration |

## Component Inventory

| Component | File | Key Classes |
|-----------|------|-------------|
| application-logo | `components/application-logo.blade.php` | SVG with brand-300/400/500 |
| theme-toggle | `components/theme-toggle.blade.php` | Alpine `$store.theme.toggle()` |
| auth-input | `components/auth-input.blade.php` | Floating label, `rounded-lg`, `shadow-sm` |
| auth-button | `components/auth-button.blade.php` | Inlined in login form (see button tokens) |
| primary-button | `components/primary-button.blade.php` | `bg-brand-600 hover:bg-brand-700` |
| secondary-button | `components/secondary-button.blade.php` | `bg-white dark:bg-[#14141c]` |
| danger-button | `components/danger-button.blade.php` | `bg-red-600` |
| text-input | `components/text-input.blade.php` | `dark:bg-[#14141c] dark:text-gray-100` |
| input-label | `components/input-label.blade.php` | `text-gray-700 dark:text-gray-300` |
| input-error | `components/input-error.blade.php` | `text-red-600 dark:text-red-400` |
| dropdown | `components/dropdown.blade.php` | `bg-white dark:bg-[#14141c]` |
| dropdown-link | `components/dropdown-link.blade.php` | `hover:bg-gray-100 dark:hover:bg-[#1e1e28]` |
| modal | `components/modal.blade.php` | `bg-white dark:bg-[#14141c]` |
| nav-link | `components/nav-link.blade.php` | `border-brand-500` active state |
| responsive-nav-link | `components/responsive-nav-link.blade.php` | `border-brand-500 bg-brand-50 dark:bg-brand-900/20` |
| auth-session-status | `components/auth-session-status.blade.php` | `text-green-600 dark:text-green-400` |
| sidebar | `components/sidebar.blade.php` | `bg-white dark:bg-[#14141c]`, all nav items themed |
| guest-layout | `layouts/guest.blade.php` | Split-screen, brand gradient, tenant-aware |
| app-layout | `layouts/app.blade.php` | Sidebar + sticky header, `dark:bg-[#0a0a0f]` page bg |
| card | `components/card.blade.php` | `bg-white dark:bg-[#14141c]` + header/footer slots |
| badge | `components/badge.blade.php` | Variants: success, warning, danger, info, brand, neutral |
| stat-card | `components/stat-card.blade.php` | Icon + value + label + trend indicator |
| quick-action | `components/quick-action.blade.php` | Icon + title + description + chevron |
| data-table | `components/data-table.blade.php` | Responsive: table on lg, cards on mobile |
| breadcrumbs | `components/breadcrumbs.blade.php` | Navigation breadcrumb trail |
| user-menu | `components/user-menu.blade.php` | Avatar initials + dropdown, `flex items-center gap-2` on links |
| page-header | `components/page-header.blade.php` | Title + subtitle + action slot |
| empty-state | `components/empty-state.blade.php` | Icon + title + description for empty lists |

## Dark Mode Checklist

When adding or modifying a component, verify:

- [ ] Every `bg-white` has `dark:bg-[#14141c]`
- [ ] Every `bg-gray-100` has `dark:bg-[#0a0a0f]`
- [ ] Every `bg-gray-50` has `dark:bg-[#1e1e28]`
- [ ] Every `text-gray-900` has `dark:text-gray-100`
- [ ] Every `text-gray-700` has `dark:text-gray-300`
- [ ] Every `text-gray-500` has `dark:text-gray-400`
- [ ] Every `text-gray-400` has `dark:text-gray-500`
- [ ] Every `border-gray-200` has `dark:border-[#2a2a38]`
- [ ] Every `border-gray-300` has `dark:border-[#2a2a38]`
- [ ] Every `hover:bg-gray-100` has `dark:hover:bg-[#1e1e28]`
- [ ] Focus ring offsets use `dark:focus:ring-offset-[#0a0a0f]`
- [ ] Brand accent uses `brand-*` not raw `indigo-*`
- [ ] Buttons and inputs use `rounded-lg` (not `rounded-md`)
- [ ] Primary buttons have `shadow-sm hover:shadow-md`
- [ ] Input fields have `shadow-sm`
