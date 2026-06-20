@props([
    'show' => false,
    'maxWidth' => '2xl',
])

@php
$maxWidthClass = match ($maxWidth) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    default => 'max-w-2xl',
};
@endphp

@if($show)
    @teleport('body')
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

            <div class="relative w-full {{ $maxWidthClass }} rounded-2xl bg-white shadow-2xl">
                {{ $slot }}
            </div>
        </div>
    @endteleport
@endif
