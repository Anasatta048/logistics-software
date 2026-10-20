@props(['status', 'label' => null, 'type' => 'button'])
@php
    // One place that knows what each status "feels" like, so the advance
    // button looks the same on the loads index and the edit screen instead of
    // each picking its own green.
    $tone = match ($status->value) {
        'pickup', 'loaded' => 'border-status-loaded/40 text-status-loaded hover:bg-status-loaded/10',
        'delivered' => 'border-status-dispatched/40 text-status-dispatched hover:bg-status-dispatched/10',
        'paid' => 'border-status-paid/40 text-status-paid hover:bg-status-paid/10',
        default => 'border-border text-text-muted hover:bg-border/60 dark:hover:bg-white/10',
    };
    $classes = 'group inline-flex items-center gap-1.5 rounded-full border bg-transparent px-3 py-1.5 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-brand-primary '.$tone;
@endphp
{{-- $type is a prop rather than a literal on the tag: merging a caller's
     `type` onto a hardcoded one leaves two attributes, and the browser keeps
     the first — so the form would never submit. --}}
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $label ?? __('Mark :status', ['status' => $status->label()]) }}
    <x-icon name="chevron-right" :size="14" class="shrink-0 transition-transform group-hover:translate-x-0.5" />
</button>
