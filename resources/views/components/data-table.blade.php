@props(['emptyTitle' => 'No records found', 'emptyDescription' => null])

<div {{ $attributes }}>
    {{-- Desktop table (hidden on mobile) --}}
    <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 dark:border-[#2a2a38]">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#2a2a38]">
            {{ $slot }}
        </table>
    </div>

    {{-- Mobile cards (hidden on desktop) --}}
    <div class="lg:hidden space-y-3">
        {{ $mobile ?? $slot }}
    </div>
</div>
