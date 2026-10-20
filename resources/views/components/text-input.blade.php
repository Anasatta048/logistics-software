@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-border bg-surface text-text-primary focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white']) }}>
