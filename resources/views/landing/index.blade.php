<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Dispatch, billing and books in one place</title>
    <meta name="description" content="Run your trucking dispatch and brokerage from one dashboard: loads, drivers, invoices, commissions and accounts.">
    @include('layouts.partials.head-icons')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes lp-drive { from { transform: translateX(-30vw); } to { transform: translateX(110vw); } }
        @keyframes lp-dash { to { background-position-x: -112px; } }
        @keyframes lp-rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
        .lp-truck { animation: lp-drive 14s linear infinite; }
        .lp-road { background: repeating-linear-gradient(90deg, rgba(255,255,255,.5) 0 28px, transparent 28px 56px); background-size: 112px 100%; animation: lp-dash 1.2s linear infinite; }
        .lp-rise { animation: lp-rise .7s both; }
        @media (prefers-reduced-motion: reduce) { .lp-truck, .lp-road, .lp-rise { animation: none; } }
    </style>
</head>
<body class="bg-white font-sans text-text-primary antialiased">

{{-- Top bar --}}
<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/10 bg-brand-navy/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center gap-6 px-5 py-3.5">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white">
            <span class="grid h-9 w-9 place-items-center bg-brand-primary"><x-icon name="truck" :size="20" /></span>
            <span class="text-lg font-extrabold tracking-tight">{{ config('app.name') }}</span>
        </a>
        <nav class="ml-auto hidden items-center gap-7 text-sm font-medium text-white/70 md:flex">
            <a href="#features" class="hover:text-white">{{ __('Features') }}</a>
            <a href="#how" class="hover:text-white">{{ __('How it works') }}</a>
            <a href="#pricing" class="hover:text-white">{{ __('Pricing') }}</a>
            @auth
                <a href="{{ route(auth()->user()->homeRouteName()) }}" class="btn-ghost btn-sm !border-white/30 !text-white">{{ __('Open dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="hover:text-white">{{ __('Log in') }}</a>
                <a href="#request" class="btn-primary btn-sm">{{ __('Request access') }}</a>
            @endauth
        </nav>
        <button @click="open = !open" class="ml-auto text-white md:hidden" aria-label="Menu"><x-icon name="menu" :size="24" /></button>
    </div>
    <div x-show="open" x-cloak @click.outside="open = false" class="space-y-3 border-t border-white/10 px-5 pb-4 pt-3 text-sm font-medium text-white/80 md:hidden">
        <a @click="open = false" href="#features" class="block">{{ __('Features') }}</a>
        <a @click="open = false" href="#how" class="block">{{ __('How it works') }}</a>
        <a @click="open = false" href="#pricing" class="block">{{ __('Pricing') }}</a>
        <a href="{{ route('login') }}" class="block">{{ __('Log in') }}</a>
        <a @click="open = false" href="#request" class="btn-primary w-full">{{ __('Request access') }}</a>
    </div>
</header>

{{-- Hero --}}
<section class="relative overflow-hidden bg-brand-navy text-white">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <span class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.035)_1px,transparent_1px)] bg-[size:48px_48px]"></span>
        <span class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-primary/20 blur-3xl"></span>
        <span class="absolute -left-24 bottom-10 h-80 w-80 rounded-full bg-brand-navy-light/40 blur-3xl"></span>
    </div>

    <div class="relative mx-auto grid max-w-7xl gap-12 px-5 pb-40 pt-16 lg:grid-cols-[1.1fr_1fr] lg:pt-24">
        <div class="lp-rise">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.25em] text-brand-primary"><span class="h-px w-8 bg-brand-primary"></span> {{ __('Dispatch & brokerage software') }}</span>
            <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                {{ __('Book it. Move it.') }}<br><span class="text-brand-primary">{{ __('Bill it.') }}</span>
            </h1>
            <p class="mt-5 max-w-xl text-lg text-white/65">
                {{ __('Loads, drivers, invoices, commissions and your books — one clean dashboard for trucking dispatchers and brokers. No spreadsheets, no chasing.') }}
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#request" class="btn-primary !px-6 !py-3 !text-sm">{{ __('Request access') }} <x-icon name="arrow-left" :size="16" class="rotate-180" /></a>
                <a href="#pricing" class="btn-ghost !border-white/30 !px-6 !py-3 !text-sm !text-white hover:!border-brand-primary">{{ __('See plans') }}</a>
            </div>
            <dl class="mt-10 grid max-w-md grid-cols-3 gap-px bg-white/10 text-center">
                @foreach ([['14 days', __('free trial')], ['5 min', __('to get started')], ['0', __('setup fees')]] as [$n, $l])
                    <div class="bg-brand-navy p-3"><dt class="text-lg font-extrabold text-white">{{ $n }}</dt><dd class="text-xs text-white/50">{{ $l }}</dd></div>
                @endforeach
            </dl>
        </div>

        {{-- Product mock --}}
        <div class="lp-rise relative hidden lg:block" style="animation-delay:.2s" aria-hidden="true">
            <div class="border border-white/10 bg-white p-4 text-text-primary shadow-2xl shadow-black/40">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <p class="text-sm font-bold">{{ __('Active loads') }}</p>
                    <span class="bg-status-paid/15 px-2 py-0.5 text-[10px] font-bold text-status-paid">LIVE</span>
                </div>
                @foreach ([['#1042', 'Dallas, TX → Atlanta, GA', 'In transit', 'bg-status-dispatched'], ['#1043', 'Chicago, IL → Denver, CO', 'Loaded', 'bg-status-loaded'], ['#1044', 'Miami, FL → Houston, TX', 'Booked', 'bg-status-booked'], ['#1041', 'Phoenix, AZ → Reno, NV', 'Paid', 'bg-status-paid']] as [$id, $route, $st, $c])
                    <div class="flex items-center gap-3 border-b border-border/60 py-2.5 text-xs last:border-0">
                        <span class="font-bold text-brand-primary">{{ $id }}</span>
                        <span class="flex-1 truncate text-text-muted">{{ $route }}</span>
                        <span class="inline-flex items-center gap-1.5 font-semibold"><span class="h-1.5 w-1.5 rounded-full {{ $c }}"></span>{{ $st }}</span>
                    </div>
                @endforeach
                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    @foreach ([['$48.2k', __('Revenue')], ['$12.9k', __('Margin')], ['27', __('Loads')]] as [$n, $l])
                        <div class="bg-bg p-2"><p class="text-sm font-extrabold">{{ $n }}</p><p class="text-[10px] text-text-muted">{{ $l }}</p></div>
                    @endforeach
                </div>
            </div>
            <div class="absolute -bottom-6 -left-6 w-56 border border-white/10 bg-brand-primary p-4 text-white shadow-xl">
                <p class="text-[11px] font-bold uppercase tracking-wider opacity-80">{{ __('Invoice paid') }}</p>
                <p class="mt-1 text-2xl font-extrabold">$3,450.00</p>
                <p class="text-xs opacity-80">{{ __('Commission accrued automatically') }}</p>
            </div>
        </div>
    </div>

    {{-- Road with driving truck --}}
    <div class="absolute inset-x-0 bottom-0 h-20 border-t-2 border-brand-primary bg-[#0F1B35]" aria-hidden="true">
        <div class="lp-road absolute inset-x-0 top-1/2 h-0.5"></div>
        <div class="lp-truck absolute bottom-[1.6rem] left-0 w-32">
            <svg viewBox="0 0 78 40" fill="none">
                <rect x="8" y="6" width="42" height="24" fill="#fff" /><rect x="12" y="10" width="34" height="2" fill="#E8622C" />
                <path d="M50 12h12l8 9v9H50z" fill="#E8622C" /><path d="M54 15h7l5 6h-12z" fill="#fff" opacity=".85" />
                <rect x="8" y="30" width="62" height="3" fill="#fff" />
                <circle cx="22" cy="34" r="5" fill="#0B1224" stroke="#fff" stroke-width="1.5" /><circle cx="60" cy="34" r="5" fill="#0B1224" stroke="#fff" stroke-width="1.5" />
            </svg>
        </div>
    </div>
</section>

{{-- Features --}}
<section id="features" class="mx-auto max-w-7xl px-5 py-20">
    <div class="mx-auto max-w-2xl text-center">
        <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-brand-primary">{{ __('Everything in one place') }}</p>
        <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Built for how dispatch really works') }}</h2>
    </div>
    <div class="mt-12 grid gap-px bg-border sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            ['truck', __('Load management'), __('Book a load, assign a driver, and move it from booked to paid with one-click status updates.')],
            ['users', __('Drivers & brokers'), __('Keep every driver, broker and company in one directory, with their loads and balances attached.')],
            ['file-text', __('Invoices in a click'), __('Generate branded PDF invoices from your loads and record payments as they arrive.')],
            ['percent', __('Automatic commissions'), __('Dispatcher commissions are worked out the moment a load is paid — and reversed if it is cancelled.')],
            ['wallet', __('Real accounting'), __('Expenses, accounts, journal vouchers and statements that stay in sync with your loads.')],
            ['shield', __('Roles & rights'), __('Give dispatchers, accountants and viewers exactly the access they need, nothing more.')],
        ] as $i => [$icon, $title, $text])
            <div class="bg-white p-7 transition hover:bg-bg">
                <span class="grid h-12 w-12 place-items-center bg-brand-navy text-white"><x-icon :name="$icon" :size="22" /></span>
                <h3 class="mt-5 text-lg font-bold">{{ $title }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-text-muted">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- How it works --}}
<section id="how" class="bg-bg py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-brand-primary">{{ __('Simple start') }}</p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Up and running in three steps') }}</h2>
        </div>
        <ol class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([[__('Request access'), __('Tell us about your fleet and pick a plan. It takes a minute.')], [__('We set you up'), __('We review your request and create your workspace, usually within a day.')], [__('Start dispatching'), __('Log in, add your drivers and companies, and book your first load.')]] as $i => [$t, $d])
                <li class="relative border border-border bg-white p-7">
                    <span class="absolute -top-4 left-7 grid h-8 w-8 place-items-center bg-brand-primary text-sm font-extrabold text-white">{{ $i + 1 }}</span>
                    <h3 class="mt-2 text-lg font-bold">{{ $t }}</h3>
                    <p class="mt-2 text-sm text-text-muted">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- Pricing --}}
<section id="pricing" class="mx-auto max-w-7xl px-5 py-20" x-data="{ yearly: false }">
    <div class="mx-auto max-w-2xl text-center">
        <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-brand-primary">{{ __('Pricing') }}</p>
        <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Pick the plan that fits your fleet') }}</h2>
        <div class="mt-6 inline-flex border border-border p-1 text-sm font-semibold">
            <button @click="yearly = false" :class="!yearly ? 'bg-brand-navy text-white' : 'text-text-muted'" class="px-4 py-1.5">{{ __('Monthly') }}</button>
            <button @click="yearly = true" :class="yearly ? 'bg-brand-navy text-white' : 'text-text-muted'" class="px-4 py-1.5">{{ __('Yearly') }} @if ($saving = $plans->max(fn ($p) => $p->yearlySavingPercent()))<span class="text-brand-primary" :class="yearly && '!text-white/80'">{{ __('save up to') }} {{ $saving }}%</span>@endif</button>
        </div>
    </div>

    <div class="mt-12 grid gap-6 lg:grid-cols-3">
        @forelse ($plans as $plan)
            <article class="relative flex flex-col border bg-white p-8 {{ $plan->is_featured ? 'border-brand-primary shadow-2xl shadow-brand-primary/15 lg:-translate-y-3' : 'border-border' }}">
                @if ($plan->is_featured)<span class="absolute -top-3 left-8 bg-brand-primary px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white">{{ __('Most popular') }}</span>@endif
                <h3 class="text-xl font-extrabold">{{ $plan->name }}</h3>
                <p class="mt-1 min-h-[2.5rem] text-sm text-text-muted">{{ $plan->tagline }}</p>
                @if ($plan->hasPkrPrice())
                    <p class="mt-5 flex items-baseline gap-1">
                        <span class="text-4xl font-extrabold tracking-tight" x-text="'Rs ' + (yearly ? {{ (int) round($plan->yearly_price_pkr / 12) }} : {{ (int) $plan->monthly_price_pkr }}).toLocaleString('en-US')">Rs {{ number_format($plan->monthly_price_pkr) }}</span>
                        <span class="text-sm text-text-muted">/ {{ __('month') }}</span>
                    </p>
                    <p class="text-xs text-text-muted"><span x-text="'$' + (yearly ? {{ (int) round($plan->yearly_price / 12) }} : {{ (int) $plan->monthly_price }})">${{ (int) $plan->monthly_price }}</span> / {{ __('month') }} <span x-show="yearly" x-cloak>· Rs {{ number_format($plan->yearly_price_pkr) }} {{ __('billed yearly') }}@if ($plan->yearlySavingPercent() > 0) ({{ __('save') }} {{ $plan->yearlySavingPercent() }}%)@endif</span></p>
                @else
                    <p class="mt-5 flex items-baseline gap-1">
                        <span class="text-5xl font-extrabold tracking-tight" x-text="'$' + (yearly ? {{ (int) round($plan->yearly_price / 12) }} : {{ (int) $plan->monthly_price }})">${{ (int) $plan->monthly_price }}</span>
                        <span class="text-sm text-text-muted">/ {{ __('month') }}</span>
                    </p>
                    <p class="text-xs text-text-muted" x-show="yearly" x-cloak>${{ number_format($plan->yearly_price, 0) }} {{ __('billed yearly') }}</p>
                @endif
                @if ($plan->trial_days)<p class="mt-1 text-xs font-semibold text-status-paid">{{ $plan->trial_days }}-{{ __('day free trial') }}</p>@endif

                <ul class="mt-6 flex-1 space-y-2.5 text-sm">
                    @foreach ([[__('users'), $plan->max_users], [__('drivers'), $plan->max_drivers], [__('loads / month'), $plan->max_loads_per_month]] as [$l, $v])
                        <li class="flex gap-2 font-semibold"><x-icon name="check" :size="16" class="mt-0.5 shrink-0 text-brand-primary" /> {{ \App\Models\Plan::limit($v) }} {{ $l }}</li>
                    @endforeach
                    @foreach ($plan->features ?? [] as $feature)
                        <li class="flex gap-2 text-text-muted"><x-icon name="check" :size="16" class="mt-0.5 shrink-0 text-status-paid" /> {{ $feature }}</li>
                    @endforeach
                </ul>

                <a href="#request" data-plan="{{ $plan->id }}" onclick="document.getElementById('plan_id').value = this.dataset.plan"
                   class="mt-8 {{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} !py-3">{{ __('Choose :plan', ['plan' => $plan->name]) }}</a>
            </article>
        @empty
            <p class="col-span-full text-center text-text-muted">{{ __('Plans are coming soon — request access and we will be in touch.') }}</p>
        @endforelse
    </div>
</section>

{{-- Request access --}}
<section id="request" class="relative overflow-hidden bg-brand-navy py-20 text-white">
    <span class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.035)_1px,transparent_1px)] bg-[size:48px_48px]"></span>
    <div class="relative mx-auto grid max-w-6xl gap-12 px-5 lg:grid-cols-[1fr_1.15fr]">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-brand-primary">{{ __('Get started') }}</p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Request your workspace') }}</h2>
            <p class="mt-4 max-w-md text-white/65">{{ __('Share a few details and we will set up your account. No payment needed to request.') }}</p>
            <ul class="mt-8 space-y-4 text-sm">
                @foreach ([[__('Reply within one business day'), 'clock'], [__('Free trial on every plan'), 'sparkles'], [__('Your data stays yours'), 'lock']] as [$t, $i])
                    <li class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center bg-white/10 text-brand-primary"><x-icon :name="$i" :size="18" /></span> {{ $t }}</li>
                @endforeach
            </ul>
        </div>

        <div class="border border-white/10 bg-white p-7 text-text-primary shadow-2xl sm:p-9">
            @if (session('request_sent'))
                <div class="py-10 text-center">
                    <span class="mx-auto grid h-16 w-16 place-items-center bg-status-paid/15 text-status-paid"><x-icon name="check-circle" :size="34" /></span>
                    <h3 class="mt-5 text-2xl font-extrabold">{{ __('Request received!') }}</h3>
                    <p class="mt-2 text-sm text-text-muted">{{ __('Thanks — we will review it and get back to you by email very soon.') }}</p>
                    <a href="{{ route('home') }}" class="btn-ghost btn-sm mt-6">{{ __('Send another') }}</a>
                </div>
            @else
                <form method="POST" action="{{ route('request-access.store') }}" class="grid gap-4 sm:grid-cols-2" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    @if ($errors->any())
                        <x-alert variant="error" class="sm:col-span-2">{{ __('Please fix the highlighted fields and try again.') }}</x-alert>
                    @endif
                    <x-field name="company_name" :label="__('Company name')" icon="building" required class="sm:col-span-2">
                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required placeholder="Acme Freight LLC">
                    </x-field>
                    <x-field name="contact_name" :label="__('Your name')" icon="user" required>
                        <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}" required>
                    </x-field>
                    <x-field name="email" :label="__('Work email')" icon="mail" required>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </x-field>
                    <x-field name="phone" :label="__('Phone')" icon="phone">
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}">
                    </x-field>
                    <x-field name="fleet_size" :label="__('Fleet size')" icon="truck">
                        <select id="fleet_size" name="fleet_size">
                            <option value="">{{ __('Select…') }}</option>
                            @foreach (['1-5', '6-20', '21-50', '51-200', '200+'] as $size)
                                <option value="{{ $size }}" @selected(old('fleet_size') === $size)>{{ $size }} {{ __('trucks') }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field name="plan_id" :label="__('Plan')" icon="tag" class="sm:col-span-2">
                        <select id="plan_id" name="plan_id">
                            <option value="">{{ __('Not sure yet') }}</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>{{ $plan->name }} — ${{ (int) $plan->monthly_price }}/{{ __('mo') }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field name="message" :label="__('Anything we should know?')" class="sm:col-span-2">
                        <textarea id="message" name="message" rows="3">{{ old('message') }}</textarea>
                    </x-field>
                    <button type="submit" :disabled="busy" class="btn-primary !py-3 sm:col-span-2">{{ __('Request access') }} <x-icon name="arrow-left" :size="16" class="rotate-180" /></button>
                    <p class="text-center text-xs text-text-muted sm:col-span-2">{{ __('Already have an account?') }} <a href="{{ route('login') }}" class="font-semibold text-brand-primary">{{ __('Log in') }}</a></p>
                </form>
            @endif
        </div>
    </div>
</section>

<footer class="bg-[#0F1B35] py-8 text-center text-xs text-white/50">
    <div class="text-white/60">@include('legal.links')</div>
    <p class="mt-3">&copy; {{ now()->year }} {{ config('saas.legal.company') }}. {{ __('All rights reserved.') }}</p>
</footer>

</body>
</html>
