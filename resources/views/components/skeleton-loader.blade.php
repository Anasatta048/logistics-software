@props(['variant' => 'row', 'count' => 3])
{{--
    Card-shaped / row-shaped pulsing placeholders (Section 4 "Loading states").
    This app is server-rendered Blade with no client-side data fetch on these
    pages, so there is no real "loading" moment to trigger this component on
    any current screen — it's provided here, ready to drop into any future
    AJAX/API-driven view (e.g. a live-refreshing dashboard widget), per the
    plan's own note that this requirement is forward-looking.
--}}
@if ($variant === 'card')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @for ($i = 0; $i < $count; $i++)
            <div class="h-24 animate-pulse rounded-card bg-border/60 dark:bg-white/10"></div>
        @endfor
    </div>
@else
    <div class="space-y-3">
        @for ($i = 0; $i < $count; $i++)
            <div class="h-10 animate-pulse rounded-input bg-border/60 dark:bg-white/10"></div>
        @endfor
    </div>
@endif
