@props(['title', 'period' => null])
<x-card>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ $title }}</h3>
        @if ($period)
            <span class="rounded-full bg-brand-primary/10 px-2.5 py-1 text-xs font-medium text-brand-primary">{{ $period }}</span>
        @endif
    </div>
    {{ $slot }}
</x-card>
