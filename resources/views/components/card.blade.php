@props(['header' => null, 'footer' => null])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-[#101016] rounded-xl border border-gray-200 dark:border-[#262632] shadow-card hover:shadow-card-hover transition-shadow duration-300']) }}>
    @if ($header)
        <div class="px-6 py-4 border-b border-gray-200 dark:border-[#262632]">
            {{ $header }}
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-6 py-4 border-t border-gray-200 dark:border-[#262632] bg-gray-50 dark:bg-[#0c0c12] rounded-b-xl">
            {{ $footer }}
        </div>
    @endif
</div>
