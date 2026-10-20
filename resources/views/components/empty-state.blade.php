@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'description' => null])
<div class="flex flex-col items-center justify-center gap-3 py-14 text-center">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-primary/10 text-brand-primary">
        <x-icon :name="$icon" :size="24" />
    </div>
    <p class="text-sm font-semibold text-text-primary dark:text-white">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
