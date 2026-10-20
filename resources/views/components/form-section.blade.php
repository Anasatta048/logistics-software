@props(['title', 'icon' => 'info', 'description' => null, 'step' => null])
<section {{ $attributes->merge(['class' => 'form-section']) }}>
    <header class="form-section-head">
        <span class="form-section-icon"><x-icon :name="$icon" :size="18" /></span>
        <div class="min-w-0">
            <h3 class="form-section-title">
                @if ($step)<span class="form-section-step">{{ $step }}</span>@endif{{ $title }}
            </h3>
            @if ($description)
                <p class="form-section-desc">{{ $description }}</p>
            @endif
        </div>
    </header>
    <div class="form-section-body">
        {{ $slot }}
    </div>
</section>
