# Page Structure Patterns

Every page follows one of two patterns: **List** or **Form**. Copy the exact structure from the polished reference.

## List Page (index)

**Reference file:** `resources/views/tenant/index.blade.php` (the gold standard)

```
<x-app-layout>
    <div class="animate-fade-up">

        ═══════ HEADER ═══════
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Page Title</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Subtitle with count</p>
            </div>
            <-- CTA link styled like primary button -->
            <a href="{{ route('...') }}" class="[PRIMARY BUTTON CLASSES]">
                <x-heroicon-o-plus class="w-4 h-4" />
                New Thing
            </a>
        </div>

        ═══════ FLASH MESSAGES ═══════
        @if (session('success'))
            <div class="mb-6"><x-alert variant="success">{{ session('success') }}</x-alert></div>
        @endif

        ═══════ EMPTY STATE ═══════
        @if ($items->isEmpty())
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] p-12 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center mb-4">
                    <x-heroicon-o-[icon] class="w-6 h-6 text-brand-600 dark:text-brand-400" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">No items yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Message about creating first one.</p>
                <a href="{{ route('...') }}"><x-primary-button type="button"><x-heroicon-o-plus class="w-4 h-4" /> Create First</x-primary-button></a>
            </div>

        ═══════ TABLE ═══════
        @else
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] bg-white dark:bg-[#101016] overflow-hidden">

                <-- Desktop Table -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-[#262632] bg-gray-50/50 dark:bg-[#0e0e15]/50">
                                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Column</th>
                                <-- last column w-[72px] or w-[120px] + text-right -->
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#181820]">
                            @foreach ($items as $item)
                                <tr class="group hover:bg-gray-50/70 dark:hover:bg-[#181820]/70 transition-all duration-150">
                                    <td class="px-5 py-4">
                                        <-- Name column: avatar + name + subtitle -->
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center shrink-0">
                                                <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">{{ initials }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">Name</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Subtitle</div>
                                            </div>
                                        </div>
                                    </td>
                                    <-- Other columns: text-sm text-gray-600 dark:text-gray-400 -->
                                    <-- Actions: dropdown with View / Edit / Delete -->
                                    <td class="px-5 py-4 text-right">
                                        <x-dropdown align="right" width="w-40">
                                            <x-slot name="trigger">
                                                <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262632] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] transition duration-150">
                                                    <-- three-dots icon -->
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <x-dropdown-link :href="route('...')">View details</x-dropdown-link>
                                                <x-dropdown-link :href="route('...')">Edit</x-dropdown-link>
                                                <div class="border-t border-gray-100 dark:border-[#262632] my-1"></div>
                                                <form method="POST" action="..." onsubmit="return confirm('...');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition duration-150">
                                                        Delete
                                                    </button>
                                                </form>
                                            </x-slot>
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <-- Mobile Cards -->
                <div class="lg:hidden divide-y divide-gray-100 dark:divide-[#181820]">
                    @foreach ($items as $item)
                        <div class="p-4 hover:bg-gray-50/50 dark:hover:bg-[#181820]/50 transition-colors duration-150">
                            <-- Avatar + name + badge row -->
                            <-- Info row -->
                            <-- Action buttons row: bg-gray-50 dark:bg-[#181820] border-gray-200 dark:border-[#262632] rounded-lg text-xs font-medium -->
                        </div>
                    @endforeach
                </div>
            </div>

            ═══════ PAGINATION ═══════
            @if ($items->hasPages())
                <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Showing X to Y of Z items</span>
                    <div>{{ $items->links() }}</div>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
```

## Form Page (create/edit)

```
<x-app-layout>
    <div class="animate-fade-up">

        ═══════ HEADER ═══════
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Form Title</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Form description.</p>
            </div>
            <a href="{{ route('...') }}">
                <x-secondary-button type="button">Back</x-secondary-button>
            </a>
        </div>

        ═══════ ERRORS ═══════
        @if ($errors->any())
            <div class="mb-6"><x-alert variant="error">Please fix the errors below.</x-alert></div>
        @endif

        ═══════ FORM ═══════
        <form method="POST" action="..." x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <x-card>
                <div class="space-y-6">
                    <-- Form fields -->
                </div>
                <x-slot name="footer">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('...') }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                        <x-primary-button x-bind:disabled="submitting" type="submit">
                            <span x-show="!submitting">SAVE</span>
                            <span x-show="submitting" x-cloak>SAVING...</span>
                        </x-primary-button>
                    </div>
                </x-slot>
            </x-card>
        </form>
    </div>
</x-app-layout>
```

## Non-Negotiable Rules

1. **Every page opens with:** `<div class="animate-fade-up">`
2. **Page structure:** Header → Alerts → Empty State (or) Table/Form → Pagination
3. **Flash messages:** Always `<x-alert>`, wrapped in `<div class="mb-6">`
4. **CTA buttons:** `<a>` link with inline primary button classes (not `<x-primary-button>` wrapped in `<a>`)
5. **Submit buttons:** Use `<x-primary-button x-bind:disabled="submitting" type="submit">` with Alpine loading state. Never use `:disabled` (Blade expression syntax) — always `x-bind:disabled` so Alpine evaluates it.
6. **Table header:** `text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400`, `bg-gray-50/50 dark:bg-[#0e0e15]/50`
7. **Table rows:** `hover:bg-gray-50/70 dark:hover:bg-[#181820]/70 transition-all duration-150`
8. **Avatar squares:** `w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20`
9. **Form footer buttons:** Cancel uses `<x-secondary-button>`, submit uses inline primary button with Alpine loading
10. **Mobile sidebar:** A separate overlay (`fixed inset-0 z-50`) containing backdrop + slide-in panel using `x-show` + `x-transition` with `-translate-x-full` ↔ `translate-x-0`. The `<aside>` stays `hidden md:flex` untouched. Both feed from shared `_sidebar-nav.blade.php` partial.
