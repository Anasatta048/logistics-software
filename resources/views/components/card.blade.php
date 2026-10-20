@props(['padded' => true])
<div {{ $attributes->merge(['class' => 'border border-border bg-surface dark:border-white/10 dark:bg-surface-dark ' . ($padded ? 'p-5 sm:p-6' : '')]) }}>
    {{ $slot }}
</div>
