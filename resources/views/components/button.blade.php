@props(['variant' => 'primary', 'as' => 'button', 'type' => 'submit'])
@php
    $base = 'inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-brand-primary';
    $variants = [
        'primary' => 'bg-brand-primary text-white hover:bg-brand-primary/90',
        'secondary' => 'bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-white/10 dark:hover:bg-white/15',
        'ghost' => 'bg-transparent text-text-primary dark:text-white hover:bg-border/60 dark:hover:bg-white/10',
        'danger' => 'bg-status-cancelled text-white hover:bg-status-cancelled/90',
    ];
    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp
@if ($as === 'a')
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
