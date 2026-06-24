import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    window.Alpine.store('theme', {
        dark: false,

        init() {
            const stored = localStorage.getItem('theme');
            if (stored) {
                this.dark = stored === 'dark';
            } else {
                this.dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
            this.apply();
        },

        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
            this.apply();
        },

        apply() {
            document.documentElement.classList.toggle('dark', this.dark);
        },
    });

    window.Alpine.store('sidebar', {
        collapsed: false,
        mobileOpen: false,

        init() {
            this.collapsed = localStorage.getItem('sidebar') === 'collapsed';
        },

        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar', this.collapsed ? 'collapsed' : 'expanded');
        },

        toggleMobile() {
            this.mobileOpen = !this.mobileOpen;
        },

        closeMobile() {
            this.mobileOpen = false;
        },
    });
});

Livewire.start();
