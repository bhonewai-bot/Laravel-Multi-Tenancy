@props(['label'])

<p {{ $attributes->merge(['class' => 'text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3']) }}>{{ $label }}</p>
