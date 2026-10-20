@props(['cancel' => null, 'submit' => null, 'icon' => 'save'])
<div {{ $attributes->merge(['class' => 'form-actions']) }}>
    <button type="submit" class="btn-primary"><x-icon :name="$icon" :size="16" /> {{ $submit ?? __('Save') }}</button>
    @if ($cancel)
        <a href="{{ $cancel }}" class="btn-ghost">{{ __('Cancel') }}</a>
    @endif
    {{ $slot }}
</div>
