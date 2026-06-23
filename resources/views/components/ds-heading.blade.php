@props(['title', 'description' => null])

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $title }}</h2>
    @if ($description)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
</div>
