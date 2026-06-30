@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white dark:bg-[#101016]'])

@php
$originClass = match ($align) {
    'left' => 'origin-top-left',
    'top' => 'origin-top',
    default => 'origin-top-right',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div class="relative"
    x-data="{ open: false, panelId: '' }"
    @close.stop="open = false"
    @keydown.escape.window="open = false"
    x-init="
        panelId = 'dd-panel-' + Math.random().toString(36).slice(2, 9);
        $nextTick(() => {
            let trigger = $el.querySelector('[data-dropdown-trigger]');
            if (trigger) trigger.setAttribute('data-dropdown-trigger', panelId);
            window.addEventListener('click', (e) => {
                if (! open) return;
                let panel = document.getElementById(panelId);
                if ($el.contains(e.target) || (panel && panel.contains(e.target))) return;
                open = false;
            });
        })
    ">
    <div @click="
        open = ! open;
        if (open) {
            $nextTick(() => {
                let panel = document.getElementById(panelId);
                if (! panel) return;
                let container = panel.parentElement;
                let rect = $el.getBoundingClientRect();
                container.style.position = 'fixed';
                container.style.top = (rect.bottom + 8) + 'px';
                @if ($align === 'left')
                    container.style.left = rect.left + 'px';
                @else
                    container.style.right = (window.innerWidth - rect.right) + 'px';
                @endif
            });
        }
    " data-dropdown-trigger>
        {{ $trigger }}
    </div>

    <template x-teleport="body">
        <div x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed z-50 {{ $width }} rounded-xl shadow-elevated {{ $originClass }}"
                style="display: none;">
            <div class="rounded-xl border border-gray-200 dark:border-[#262632] shadow-elevated {{ $contentClasses }}"
                @click="open = false"
                :id="panelId"
                data-dropdown-content>
                {{ $content }}
            </div>
        </div>
    </template>
</div>
