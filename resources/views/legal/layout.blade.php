<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name') }}</title>
    @include('layouts.partials.head-icons')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-text-primary antialiased">
<header class="border-b border-white/10 bg-brand-navy">
    <div class="mx-auto flex max-w-3xl items-center gap-3 px-5 py-3.5">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white">
            <span class="grid h-9 w-9 place-items-center bg-brand-primary"><x-icon name="truck" :size="20" /></span>
            <span class="text-lg font-extrabold tracking-tight">{{ config('app.name') }}</span>
        </a>
        <a href="{{ route('home') }}" class="ml-auto text-sm text-white/70 hover:text-white">&larr; {{ __('Back to home') }}</a>
    </div>
</header>

<main class="mx-auto max-w-3xl px-5 py-12">
    <h1 class="text-3xl font-extrabold tracking-tight">{{ $title }}</h1>
    <p class="mt-1 text-sm text-text-muted">{{ __('Last updated') }} {{ $updated }}</p>
    <div class="mt-8 space-y-5 text-[15px] leading-relaxed text-text-primary [&_h2]:mt-8 [&_h2]:text-lg [&_h2]:font-extrabold [&_ul]:list-disc [&_ul]:space-y-1.5 [&_ul]:pl-5 [&_a]:text-brand-primary [&_a]:underline">
        @yield('content')
    </div>
</main>

<footer class="bg-[#0F1B35] py-8 text-center text-xs text-white/60">
    @include('legal.links')
    <p class="mt-3">&copy; {{ now()->year }} {{ $legal['company'] }}. {{ __('All rights reserved.') }}</p>
</footer>
</body>
</html>
