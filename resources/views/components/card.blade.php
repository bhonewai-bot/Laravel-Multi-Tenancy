@props(['header' => null, 'footer' => null])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-[#14141c] rounded-lg border border-gray-200 dark:border-[#2a2a38] shadow-sm']) }}>
    @if ($header)
        <div class="px-6 py-4 border-b border-gray-200 dark:border-[#2a2a38]">
            {{ $header }}
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-6 py-4 border-t border-gray-200 dark:border-[#2a2a38] bg-gray-50 dark:bg-[#0e0e15] rounded-b-lg">
            {{ $footer }}
        </div>
    @endif
</div>
