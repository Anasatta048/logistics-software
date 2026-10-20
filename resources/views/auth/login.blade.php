<x-guest-layout>
    <div class="mb-8">
        <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-brand-primary"><span class="h-px w-6 bg-brand-primary"></span> {{ __('Dispatch portal') }}</span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-text-primary">{{ __('Welcome back') }}</h1>
        <p class="mt-2 text-sm text-text-muted">{{ __('Log in to your dispatch dashboard.') }}</p>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert variant="error" class="mb-4">{{ __('Invalid email or password.') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ submitting: false, show: false }" @submit="submitting = true" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="text-sm font-medium text-text-primary">{{ __('Email') }}</label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-text-muted">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/></svg>
                </span>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    placeholder="you@dreamelectlogistics.com"
                    class="block w-full rounded-xl border-border bg-bg py-3 pl-11 pr-4 text-sm text-text-primary placeholder:text-text-muted/70 transition focus:border-brand-primary focus:bg-white focus:ring-2 focus:ring-brand-primary/20">
            </div>
        </div>

        <div>
            <label for="password" class="text-sm font-medium text-text-primary">{{ __('Password') }}</label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-text-muted">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                </span>
                <input id="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                    x-bind:type="show ? 'text' : 'password'" type="password"
                    class="block w-full rounded-xl border-border bg-bg py-3 pl-11 pr-12 text-sm text-text-primary placeholder:text-text-muted/70 transition focus:border-brand-primary focus:bg-white focus:ring-2 focus:ring-brand-primary/20">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center px-3.5 text-text-muted transition hover:text-brand-primary"
                    x-bind:aria-label="show ? 'Hide password' : 'Show password'">
                    <x-icon name="eye" :size="18" />
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex cursor-pointer items-center">
                <input id="remember_me" type="checkbox" name="remember" class="rounded border-border text-brand-primary shadow-sm focus:ring-brand-primary">
                <span class="ms-2 text-sm text-text-muted">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand-primary hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit" x-bind:disabled="submitting"
            class="group flex w-full items-center justify-center gap-2 rounded-xl bg-brand-primary px-4 py-3.5 text-sm font-bold uppercase tracking-wider text-white transition hover:bg-[#d4541f] focus:outline-none focus:ring-4 focus:ring-brand-primary/30 disabled:opacity-70">
            <x-truck-loader size="sm" x-show="submitting" x-cloak class="!text-white" />
            <span x-text="submitting ? '{{ __('Signing you in…') }}' : '{{ __('Log In') }}'"></span>
            <x-icon name="chevron-right" :size="16" class="transition-transform group-hover:translate-x-0.5" x-show="!submitting" />
        </button>
    </form>
</x-guest-layout>
