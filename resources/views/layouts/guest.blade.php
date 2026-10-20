<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dreamelect Logistics') }}</title>
        @include('layouts.partials.head-icons')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-brand-navy font-sans text-text-primary antialiased">
        <div class="relative min-h-screen overflow-hidden lg:grid lg:grid-cols-[1.15fr_1fr]">

            {{-- Brand side: an animated highway scene --}}
            <div class="relative hidden flex-col justify-between overflow-hidden bg-brand-navy lg:flex">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <span class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.035)_1px,transparent_1px)] bg-[size:48px_48px]"></span>
                    <span class="auth-float absolute left-[12%] top-[16%] h-10 w-10 border border-brand-primary/40"></span>
                    <span class="auth-float absolute left-[70%] top-[10%] h-6 w-6 bg-brand-primary/25" style="animation-delay:-2s"></span>
                    <span class="auth-float absolute left-[55%] top-[34%] h-14 w-14 border border-white/10" style="animation-delay:-4s"></span>

                    {{-- skyline: two parallax layers, each a seamless double-width strip --}}
                    <div class="absolute inset-x-0 bottom-24 h-40 overflow-hidden">
                        @foreach ([['slow', 'text-white/[0.04]', 150], ['', 'text-white/[0.08]', 110]] as [$speed, $tone, $h])
                            <svg class="auth-scroll {{ $speed }} absolute bottom-0 left-0 {{ $tone }}" style="height: {{ $h }}px" viewBox="0 0 1600 150" preserveAspectRatio="none" fill="currentColor">
                                <path d="M0 150V80h40V50h50v40h30V20h60v70h40V60h70v30h40V40h50v50h60V70h50v80zM800 150V80h40V50h50v40h30V20h60v70h40V60h70v30h40V40h50v50h60V70h50v80z" />
                                <path d="M0 150V100h60V70h40v30h50V40h70v60h50V80h60v20h70V60h60v90zM800 150V100h60V70h40v30h50V40h70v60h50V80h60v20h70V60h60v90z" opacity=".7" />
                            </svg>
                        @endforeach
                        <span class="auth-blink absolute bottom-10 left-[22%] h-1.5 w-1.5 bg-brand-gold"></span>
                        <span class="auth-blink absolute bottom-16 left-[48%] h-1.5 w-1.5 bg-brand-primary" style="animation-delay:-1s"></span>
                        <span class="auth-blink absolute bottom-8 left-[74%] h-1.5 w-1.5 bg-brand-gold" style="animation-delay:-2s"></span>
                    </div>

                    {{-- road --}}
                    <div class="absolute inset-x-0 bottom-0 h-24 border-t-2 border-brand-primary bg-[#0F1B35]">
                        <div class="absolute inset-x-0 top-1/2 h-0.5 overflow-hidden">
                            <div class="auth-scroll fast h-full bg-[repeating-linear-gradient(90deg,rgba(255,255,255,.55)_0_28px,transparent_28px_56px)]"></div>
                        </div>
                    </div>

                    {{-- truck --}}
                    <div class="auth-truck absolute bottom-[3.4rem] left-0 w-44">
                        <svg viewBox="0 0 78 40" fill="none">
                            <rect x="8" y="6" width="42" height="24" fill="#fff" />
                            <rect x="12" y="10" width="34" height="2" fill="#E8622C" />
                            <path d="M50 12h12l8 9v9H50z" fill="#E8622C" />
                            <path d="M54 15h7l5 6h-12z" fill="#fff" opacity=".85" />
                            <rect x="8" y="30" width="62" height="3" fill="#fff" />
                            <path d="M70 26h6v3h-6z" fill="#C9972E" />
                            <circle cx="22" cy="34" r="5" fill="#0B1224" stroke="#fff" stroke-width="1.5" />
                            <circle cx="60" cy="34" r="5" fill="#0B1224" stroke="#fff" stroke-width="1.5" />
                        </svg>
                        <span class="absolute -right-20 top-[48%] h-3 w-24 bg-gradient-to-r from-brand-gold/50 to-transparent"></span>
                    </div>
                </div>

                <div class="relative p-12 xl:p-16">
                    <a href="{{ route('login') }}" class="auth-rise inline-flex w-fit items-center">
                        <x-brand-logo class="h-14 w-auto" />
                    </a>
                </div>

                <div class="relative p-12 pb-44 xl:p-16 xl:pb-48">
                    <p class="auth-rise mb-4 inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.25em] text-brand-primary" style="animation-delay:.1s">
                        <span class="h-px w-8 bg-brand-primary"></span> {{ __('Dispatch & Accounting') }}
                    </p>
                    <h1 class="auth-rise max-w-lg text-4xl font-extrabold leading-[1.08] tracking-tight text-white xl:text-5xl" style="animation-delay:.2s">
                        {{ __('Reliable loads,') }}<br>
                        <span class="text-brand-primary">{{ __('anytime you need.') }}</span>
                    </h1>
                    <p class="auth-rise mt-4 max-w-md text-base text-white/60" style="animation-delay:.35s">
                        {{ __('Book it, move it, bill it — loads, drivers, invoices and books in one place.') }}
                    </p>

                    <div class="mt-8 flex max-w-lg gap-px bg-white/10">
                        @foreach ([[__('Loads'), __('Booked → Paid'), 'truck'], [__('Invoices'), __('PDF in a click'), 'file-text'], [__('Books'), __('Always in sync'), 'wallet']] as $i => [$title, $sub, $icon])
                            <div class="auth-rise flex-1 bg-brand-navy/90 p-4 backdrop-blur" style="animation-delay: {{ 0.5 + $i * 0.12 }}s">
                                <span class="text-brand-primary"><x-icon :name="$icon" :size="18" /></span>
                                <p class="mt-2 text-sm font-semibold text-white">{{ $title }}</p>
                                <p class="text-xs text-white/50">{{ $sub }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Form side --}}
            <div class="relative flex min-h-screen items-center justify-center bg-bg px-5 py-10 sm:px-10">
                <span class="absolute inset-x-0 top-0 h-1 bg-brand-primary lg:hidden"></span>
                <div class="w-full max-w-md">
                    <a href="{{ route('login') }}" class="mb-6 flex justify-center lg:hidden">
                        <x-brand-logo :chip="true" class="h-12 w-auto" />
                    </a>

                    <div class="auth-rise relative border border-border bg-white p-8 shadow-2xl shadow-brand-navy/10 sm:p-10" style="animation-delay:.15s">
                        <span class="absolute inset-x-0 top-0 h-1 bg-brand-primary"></span>
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-xs text-text-muted">
                        {{ __('We Drive Your Dreams!') }} &middot; &copy; {{ now()->year }} {{ config('app.name', 'Dreamelect Logistics') }} LLC
                    </p>
                </div>
            </div>
        </div>
        <div id="page-progress"></div>
        <div id="page-loader" aria-hidden="true"><div><x-truck-loader /><p>{{ __('Signing you in') }}</p></div></div>
    </body>
</html>
