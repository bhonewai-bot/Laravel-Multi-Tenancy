@props(['items' => []])

@if (count($items) > 0)
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
        </svg>
        @foreach ($items as $index => $item)
            @if ($index > 0)
                <svg class="w-4 h-4 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            @endif
            @if (isset($item['href']))
                <a href="{{ $item['href'] }}" class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    {{ $item['label'] }}
                </a>
            @else
                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
