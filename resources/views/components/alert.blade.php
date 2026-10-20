@props(['variant' => 'success', 'inline' => false])
@php
    $variants = [
        'success' => ['bg-status-paid/10 text-status-paid border-status-paid/20', 'check-circle'],
        'error' => ['bg-status-cancelled/10 text-status-cancelled border-status-cancelled/20', 'x-circle'],
        'warning' => ['bg-warning/10 text-warning border-warning/20', 'alert-triangle'],
        'danger' => ['bg-status-cancelled/10 text-status-cancelled border-status-cancelled/20', 'x-circle'],
        'info' => ['bg-brand-navy-light/10 text-brand-navy-light border-brand-navy-light/20', 'info'],
    ];
    [$classes, $icon] = $variants[$variant] ?? $variants['info'];
@endphp
<div @unless ($inline) data-alert="{{ $variant === 'danger' ? 'error' : $variant }}" @endunless {{ $attributes->merge(['class' => "flex items-start gap-2 rounded-input border px-4 py-3 text-sm $classes"]) }}>
    <x-icon :name="$icon" :size="16" class="mt-0.5 shrink-0" />
    <div>{{ $slot }}</div>
</div>
