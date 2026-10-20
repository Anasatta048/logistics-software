<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>
        @include('layouts.partials.head-icons')

        {{-- Applied before first paint so switching theme never flashes the
             wrong mode (Section 2.1's light/dark toggle). Falls back to the
             user's saved theme_mode, then system preference. --}}
        <script>
            (function () {
                try {
                    var saved = localStorage.getItem('theme');
                    var serverMode = @json(Auth::user()->theme_mode ?? 'system');
                    var mode = saved || serverMode;
                    var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (dark) document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        {{-- Sidebar width must be right on first paint, so the saved collapsed
             state is applied here, before any CSS-driven layout happens. --}}
        <script>
            try { if (localStorage.getItem('sb-collapsed') === '1') document.documentElement.classList.add('sb-collapsed'); } catch (e) {}
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Realtime (Reverb): resolved at request time so one build works on any domain -->
        @php
            $reverbClient = [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('reverb.public.host'),
                'port' => config('reverb.public.port'),
                'scheme' => config('reverb.public.scheme'),
            ];
        @endphp
        <meta name="reverb" content="{{ json_encode($reverbClient) }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @php($navPosition = 'sidebar')
        <div class="flex h-screen overflow-hidden bg-bg dark:bg-bg-dark">
            @if ($navPosition === 'sidebar')
                @include('layouts.partials.sidebar')
            @endif

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                @include('layouts.partials.header')


                <main class="relative flex-1 overflow-y-auto">
                    @if (session('impersonating_tenant_id') && auth()->user()?->is_super_admin)
                        <div class="sticky top-0 z-30 flex flex-wrap items-center gap-3 bg-brand-gold px-4 py-2 text-sm font-semibold text-white">
                            <x-icon name="eye" :size="16" />
                            <span class="min-w-0 flex-1">{{ __('Viewing workspace :name as super admin', ['name' => app(\App\Support\Tenancy\TenantContext::class)->tenant()?->name]) }}</span>
                            <form method="POST" action="{{ route('super-admin.tenants.leave') }}">
                                @csrf
                                <button class="bg-white/20 px-3 py-1 text-xs font-bold hover:bg-white/30">{{ __('Exit workspace') }}</button>
                            </form>
                        </div>
                    @endif
                    @php($ws = app(\App\Support\Tenancy\TenantContext::class)->tenant())
                    @if ($ws && ! auth()->user()?->is_super_admin && auth()->user()?->can('settings.manage') && ($ends = $ws->endsAt()))
                        @php($daysLeft = (int) ceil(now()->diffInDays($ends, false)))
                        @if ($ws->status === \App\Enums\TenantStatus::Trial || $daysLeft <= 7)
                            <div class="flex flex-wrap items-center gap-3 px-4 py-2 text-sm font-semibold {{ $daysLeft <= 3 ? 'bg-status-cancelled text-white' : 'bg-brand-navy text-white' }}">
                                <x-icon name="clock" :size="16" />
                                <span class="min-w-0 flex-1">
                                    @if ($ws->status === \App\Enums\TenantStatus::Trial)
                                        {{ $daysLeft > 0 ? __('Free trial: :n day(s) left.', ['n' => $daysLeft]) : __('Your free trial ends today.') }}
                                    @else
                                        {{ $daysLeft > 0 ? __('Your subscription renews in :n day(s).', ['n' => $daysLeft]) : __('Your subscription period ends today.') }}
                                    @endif
                                    @if (config('saas.support_email'))
                                        <a class="underline" href="mailto:{{ config('saas.support_email') }}">{{ __('Contact us to continue') }}</a>
                                    @endif
                                </span>
                            </div>
                        @endif
                    @endif
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{-- A form sent back with validation errors: the messages stay inline
                             under their fields and are also raised as toasts (lifted by toasts.js). --}}
                        @if ($errors->any())
                            @php($toastErrors = collect($errors->all())->unique()->values())
                            @foreach ($toastErrors->take(4) as $message)
                                <div data-alert="error" hidden>{{ $message }}</div>
                            @endforeach
                            @if ($toastErrors->count() > 4)
                                <div data-alert="error" hidden>{{ __('…and :count more to fix below.', ['count' => $toastErrors->count() - 4]) }}</div>
                            @endif
                        @endif
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <div id="page-progress"></div>
        <div id="page-loader" aria-hidden="true"><div><x-truck-loader /><p>{{ __('Loading') }}</p></div></div>

        @include('layouts.partials.mobile-nav')

        @stack('scripts')
    </body>
</html>
