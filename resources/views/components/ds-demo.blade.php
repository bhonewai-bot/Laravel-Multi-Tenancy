@props(['label'])

<div {{ $attributes->merge(['class' => '']) }}>
    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-2">{{ $label }}</p>
    <div class="p-4 rounded-lg bg-white dark:bg-[#101016] border border-gray-200 dark:border-[#262632]">
        {{ $slot }}
    </div>
</div>
