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
        x-data="{ submitting: false }"
        @submit="submitting = true"
        class="space-y-5"
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
            <x-auth-button>Sign in</x-auth-button>
        </div>
    </form>
</x-guest-layout>
