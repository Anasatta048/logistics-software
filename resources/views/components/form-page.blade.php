@props(['title', 'subtitle' => null, 'icon' => 'file-text', 'back' => null, 'backLabel' => null, 'tips' => [], 'wide' => false])
{{--
    Shell for every create/edit screen: a title block with a big icon, the form
    in the middle, and (optionally) a short "how this works" panel beside it so a
    first-time user never has to guess what a field means.
--}}
<div class="form-page {{ $wide ? 'max-w-6xl' : 'max-w-5xl' }}">
    <div class="form-page-head">
        <span class="form-page-icon"><x-icon :name="$icon" :size="26" /></span>
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-0.5 text-sm text-text-muted">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($back)
            <a href="{{ $back }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ $backLabel ?? __('Back') }}</a>
        @endif
    </div>

    <div class="grid gap-5 {{ count($tips) ? 'lg:grid-cols-[1fr_17rem]' : '' }}">
        <div class="form-anim min-w-0 space-y-5">
            {{ $slot }}
        </div>

        @if (count($tips))
            <aside class="form-tips h-fit lg:sticky lg:top-4">
                <p class="form-tips-title"><x-icon name="sparkles" :size="15" /> {{ __('Quick guide') }}</p>
                <ul>
                    @foreach ($tips as $tip)
                        <li><x-icon name="check" :size="14" class="mt-0.5 shrink-0 text-brand-primary" /> <span>{{ $tip }}</span></li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</div>
