@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

<x-guest-layout>
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Welcome back
        </h1>
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
            @if (tenant())
                Sign in to your workspace
            @else
                Sign in to your account to continue
            @endif
        </p>
    </div>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="mb-6 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-700 dark:text-green-400">{{ session('status') }}</p>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('login', absolute: false) }}"
        class="space-y-5"
        x-data="{ submitting: false }"
        @submit="submitting = true"
    >
        @csrf

        {{-- Email --}}
        <x-auth-input
            name="email"
            type="email"
            label="Email address"
            :required="true"
            :autofocus="true"
            autocomplete="username"
        />

        {{-- Password --}}
        <x-auth-input
            name="password"
            type="password"
            label="Password"
            :required="true"
            autocomplete="current-password"
        />

        {{-- Submit --}}
        <div class="pt-1">
            <button
                type="submit"
                class="group relative w-full flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-white rounded-lg bg-brand-600 hover:bg-brand-700 active:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] shadow-card hover:shadow-md transition-all duration-200 ease-in-out disabled:opacity-75 disabled:cursor-not-allowed"
                :disabled="submitting"
            >
                <span x-show="!submitting" class="flex items-center gap-2">
                    Sign in
                    <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </span>
                <span x-show="submitting" x-cloak class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    Signing in...
                </span>
            </button>
        </div>
    </form>
</x-guest-layout>
