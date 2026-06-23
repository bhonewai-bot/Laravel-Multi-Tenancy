@props(['emptyTitle' => 'No records found', 'emptyDescription' => null])

<div {{ $attributes }}>
    {{-- Desktop table (hidden on mobile) --}}
    <div class="hidden lg:block rounded-lg border border-gray-200 dark:border-[#262632]">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#262632]">
            {{ $slot }}
        </table>
    </div>

    {{-- Mobile cards (hidden on desktop) --}}
    <div class="lg:hidden space-y-3">
        {{ $mobile ?? $slot }}
    </div>
</div>
