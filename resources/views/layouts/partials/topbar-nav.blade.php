@php($groups = \App\Support\Navigation::forCurrentUser())
<div class="hidden border-b border-border bg-surface dark:border-white/10 dark:bg-surface-dark lg:block">
    <nav class="topnav flex gap-0.5 overflow-x-auto px-4 sm:px-6">
        @foreach ($groups as $group)
            @foreach ($group['items'] as $item)
                <a href="{{ route($item['route']) }}"
                    class="relative flex shrink-0 items-center gap-1.5 whitespace-nowrap px-3 py-3 text-[13px] font-medium transition
                        {{ $item['active'] ? 'text-brand-primary' : 'text-text-muted hover:bg-bg hover:text-text-primary dark:hover:bg-white/5 dark:hover:text-white' }}">
                    <x-icon :name="$item['icon']" :size="15" />
                    {{ $item['label'] }}
                    @if ($item['active'])
                        <span class="absolute inset-x-0 bottom-0 h-0.5 bg-brand-primary"></span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>
</div>
