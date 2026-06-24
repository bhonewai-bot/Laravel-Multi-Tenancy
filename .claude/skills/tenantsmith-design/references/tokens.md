# TenantSmith Design Tokens

## Color Palette

### Brand (Primary Accent)

| Token     | Hex       | Tailwind Class |
| --------- | --------- | -------------- |
| brand-50  | `#eef2ff` | `bg-brand-50`  |
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

| Surface  | Hex       | Tailwind Class          | Usage                                             |
| -------- | --------- | ----------------------- | ------------------------------------------------- |
| Page bg  | `#08080c` | `dark:bg-[#08080c]`     | Body background, focus ring offset                |
| Surface  | `#101016` | `dark:bg-[#101016]`     | Cards, sidebar, header, inputs, dropdowns         |
| Elevated | `#181820` | `dark:bg-[#181820]`     | Hover states, active nav items, accordion content |
| Border   | `#262632` | `dark:border-[#262632]` | All borders, input borders, dividers              |

### Light Theme Surface Colors (CSS Custom Properties)

| Variable                 | Light Value        | Dark Value           |
| ------------------------ | ------------------ | -------------------- |
| `--color-bg-page`        | `rgb(243 244 246)` | `rgb(8 8 12)`        |
| `--color-bg-surface`     | `rgb(255 255 255)` | `rgb(16 16 22)`    |
| `--color-bg-elevated`    | `rgb(249 250 251)` | `rgb(24 24 32)`    |
| `--color-text-primary`   | `rgb(17 24 39)`    | `rgb(243 244 246)` |
| `--color-text-secondary` | `rgb(107 114 128)` | `rgb(156 163 175)` |
| `--color-text-muted`     | `rgb(156 163 175)` | `rgb(107 114 128)` |
| `--color-border`         | `rgb(229 231 235)` | `rgb(38 38 50)`    |
| `--color-border-strong`  | `rgb(209 213 219)` | `rgb(55 55 72)`    |

### Feedback Colors

| State   | Light            | Dark                  | Component                   |
| ------- | ---------------- | --------------------- | --------------------------- |
| Success | `text-green-600` | `dark:text-green-400` | `auth-session-status`       |
| Error   | `text-red-600`   | `dark:text-red-400`   | `input-error`, `auth-input` |
| Danger  | `bg-red-600`     | (same)                | `danger-button`             |

### Shadows & Glows (CSS Custom Properties → Tailwind Utilities)

| Token                  | Light                                                              | Dark                                                                | Tailwind Class       |
| ---------------------- | ------------------------------------------------------------------ | ------------------------------------------------------------------- | -------------------- |
| `--shadow-card`        | `0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06)`          | `0 1px 3px rgba(0,0,0,0.3), 0 1px 2px rgba(0,0,0,0.2)`             | `shadow-card`        |
| `--shadow-card-hover`  | `0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04)`         | `0 4px 16px rgba(0,0,0,0.4), 0 2px 6px rgba(0,0,0,0.2)`            | `shadow-card-hover`  |
| `--shadow-elevated`    | `0 8px 24px rgba(0,0,0,0.1), 0 2px 8px rgba(0,0,0,0.06)`          | `0 8px 32px rgba(0,0,0,0.5), 0 4px 12px rgba(0,0,0,0.3)`           | `shadow-elevated`    |
| `--glow-brand`         | `0 0 0 1px rgba(99,102,241,0.15), 0 0 20px rgba(99,102,241,0.1)`   | `0 0 0 1px rgba(99,102,241,0.2), 0 0 24px rgba(99,102,241,0.08)`   | `shadow-glow-brand`  |
| `--glow-brand-strong`  | `0 0 0 1px rgba(99,102,241,0.3), 0 0 30px rgba(99,102,241,0.15)`   | `0 0 0 1px rgba(99,102,241,0.4), 0 0 40px rgba(99,102,241,0.12)`   | `shadow-glow-brand-strong` |

## Component Tokens

### Input Fields (auth-input)

| Property       | Value                            | Tailwind                 |
| -------------- | -------------------------------- | ------------------------ |
| Border radius  | `rounded-lg`                     | `rounded-lg`             |
| Padding        | top 20px, bottom 8px, sides 12px | `pt-5 pb-2 px-3`         |
| Font size      | 14px                             | `text-sm`                |
| Shadow         | `shadow-card` default, `shadow-glow-brand` focus | `shadow-card` / `focus:shadow-glow-brand` |
| Border         | `border-gray-200` light, `dark:border-[#262632]` | `border-gray-200` / `dark:border-[#262632]` |
| Background     | `bg-white` light, `dark:bg-[#101016]` dark       | `bg-white` / `dark:bg-[#101016]`    |
| Focus border   | `focus:border-brand-500`                         | `focus:border-brand-500`            |
| Focus ring     | `ring-2 ring-brand-500/30` (semi-transparent)     | `focus:ring-brand-500/30`           |
| Error          | `border-red-400 dark:border-red-500` + `animate-shake` | `border-red-400` + `animate-shake` |

### Buttons

| Property      | Primary                                                                                  | Secondary       | Danger               |
| ------------- | ---------------------------------------------------------------------------------------- | --------------- | -------------------- |
| Border radius | `rounded-lg`                                                                             | `rounded-lg`    | `rounded-lg`         |
| Padding       | `px-6 py-3`                                                                              | `px-4 py-2`     | `px-4 py-2`          |
| Font size     | `text-sm`                                                                                | `text-xs`       | `text-xs`            |
| Font weight   | `font-semibold`                                                                          | `font-semibold` | `font-semibold`      |
| Background    | `bg-gradient-to-b from-brand-500 to-brand-600`                                          | `bg-white dark:bg-[#101016]` | `bg-gradient-to-b from-red-500 to-red-600` |
| Shadow        | `shadow-card hover:shadow-glow-brand-strong`                                             | `shadow-card`   | `shadow-card hover:shadow-[0_0_20px_rgba(239,68,68,0.15)]` |
| Transition    | `transition-all duration-200`                                                            | `transition-all duration-200` | `transition-all duration-200` |
| Focus ring    | `focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c]` | Same            | `focus:ring-red-500` |

### Primary Button Full Classes

```
group relative w-full flex items-center justify-center gap-2
px-6 py-3 text-sm font-semibold text-white rounded-lg
bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20
hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700
active:from-brand-600 active:to-brand-800
focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c]
shadow-card
transition-all duration-200 ease-in-out
disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none
```

### Sidebar Nav Item

| State    | Background                                  | Text                                           | Icon                                 |
| -------- | ------------------------------------------- | ---------------------------------------------- | ------------------------------------ |
| Inactive | none                                        | `text-gray-600 dark:text-gray-400`             | `text-gray-400 dark:text-gray-500`   |
| Hover    | `hover:bg-gray-100 dark:hover:bg-[#181820]` | `hover:text-gray-900 dark:hover:text-gray-100` | —                                    |
| Active   | `bg-gray-100 dark:bg-[#181820]`             | `text-gray-900 dark:text-gray-100 font-medium` | `text-brand-500 dark:text-brand-400` |

All nav items: `flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors`

**Icons:** Heroicons via `blade-heroicons` package. Outline style (`<x-heroicon-o-*>`).
**Section labels:** `text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500`
**Sidebar width:** `w-56` (expanded) / `w-16` (collapsed)

### Sidebar Sub-Link (in accordion)

| State    | Background                                 | Text                                             |
| -------- | ------------------------------------------ | ------------------------------------------------ |
| Inactive | none                                       | `text-gray-600 dark:text-gray-400`               |
| Hover    | `hover:bg-gray-50 dark:hover:bg-[#181820]` | `hover:text-gray-900 dark:hover:text-gray-100`   |
| Active   | `bg-brand-50 dark:bg-brand-500/10`         | `text-brand-700 dark:text-brand-300 font-medium` |

Sub-links: `flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors`

### Dropdown

| Property           | Value                                                   |
| ------------------ | ------------------------------------------------------- |
| Background (light) | `bg-white`                                              |
| Background (dark)  | `dark:bg-[#101016]`                                     |
| Ring               | `ring-1 ring-black ring-opacity-5 dark:ring-opacity-20` |
| Border radius      | `rounded-md`                                            |

### Modal

| Property        | Value                                     |
| --------------- | ----------------------------------------- |
| Backdrop        | `bg-gray-500 dark:bg-gray-900 opacity-75` |
| Content (light) | `bg-white`                                |
| Content (dark)  | `dark:bg-[#101016]`                       |
| Border radius   | `rounded-lg`                              |
| Shadow          | `shadow-xl`                               |

## Animation Tokens

| Class                     | Keyframes                      | Duration         | Delay | Usage                  |
| ------------------------- | ------------------------------ | ---------------- | ----- | ---------------------- |
| `animate-fade-up`         | opacity 0→1, translateY 16px→0 | 0.4s ease-out    | none  | Page entrance          |
| `animate-fade-up-delay-1` | Same                           | 0.4s ease-out    | 0.1s  | Staggered entrance     |
| `animate-fade-up-delay-2` | Same                           | 0.4s ease-out    | 0.2s  | Staggered entrance     |
| `animate-fade-up-delay-3` | Same                           | 0.4s ease-out    | 0.3s  | Staggered entrance     |
| `animate-shake`           | translateX 0→-4→4→0            | 0.4s ease-in-out | none  | Error validation       |
| `animate-float`           | translateY 0→-8px→0            | 6s ease-in-out   | none  | Illustration bobbing   |
| `animate-float-delayed`   | Same                           | 6s ease-in-out   | 2s    | Secondary illustration |

## Component Inventory

| Component           | File                                       | Key Classes                                                    |
| ------------------- | ------------------------------------------ | -------------------------------------------------------------- |
| application-logo    | `components/application-logo.blade.php`    | SVG with brand-300/400/500                                     |
| theme-toggle        | `components/theme-toggle.blade.php`        | Alpine `$store.theme.toggle()`                                 |
| auth-input          | `components/auth-input.blade.php`          | Floating label, `rounded-lg`, `shadow-card`                      |
| auth-button         | `components/auth-button.blade.php`         | Inlined in login form (see button tokens)                      |
| primary-button      | `components/primary-button.blade.php`      | `bg-brand-600 hover:bg-brand-700`                              |
| secondary-button    | `components/secondary-button.blade.php`    | `bg-white dark:bg-[#101016]`                                   |
| danger-button       | `components/danger-button.blade.php`       | `bg-red-600`                                                   |
| text-input          | `components/text-input.blade.php`          | `dark:bg-[#101016] dark:text-gray-100`                         |
| input-label         | `components/input-label.blade.php`         | `text-gray-700 dark:text-gray-300`                             |
| input-error         | `components/input-error.blade.php`         | `text-red-600 dark:text-red-400`                               |
| dropdown            | `components/dropdown.blade.php`            | `bg-white dark:bg-[#101016]`                                   |
| dropdown-link       | `components/dropdown-link.blade.php`       | `hover:bg-gray-100 dark:hover:bg-[#181820]`                    |
| modal               | `components/modal.blade.php`               | `bg-white dark:bg-[#101016]`                                   |
| nav-link            | `components/nav-link.blade.php`            | `border-brand-500` active state                                |
| responsive-nav-link | `components/responsive-nav-link.blade.php` | `border-brand-500 bg-brand-50 dark:bg-brand-500/10`            |
| auth-session-status | `components/auth-session-status.blade.php` | `text-green-600 dark:text-green-400`                           |
| sidebar             | `components/sidebar.blade.php`             | `bg-white dark:bg-[#101016]`, all nav items themed             |
| guest-layout        | `layouts/guest.blade.php`                  | Split-screen, brand gradient, tenant-aware                     |
| app-layout          | `layouts/app.blade.php`                    | Sidebar + sticky header, `dark:bg-[#08080c]` page bg           |
| card                | `components/card.blade.php`                | `bg-white dark:bg-[#101016]` + header/footer slots             |
| badge               | `components/badge.blade.php`               | Variants: success, warning, danger, info, brand, neutral       |
| stat-card           | `components/stat-card.blade.php`           | Icon + value + label + trend indicator                         |
| data-table          | `components/data-table.blade.php`          | Responsive: table on lg, cards on mobile                       |
| breadcrumbs         | `components/breadcrumbs.blade.php`         | Navigation breadcrumb trail                                    |
| user-menu           | `components/user-menu.blade.php`           | Avatar initials + dropdown, `flex items-center gap-2` on links |
| page-header         | `components/page-header.blade.php`         | Title + subtitle + action slot                                 |
| empty-state         | `components/empty-state.blade.php`         | Icon + title + description for empty lists                     |

## Dark Mode Checklist

When adding or modifying a component, verify:

- [ ] Every `bg-white` has `dark:bg-[#101016]`
- [ ] Every `bg-gray-100` has `dark:bg-[#08080c]`
- [ ] Every `bg-gray-50` has `dark:bg-[#181820]`
- [ ] Every `text-gray-900` has `dark:text-gray-100`
- [ ] Every `text-gray-700` has `dark:text-gray-300`px]
- [ ] Every `text-gray-500` has `dark:text-gray-400`
- [ ] Every `text-gray-400` has `dark:text-gray-500`
- [ ] Every `border-gray-200` has `dark:border-[#262632]`
- [ ] Every `border-gray-300` has `dark:border-[#262632]`
- [ ] Every `hover:bg-gray-100` has `dark:hover:bg-[#181820]`
- [ ] Focus ring offsets use `dark:focus:ring-offset-[#08080c]`
- [ ] Brand accent uses `brand-*` not raw `indigo-*`
- [ ] Buttons and inputs use `rounded-lg` (not `rounded-md`)
- [ ] Primary buttons use `<x-primary-button>` component with `shadow-card hover:shadow-glow-brand-strong`
- [ ] Input fields use `shadow-card`, focus uses `shadow-glow-brand`
