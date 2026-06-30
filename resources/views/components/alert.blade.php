@props(['variant' => 'info', 'title' => null, 'dismissible' => false])

@php
$config = [
    'success' => [
        'bg'    => 'bg-green-50 dark:bg-green-500/10',
        'border' => 'border-green-200 dark:border-green-500/20',
        'icon'  => 'text-green-500 dark:text-green-400',
        'title' => 'text-green-800 dark:text-green-300',
        'text'  => 'text-green-700 dark:text-green-400',
    ],
    'error' => [
        'bg'    => 'bg-red-50 dark:bg-red-500/10',
        'border' => 'border-red-200 dark:border-red-500/20',
        'icon'  => 'text-red-500 dark:text-red-400',
        'title' => 'text-red-800 dark:text-red-300',
        'text'  => 'text-red-700 dark:text-red-400',
    ],
    'warning' => [
        'bg'    => 'bg-amber-50 dark:bg-amber-500/10',
        'border' => 'border-amber-200 dark:border-amber-500/20',
        'icon'  => 'text-amber-500 dark:text-amber-400',
        'title' => 'text-amber-800 dark:text-amber-300',
        'text'  => 'text-amber-700 dark:text-amber-400',
    ],
    'info' => [
        'bg'    => 'bg-blue-50 dark:bg-blue-500/10',
        'border' => 'border-blue-200 dark:border-blue-500/20',
        'icon'  => 'text-blue-500 dark:text-blue-400',
        'title' => 'text-blue-800 dark:text-blue-300',
        'text'  => 'text-blue-700 dark:text-blue-400',
    ],
];

$c = $config[$variant] ?? $config['info'];
@endphp

<div
    {{ $attributes->merge(['class' => "rounded-lg border p-4 {$c['bg']} {$c['border']}"]) }}
    @if ($dismissible) x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @endif
>
    <div class="flex gap-3">
        {{-- Icon --}}
        <div class="shrink-0 {{ $c['icon'] }}">
            @if ($variant === 'success')
                <x-heroicon-o-check-circle class="w-5 h-5" />
            @elseif ($variant === 'error')
                <x-heroicon-o-x-circle class="w-5 h-5" />
            @elseif ($variant === 'warning')
                <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
            @else
                <x-heroicon-o-information-circle class="w-5 h-5" />
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            @if ($title)
                <p class="text-sm font-semibold {{ $c['title'] }}">{{ $title }}</p>
            @endif
            <div class="text-sm {{ $c['text'] }} {{ $title ? 'mt-1' : '' }}">
                {{ $slot }}
            </div>
        </div>

        {{-- Dismiss button --}}
        @if ($dismissible)
            <button type="button" @click="show = false" class="shrink-0 {{ $c['icon'] }} hover:opacity-70 transition-opacity">
                <x-heroicon-o-x-mark class="w-4 h-4" />
            </button>
        @endif
    </div>
</div>
